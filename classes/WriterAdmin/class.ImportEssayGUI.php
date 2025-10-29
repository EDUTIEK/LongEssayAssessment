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
use Generator;
use ZipArchive;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo;
use ILIAS\Filesystem\Stream\Streams;
use ilSession;
use ILIAS\UI\Component\Table\Data as Table;
use ilTemporaryStakeholder;
use ILIAS\Plugin\LongEssayAssessment\System\File\StorageAdapter;
use Edutiek\AssessmentService\System\File\Storage;
use Edutiek\AssessmentService\EssayTask\EssayImport\FullService as ImportService;
use ILIAS\Plugin\LongEssayAssessment\EssayTask\EssayImport\Import as ImportAdapter;
use ilLongEssayAssessmentUploadHandlerGUI;

class ImportEssayGUI extends BaseGUI
{
    public const SESSION_KEY = self::class;

    private readonly Storage $perm_storage;
    private readonly Storage $temp_storage;
    private readonly ilLongEssayAssessmentUploadHandlerGUI $upload_handler;
    private readonly ImportService $import;
    private readonly ImportAdapter $import_adapter;

    public function __construct(BaseObjectData $object)
    {
        // @Todo: Add access right here.
        parent::__construct($object);

        $this->perm_storage = $this->system_api->fileStorage();
        $this->temp_storage = new StorageAdapter(
            $this->dic->resourceStorage()->manage(),
            $this->dic->resourceStorage()->consume(),
            new ilTemporaryStakeholder()
        );
        $this->import_adapter = new ImportAdapter(
            $this->temp_storage,
            $this->perm_storage,
            $this->system_api,
            fn() => $this->session()['files'] ?? [],
        );
        $this->import = $this->essay_task_api->import($this->import_adapter);
        $this->upload_handler = new ilLongEssayAssessmentUploadHandlerGUI($this->temp_storage, $this->plugin->dic()->uploadTempFile());
    }

    public function executeCommand(): void
    {
        if (in_array($this->ctrl->getCmd(), ['showForm', 'showTable', 'cancel', 'import', 'importOverwrite'], true)) {
            $this->{$this->ctrl->getCmd()}();
        } else {
            echo 'Invalid cmd';
        }
    }

    public function showForm(): void
    {
        $form = $this->ui_factory->input()->container()->form()->standard($this->ctrl->getLinkTarget($this, __FUNCTION__), [
            'title' => $this->ui_factory->input()->field()->section([], $this->plugin->txt('import_essays')),
            'file' => $this->ui_factory->input()->field()->file($this->upload_handler, $this->plugin->txt('import_zip_name')),
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
        $hashes = $this->import_adapter->buildPdfHashes(array_keys($files));

        $type = $this->import->type($this->session()['type']);
        $rows = $type->rows(array_keys($files), $hashes);

        $content = [$this->table($hashes, $rows, $type->columns())];
        $has_errors = in_array(false, array_map(fn($r) => $r->getImportPossible(), $rows));
        $overwrites = count(array_filter($rows, fn($r) => $r->getOverwrites() !== [] && $r->getImportPossible()));

        $import_button = $this->ui_factory->button()->primary(
            $this->plugin->txt($has_errors ? 'import_zip_only_valid' : 'import_zip'),
            $this->ctrl->getLinkTarget($this, 'import')
        );

        if ($overwrites > 0) {
            $lang_var = 'import_confirmation_content_' . ($overwrites === 1 ? 'singular' : 'plural');
            $modal = $this->ui_factory->modal()->roundtrip($this->plugin->txt('import_confirmation_title'), [
                $this->ui_factory->legacy('<span>' . sprintf($this->plugin->txt($lang_var), $overwrites) . '</span>'),
            ], [], $this->ctrl->getLinkTarget($this, 'import'))->withActionButtons([
                $this->ui_factory->button()->primary($this->plugin->txt('import_zip_no_overwrite'), $this->ctrl->getLinkTarget($this, 'import')),
                $this->ui_factory->button()->standard($this->plugin->txt('import_zip_overwrite'), $this->ctrl->getLinkTarget($this, 'importOverwrite'))
            ]);

            $import_button = $import_button->withOnClick($modal->getShowSignal());
            $content[] = $modal;
        }

        $content[] = $import_button;

        $content[] = $this->ui_factory->button()->standard($this->lng->txt('cancel'), $this->ctrl->getLinkTarget($this, 'cancel'));

        $this->renderContent($content);
    }

    public function import(bool $overwrite = false): void
    {
        $files = $this->session()['files'];
        $type = $this->import->type($this->session()['type']);
        $imported = $this->import->import($type, $files, $overwrite);

        array_map($this->temp_storage->deleteFile(...), $files);
        $this->saveSession(null);
        $this->success(sprintf($this->plugin->txt('upload_successful'), $imported), true);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterAdminGUI::class));
    }

