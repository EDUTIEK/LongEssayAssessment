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
use ilSession;
use ILIAS\UI\Component\Table\Data as Table;
use ilTemporaryStakeholder;
use ILIAS\Plugin\LongEssayAssessment\System\File\StorageAdapter;
use Edutiek\AssessmentService\System\File\Storage;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo as Task;
use Edutiek\AssessmentService\Task\Resource\FullService as ResourceApi;

class ImportEssayGUI extends BaseGUI
{
    public const SESSION_KEY = self::class;
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

    private const IMPORT_TYPE = [
            'by' => [
                'pattern' => self::BY_FILE_PATTERN,
                'build' => 'buildByTableArray',
                'usersFromFiles' => 'byUsersFromFiles',
                'columns' => [
                    'file' => 'text',
                    'id' => 'text',
                    'hash_ok' => 'boolean',
                    'error' => 'text',
                ],
            ],
            'nrw' => [
                'pattern' => self::NRW_FILE_PATTERN,
                'build' => 'buildNrwTableArray',
                'usersFromFiles' => 'nrwUsersFromFiles',
                'columns' => [
                    'file' => 'text',
                    'id' => 'text',
                    'error' => 'text',
                ],
            ],
        ];

    private readonly UploadHelper $upload;
    private readonly Storage $perm_storage;
    private readonly Storage $temp_storage;
    private array $cache = [];

    public function __construct(BaseObjectData $object)
    {
        // @Todo: Add access right here.
        parent::__construct($object);

        $this->upload = new UploadHelper($this->dic);
        $this->perm_storage = $this->system_api->fileStorage();
        $this->temp_storage = new StorageAdapter(
            $this->dic->resourceStorage()->manage(),
            $this->dic->resourceStorage()->consume(),
            new ilTemporaryStakeholder()
        );
    }

    public function executeCommand(): void
    {
        if (in_array($this->ctrl->getCmd(), ['show', 'upload', 'showTable', 'cancel', 'import'], true)) {
            $this->{$this->ctrl->getCmd()}();
        } else {
            echo 'Invalid cmd';
        }
    }

    public function show(): void
    {
        $form = $this->ui_factory->input()->container()->form()->standard($this->ctrl->getLinkTarget($this, __FUNCTION__), [
            'file' => $this->ui_factory->input()->field()->file(new Upload(
                $this->fileInfo(...),
                fn($cmd) => $this->ctrl->getLinkTarget($this, $cmd),
            ), $this->plugin->txt('import_zip_name')),
            'hash' => $this->ui_factory->input()->field()->text($this->plugin->txt('import_hash')),
            'password' => $this->ui_factory->input()->field()->optionalGroup([
                'value' => $this->ui_factory->input()->field()->text($this->plugin->txt('import_password')),
            ], $this->plugin->txt('import_use_password'))->withValue(null),
        ]);

        $form = $this->withFormData($form, $this->saveForm(...));

        $this->renderContent($form);
    }

    public function showTable(): void
    {
        $files = $this->session()['files'];
        $hashes = $this->buildPdfHashes($files);

        $type = self::IMPORT_TYPE[$this->session()['type']];
        $array = $this->{$type['build']}($files, $hashes);

        $content = [$this->table($hashes, $array, $type['columns'])];
        $no_errors = [] === array_filter(array_column($array, 'hash_ok'), fn($x) => !$x)
            && in_array(array_unique(array_column($array, 'error')), [[], ['']], true);

        $content[] = $this->ui_factory->button()->primary($this->plugin->txt(
            $no_errors ?
                'import_zip' :
                'import_zip_only_valid'
        ), $this->ctrl->getLinkTarget($this, 'import'));

        $content[] = $this->ui_factory->button()->standard($this->lng->txt('cancel'), $this->ctrl->getLinkTarget($this, 'cancel'));

        $this->renderContent($content);
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

        $info = $this->temp_storage->saveFile(fopen($result->getPath(), 'rb'), null);
        $this->upload->exitWithJson($this->upload->okJson($info->getId()));
    }

