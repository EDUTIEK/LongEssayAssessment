<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\Plugin\LongEssayAssessment\Handler\Upload;
use ILIAS\Plugin\LongEssayAssessment\Handler\UploadHelper;
use ILIAS\FileUpload\Handler\FileInfoResult;
use Generator;
use ZipArchive;
use ilObjUser;
use Edutiek\AssessmentService\EssayTask\Data\EssayImport;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo;
use DateTimeImmutable;
use Edutiek\AssessmentService\Assessment\Data\WritingStatus;
use ILIAS\Filesystem\Stream\Streams;
use Closure;

class ImportEssayGUI extends BaseGUI
{
    public const SESSION_KEY = 'huhu';
    public const PROTOCOL_FILE_NAME = 'Abgabe-Protokoll.csv';
    public const NRW_FILE_PATTERN = '/^\d+von\d+_(\d+-\d+).pdf$/';
    public const BY_FILE_PATTERN = '/^[[:alnum:]]+-\d+_([[:alpha:]]-\d+)_.*.pdf$/';

    // Values are lang vars.
    private const BY_CSV_MAP = [
        'id' => 'by_csv_id',
        'alloc_time' => 'by_csv_alloc_time',
        'used_time' => 'by_csv_used_time',
        'logged_in' => 'by_csv_logged_in',
        'given_up' => 'by_csv_given_up',
        'pdf_hash' => 'by_csv_pdf_hash',
    ];

    private readonly UploadHelper $upload;

    public function __construct(BaseObjectData $object)
    {
        // @Todo: Add access right here.
        parent::__construct($object);

        $this->upload = new UploadHelper($this->dic);
    }

    public function executeCommand(): void
    {
        if (in_array($this->ctrl->getCmd(), ['uploadConfigGUI', 'upload', 'uploadZip', 'cancel', 'import'], true)) {
            $this->{$this->ctrl->getCmd()}();
        } else {
            echo 'Invalid cmd';
        }
    }

    public function uploadConfigGUI(): void
    {
        if (\ilSession::get(self::SESSION_KEY)) {
            $this->uploadZip();
            return;
        }
        $form = $this->ui_factory->input()->container()->form()->standard($this->ctrl->getLinkTarget($this, __FUNCTION__), [
            'file' => $this->ui_factory->input()->field()->file(new Upload(
                $this->fileInfo(...),
                fn($cmd) => $this->ctrl->getLinkTarget($this, $cmd),
            ), 'Zip'),
            'hash' => $this->ui_factory->input()->field()->text('Hash'),
            'password' => $this->ui_factory->input()->field()->optionalGroup([
                'value' => $this->ui_factory->input()->field()->text('pwd'),
            ], 'Password protected?')->withValue(null),
        ]);

        $form = $this->withFormData($form, function ($data): void {
            $stream = $this->system_api->fileStorage()->getFileStream(current($data['file']));
            if (($data['hash'] ?? null) && $data['hash'] !== $this->hash(stream_get_contents($stream))) {
                $this->failure($this->plugin->txt('import_hash_mismatch'), false);
                return;
            }
            $zip = new ZipArchive();
            $path = stream_get_meta_data($stream)['uri'];
            $zip->open($path, ZipArchive::RDONLY);
            $files = $this->filesFromZip($zip, $data['password']['value'] ?? null);
            $file_map = [];
            foreach ($files as $file) {
                if ($this->isRelevantFile($file)) {
                    $info = (new FileInfo())
                        ->setMimeType('application/pdf')
                        ->setFileName($file);
                    //dd(stream_get_meta_data($zip->getStream($file))['uri']);
                    $id = $this->system_api->fileStorage()->saveFile(Streams::ofString(stream_get_contents($zip->getStream($file))), $info)->getId();
                    $file_map[$file] = $id;
                }
            }
            \ilSession::set(self::SESSION_KEY, [
                'files' => $file_map,
            ]);
            $this->system_api->fileStorage()->deleteFile(current($data['file']));
            // $import = $this->essay_task_api->essayImport()->new(current($data['file']), $data['password']['value'] ?? null, $data['hash'] ?: null);
            // $this->essay_task_api->essayImport()->save($import);
            // $this->ctrl->setParameter($this, 'import_id', (string) $import->getId());
            // $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'uploadZip'));
        });

        $this->renderContent($form);
    }

    public function uploadZip(): void
    {
        $files = \ilSession::get(self::SESSION_KEY)['files'];

        if (isset($files[self::PROTOCOL_FILE_NAME])) {
            $this->by($files[self::PROTOCOL_FILE_NAME], $files);
            return;
        }

        $this->nrw($files);
    }