    public function importOverwrite(): void
    {
        $this->import(true);
    }

    public function cancel(): void
    {
        array_map($this->temp_storage->deleteFile(...), $this->session()['files']);
        $this->saveSession(null);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterAdminGUI::class));
    }

    public function txt(string $lang_var): string
    {
        return $this->plugin->txt($lang_var);
    }

    private function table(array $hashes, array $rows, array $columns): Table
    {
        $retrieval = new class () implements DataRetrieval {
            public array $rows;
            public function getRows(
                DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                    ?array $filter_data,
                    ?array $additional_parameters
            ): Generator {
                $field = key($order->get());
                $dir = current($order->get()) === 'ASC' ? 1 : -1;
                usort($this->rows, fn($row, $other) => $dir * strcmp((string) $row->getFields()[$field], (string) $other->getFields()[$field]));
                yield from array_map(fn($row) => $row_builder->buildDataRow($row->getId(), $row->getFields()), $this->rows);
            }

            public function getTotalRowCount(
                ?array $filter_data,
                    ?array $additional_parameters
            ): ?int {
                // Disable pagination but enable ordering.
                // See ILIAS\UI\Implementation\Component\Table\TableViewControlPagination::getViewControlPagination (requires less than 5)
                // See ILIAS\UI\Implementation\Component\Table\TableViewControlOrdering::getViewControlOrdering (requires more than 1)
                return 2;
            }
        };

        $retrieval->rows = $rows;

        $column = $this->ui_factory->table()->column();
        $ok = $this->ui_factory->symbol()->icon()->custom('assets/images/standard/icon_ok.svg', '', 'small');
        $nok = $this->ui_factory->symbol()->icon()->custom('assets/images/standard/icon_not_ok.svg', '', 'small');

        $columns = array_map(fn($c) => match ($c->getType()) {
            'text' => $column->text($c->getTitle()),
            'boolean' => $column->boolean($c->getTitle(), $ok, $nok),
        }, $columns);

        return $this->ui_factory->table()->data($this->plugin->txt('essay_import_table'), $columns, $retrieval)
            ->withRequest($this->dic->http()->request());
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

    private function session(): ?array
    {
        return ilSession::get(self::SESSION_KEY) ?? null;
    }

    private function saveSession(?array $files): void
    {
        ilSession::set(self::SESSION_KEY, $files);
    }

    private function saveForm(array $data): void
    {
        $stream = $this->upload_handler->getApiStream(current($data['file']));
        if (($data['hash'] ?? null) && $data['hash'] !== $this->import_adapter->hash(stream_get_contents($stream))) {
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
            if ($this->import->isRelevantFile($file)) {
                $info = (new FileInfo())
                    ->setMimeType('application/pdf')
                    ->setFileName($file);
                $s = $zip->getStream($file);
                $id = $this->temp_storage->saveFile(Streams::ofString(stream_get_contents($s)), $info)->getId();
                fclose($s);
                $file_map[$file] = $id;
            }
        }

        $type = $this->import->typeByFiles(array_keys($file_map));
        $this->temp_storage->deleteFile(current($data['file']));
        if ($type === null) {
            array_map($this->temp_storage->deleteFile(...), $file_map);
            $this->fail('import_invalid_zip_format');
        }
        $this->saveSession(['type' => $type, 'files' => $file_map]);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'showTable'));
    }

    private function fail(string $lang_var, ?string $log = null): never
    {
        if ($log !== null) {
            $this->dic->logger()->xlas()->error($log);
        }
        $this->failure($this->plugin->txt($lang_var), true);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'showForm'));
    }
}