    public function import(): void
    {
        $type = self::IMPORT_TYPE[$this->session()['type']];
        $users = $this->existingUsers($this->{$type['usersFromFiles']}($this->session()['files']));
        $files = $this->session()['files'];
        $pdfs = $this->filesByLogin($files, $type['pattern']);
        $now = new DateTimeImmutable();
        $imported = 0;

        foreach ($users as $user_id => $login) {
            $zip_pdf = $files[$pdfs[$login] ?? null] ?? null;
            if (!$zip_pdf) {
                continue;
            }

            $zip_pdf = $this->moveTempFileToPermanemt($zip_pdf);

            $writer = $this->writerByUser($user_id);
            $task = $this->task();
            $essay = $this->essayByWriter($writer->getId()) ??
                $this->essay_task_api->essay()->new($writer->getId(), $task->getId())->setFirstChange($now);
            $essay = $essay->setLastChange($now);
            $pdf = $essay->getPdfVersion();
            $resource_api = $this->task_api->resource($task->getId());
            $essay->setPdfVersion((string) $this->saveResource($resource_api, $zip_pdf));
            $this->essay_task_api->essay()->save($essay);
            if ($pdf) {
                $resource_api->delete($resource_api->one((int) $pdf));
            }
            $writer->setWorkingStart($writer->getWorkingStart() ?? $now);
            $writer->setWritingAuthorized($now);
            $writer->setWritingAuthorizedBy($this->user->getId());
            $this->assessment_api->writer()->save($writer);
            $imported++;

            // Comment in to start the background task
            // $this->essay_task_api->pdfInput()->handleInput($essay);
        }

        $this->saveSession(null);
        $this->success(sprintf($this->plugin->txt('upload_successful'), $imported), true);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'show'));
    }

    public function cancel(): void
    {
        array_map($this->temp_storage->deleteFile(...), $this->session()['files']);
        $this->saveSession(null);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'show'));
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

    private function table(array $hashes, array $data, array $columns): Table
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
        return $this->ui_factory->table()->data($this->plugin->txt('essay_import_table'), array_column(array_map(fn(string $name, string $type) => [
            'value' => $c->$type($this->plugin->txt('essay_import_column_' . $name), 'ok', 'not ok'),
            'key' => $name,
        ], array_keys($columns), array_values($columns)), 'value', 'key'), $retrieval)->withRequest($this->dic->http()->request());
    }

    private function extract(string $pattern, string $subject, int $index): ?string
    {
        return preg_match($pattern, $subject, $matches) ?
            $matches[$index] :
            null;
    }

    private function determineError(array $pdfs, string $login, array $hashes): array
    {
        $errors = [];
        if (!isset($pdfs[$login])) {
            $errors[] = $this->plugin->txt('import_file_missing');
        }

        $user_id = ilObjUser::getUserIdByLogin($login);
        if (!$user_id) {
            $errors[] = $this->plugin->txt('import_user_not_existing');
        } else {
            $writer = $this->writerByUser($user_id);
            $task = $this->task();
            $essay = $this->essayByWriter($writer->getId());
            if ($essay) {
                $pdf = $essay->getPdfVersion();
                if  ($pdf) {
                    $resource_api = $this->task_api->resource($task->getId());
                    $resource = $resource_api->one((int) $pdf);
                    $stream = $this->perm_storage->getFileStream($resource->getFileId());
                    $same = $hashes[$pdfs[$login]] === $this->hash(stream_get_contents($stream));
                    fclose($stream);
                    $errors[] = $same ?
                        $this->plugin->txt('import_same_file_exists') :
                        $this->plugin->txt('import_another_file_exists');
                }
            }
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

    private function buildByTableArray(array $files, array $hashes): array
    {
        $protocol = $this->readProtocol($files[self::PROTOCOL_FILE_NAME]);
        $pdfs = $this->filesByLogin($files, self::BY_FILE_PATTERN);
        return array_map(fn(array $row) => [
            'file' => $pdfs[$this->byLogin($row)] ?? null,
            'id' => $row['id'],
            'hash_ok' => $row['pdf_hash'] === ($hashes[$pdfs[$this->byLogin($row)] ?? false] ?? false),
            'error' => join(', ', $this->determineError($pdfs, $this->byLogin($row), $hashes)),
        ], $protocol);
    }

    private function buildNrwTableArray(array $files, array $hashes): array
    {
        $pdfs = $this->filesByLogin($files, self::NRW_FILE_PATTERN);
        return array_map(fn(string $login, string $file) => [
            'file' => $file,
            'id' => $login,
            'error' => join(', ', $this->determineError($pdfs, $login, $hashes))
        ], array_keys($pdfs), array_values($pdfs));
    }

    private function buildPdfHashes(array $pdfs): array
    {
        return array_map(
            function(string $id): string {
                $s = $this->temp_storage->getFileStream($id);
                $r = $this->hash(stream_get_contents($s));
                fclose($s);
                return $r;
            },
            $pdfs
        );
    }

    private function readProtocol(string $protocol_id): ?array
    {
        $csv = $this->temp_storage->getFileStream($protocol_id);
        $x = fgets($csv); // Skip Description
        $by_row = $this->byCsvRow(fgetcsv($csv)); // Header

        $array = iterator_to_array($this->iterator(fn() => fgetcsv($csv), false));
        fclose($csv);
        return array_map($by_row, $array);
    }

    private function filesFromZip(ZipArchive $zip, ?string $password): array
    {
        if ($password !== null) {
            $zip->setPassword($password);
        }
        return array_map(function ($index) use ($zip, $password): string {
            $stat = $zip->statIndex($index);
            if (($stat['encryption_method'] ?? false) && $password === null) {
                throw new \Exception('No password given');
            }
            return $stat['name'];
        }, range(0, $zip->numFiles - 1));
    }

    /**
     * @return array<int, string>
     */
    private function existingUsers(array $logins): array
    {
        $users = $this->keysBy(ilObjUser::getUserIdByLogin(...), $logins);
        unset($users[0]); // Remove not found users.

        return $users;
    }

    private function byUsersFromFiles(array $files): array
    {
        return array_map($this->byLogin(...), array_filter(
            $this->buildByTableArray($files, $this->buildPdfHashes($files)),
            fn(array $row) => $row['hash_ok'] && $row['error'] === ''
        ));
    }

    private function nrwUsersFromFiles(array $files): array
    {
        return array_filter(array_map(fn($file) => $this->extract(self::NRW_FILE_PATTERN, $file, 1), array_keys($files)));
    }

    private function filesByLogin(array $files, string $pattern): array
    {
        $files = $this->keysBy(
            fn(string $pdf) => $this->extract($pattern, $pdf, 1),
            array_keys($files)
        );

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

    private function session(): ?array
    {
        return ilSession::get(self::SESSION_KEY) ?? null;
    }

    private function saveSession(?array $files): void
    {
        ilSession::set(self::SESSION_KEY, $files);
    }

    private function typeByFiles(array $files): ?string
    {
        $names = array_keys($files);
        if (isset($files[self::PROTOCOL_FILE_NAME]) && preg_grep(self::BY_FILE_PATTERN, $names) !== []) {
            return 'by';
        }
        if (preg_grep(self::NRW_FILE_PATTERN, $names) !== []) {
            return 'nrw';
        }

        return null;
    }

    private function byLogin(array $row): string
    {
        return str_replace(' ', '-', $row['id']);
    }

    private function saveForm(array $data): void
    {
        $stream = $this->temp_storage->getFileStream(current($data['file']));
        if (($data['hash'] ?? null) && $data['hash'] !== $this->hash(stream_get_contents($stream))) {
            $this->fail('import_hash_mismatch');
        }
        $zip = new ZipArchive();
        $path = stream_get_meta_data($stream)['uri'];
        $code = $zip->open($path, ZipArchive::RDONLY);
        match ($code) {
            true => null,
            ZipArchive::ER_NOZIP => $this->fail('import_not_a_zip'),
            ZipArchive::ER_INCONS => $this->fail('import_zip_inconsistent'),
            default => $this->fail('import_unknown_error', 'ZipArchive returned error code: ' . $code),
        };
        $files = $this->filesFromZip($zip, $data['password']['value'] ?? null);
        $file_map = [];
        foreach ($files as $file) {
            if ($this->isRelevantFile($file)) {
                $info = (new FileInfo())
                    ->setMimeType('application/pdf')
                    ->setFileName($file);
                $s = $zip->getStream($file);
                $id = $this->temp_storage->saveFile(Streams::ofString(stream_get_contents($s)), $info)->getId();
                fclose($s);
                $file_map[$file] = $id;
            }
        }
        $type = $this->typeByFiles($file_map);
        $this->temp_storage->deleteFile(current($data['file']));
        if ($type === null) {
            array_map($this->temp_storage->deleteFile(...), $file_map);
            $this->fail('import_invalid_zip_format');
        }
        $this->saveSession(['type' => $type, 'files' => $file_map]);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'showTable'));
    }

    public function fail(string $lang_var, ?string $log = null): never
    {
        if ($log !== null) {
            $this->dic->logger()->xlas()->error($log);
        }
        $this->failure($this->plugin->txt($lang_var), true);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'show'));
    }

    private function moveTempFileToPermanemt(string $temp_file_id): string
    {
        $info = $this->temp_storage->getFileInfo($temp_file_id);
        $info->setId(null);
        $s = $this->temp_storage->getFileStream($temp_file_id);
        $perm_id = $this->perm_storage->saveFile($s, $info)->getId();
        $this->temp_storage->deleteFile($temp_file_id);

        return $perm_id;
    }

    private function saveResource(ResourceApi $resource_api, string $file_id): int
    {
        $resource = $resource_api->new();
        $resource->setFileId($file_id);
        $resource_api->save($resource);
        return $resource->getId();
    }

    private function writerByUser(int $user_id): ?Writer
    {
        $this->cache['writers'] ??= $this->keysBy(
            fn(Writer $writer) => $writer->getUserId(),
            $this->assessment_api->writer()->all()
        );

        return $this->cache['writers'][$user_id] ?? null;
    }

    private function task(): Task
    {
        return $this->cache['task'] ??= $this->task_api->manager()->first();
    }

    private function essayByWriter(int $writer_id): ?Essay
    {
        $this->cache['essays'] ??= $this->keysBy(
            fn(Essay $essay) => $essay->getWriterId(),
            $this->essay_task_api->essay()->allByTaskId($this->task()->getId())
        );

        return $this->cache['essays'][$writer_id] ?? null;
    }

    /**
     * @template A
     * @template B
     *
     * @param callable(A): B $proc
     * @param A[] $array
     * @return array<B, A>
     */
    private function keysBy(callable $proc, array $array): array
    {
        return array_column(array_map(
            fn($x) => ['value' => $x, 'key' => $proc($x)],
            $array
        ), 'value', 'key');
    }
}