    public function upload(): never
    {
        $upload = $this->dic->upload();
        $upload->process();
        $result_array = $upload->getResults();
        $result = current($result_array);
        if (!(($result ?: null)?->isOk())) {
            $this->upload->exitWithJson($this->upload->errorJson('No upload'));
        }

        $info = $this->system_api->fileStorage()->saveFile(fopen($result->getPath(), 'rb'), null);
        $this->upload->exitWithJson($this->upload->okJson($info->getId()));
    }

    public function import(): void
    {
        $users = $this->existingUsers();
        $files = \ilSession::get(self::SESSION_KEY)['files'];
        $pdfs = $this->filesByLogin($files);
        $now = new DateTimeImmutable();

        foreach ($users as $user_id => $login) {
            $zip_pdf = $files[$pdfs[$login] ?? null] ?? null;
            if (!$zip_pdf) {
                continue;
            }
            $writer = $this->assessment_api->writer()->getByUserId($user_id);

            $task = $this->task_api->manager()->first();
            $essay = $this->essay_task_api->essay()->oneByWriterIdAndTaskId($writer->getId(), $task->getId()) ??
                $this->essay_task_api->essay()->new($writer->getId(), $task->getId())->setFirstChange($now);

            $essay = $essay->setLastChange($now);


            $pdf = $essay->getPdfVersion();
            $resource_api = $this->task_api->resource($essay->getTaskId());
            $resource = $resource_api->new();
            $resource->setFileId($zip_pdf);
            $resource_api->save($resource);
            $essay->setPdfVersion((string) $resource->getId());
            $this->essay_task_api->essay()->save($essay);
            if ($pdf) {
                $pdf = $resource_api->one((int) $pdf);
                $resource_api->delete($pdf);
            }
            $writer->setWorkingStart($writer->getWorkingStart() ?? $now);
            $writer->setWritingAuthorized($now);
            $writer->setWritingAuthorizedBy($this->user->getId());
            $this->assessment_api->writer()->save($writer);

            // $this->essay_task_api->pdfInput()->handleInput($essay);
        }

        \ilSession::set(self::SESSION_KEY, null);
        $this->success($this->plugin->txt('upload_successful'), true);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'uploadConfigGUI'));
    }

    public function cancel(): void
    {
        $id = $this->get->integer('import_id');
        $import = $this->essay_task_api->essayImport()->getById($this->get->integer('import_id'));
        $this->essay_task_api->essayImport()->delete($import);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'uploadConfigGUI'));
    }

    private function nrw(ZipArchive $zip, array $pdfs): void
    {
        // @Todo
    }

    private function by(string $protocol_id, array $pdfs): void
    {
        $hashes = $this->buildPdfHashes($pdfs);
        $array = $this->buildTableArray($this->readProtocol($protocol_id), $pdfs, $hashes);

        if ($array === null) {
            $this->failure($this->plugin->txt('import_protocol_missing'));
        }

        $this->ctrl->saveParameter($this, 'import_id');
        $this->renderContent([
            $this->table($hashes, $array, $pdfs),
            $this->ui_factory->button()->primary($this->plugin->txt('import_zip'), $this->ctrl->getLinkTarget($this, 'import')),
            $this->ui_factory->button()->standard($this->lng->txt('cancel'), $this->ctrl->getLinkTarget($this, 'cancel')),
        ]);
    }

    private function iterator(callable $proc, $end = false): Generator
    {
        $v = $proc();
        while($v !== $end)
        {
            yield $v;
            $v = $proc();
        }
    }

    private function table(array $hashes, array $data, array $pdfs)
    {
        $retrieval = new class implements DataRetrieval {
            public array $data;
            public function getRows(
                DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                ?array $filter_data,
                ?array $additional_parameters
            ): Generator
            {
                yield from array_map(fn($row) => $row_builder->buildDataRow($row['id'], $row), $this->data);
            }

            public function getTotalRowCount(
                ?array $filter_data,
                ?array $additional_parameters
            ): ?int
            {
                return -1;
            }
        };

        $retrieval->data = $data;
        $c = $this->ui_factory->table()->column();
        return $this->ui_factory->table()->data('hej', [
            'file' => $c->text($this->plugin->txt('file')),
            'id' => $c->text($this->plugin->txt('id')),
            'hash_ok' => $c->boolean($this->plugin->txt('hash_ok'), $this->plugin->txt('ok'), $this->plugin->txt('not ok')),
            'error' => $c->text($this->plugin->txt('error')),
        ], $retrieval)->withRequest($this->dic->http()->request());
    }

    private function extract(string $pattern, string $subject, int $index): ?string
    {
        return preg_match($pattern, $subject, $matches) ?
            $matches[$index] :
            null;
    }

    private function determineError(array $pdfs, string $login): array
    {
        $errors = [];
        if (!isset($pdfs[$login])) {
            $errors[] = 'File Missing';
        }

        if (!ilObjUser::getUserIdByLogin($login)) {
            $errors[] = 'User does not exist';
        }

        return $errors;
    }

    private function fileInfo(string $identifier): ?FileInfoResult
    {
        return null;
    }

    private function hash(string $value): string
    {
        return hash($this->system_api->config()->getConfig()->getHashAlgo(), $value);
    }

    private function buildTableArray(array $protocol, array $pdfs, array $hashes): ?array
    {
        $pdfs = $this->filesByLogin($pdfs);
        $array = array_map(fn(array $row) => [
                'file' => $pdfs[str_replace(' ', '-', $row['id'])] ?? null,
                'id' => $row['id'],
                'hash_ok' => $row['pdf_hash'] === ($hashes[$pdfs[str_replace(' ', '-', $row['id'])] ?? false] ?? false),
                'error' => join(', ', $this->determineError($pdfs, str_replace(' ', '-', $row['id']))),
        ], $protocol);

        return $array;
    }

    private function buildPdfHashes(array $pdfs): array
    {
        return array_map(
            function(string $id): string {
                $s = $this->system_api->fileStorage()->getFileStream($id);
                $r = $this->hash(stream_get_contents($s));
                fclose($s);
                return $r;
            },
            $pdfs
        );
    }

    private function readProtocol(string $protocol_id): ?array
    {
        $csv = $this->system_api->fileStorage()->getFileStream($protocol_id);
        $x = fgets($csv); // Skip Description
        $by_row = $this->byCsvRow(fgetcsv($csv)); // Header

        $array = iterator_to_array($this->iterator(fn() => fgetcsv($csv), false));
        fclose($csv);
        return array_map($by_row, $array);
    }

    private function zipFromImport(EssayImport $import, $file = null): ZipArchive
    {
        $file = $file ?? $this->system_api->fileStorage()->getFileStream($import->getFileId());
        $zip = new ZipArchive();
        $zip->open(stream_get_meta_data($file)['uri'], ZipArchive::RDONLY);

        if ($import->getPassword() !== null) {
            $zip->setPassword($import->getPassword());
        }

        return $zip;
    }

    private function filesFromZip(ZipArchive $zip, ?string $password): array
    {
        return array_map(function ($index) use ($zip, $password) {
            $stat = $zip->statIndex($index);
            if (($stat['encryption_method'] ?? false) && $password === null) {
                throw new \Exception('No password given');
            }
            return $stat['name'];
        }, range(0, $zip->numFiles - 1));
    }

    /**
     * @return null|array<int, string>
     */
    private function existingUsers(): ?array
    {
        $protocol = $this->readProtocol(\ilSession::get(self::SESSION_KEY)['files'][self::PROTOCOL_FILE_NAME]);

        $users = array_column(array_map(
            fn($row) => [
                'key' => ilObjUser::getUserIdByLogin(str_replace(' ', '-', $row['id'])),
                'value' => str_replace(' ', '-', $row['id']),
            ],
            $protocol
        ), 'value', 'key');
        unset($users[0]); // Remove not found users.

        return $users;
    }

    private function filesByLogin(array $files): array
    {
        $files = array_column(array_map(fn(string $pdf) => [
            'key' => $this->extract(self::BY_FILE_PATTERN, $pdf, 1),
            'value' => $pdf,
        ], array_keys($files)), 'value', 'key');

        unset($files['']); // Remove null keys

        return $files;
    }

    private function isRelevantFile(string $name): bool
    {
        return $name === self::PROTOCOL_FILE_NAME
            || preg_match(self::NRW_FILE_PATTERN, $name)
            || preg_match(self::BY_FILE_PATTERN, $name);
    }

    private function byCsvRow(array $header): Closure
    {
        $txts = array_flip(array_map($this->plugin->txt(...), self::BY_CSV_MAP));

        $header = array_map(fn($key) => $txts[$key] ?? 'unknown', $header);
        return fn(array $row) => array_combine($header, $row);
    }
}
