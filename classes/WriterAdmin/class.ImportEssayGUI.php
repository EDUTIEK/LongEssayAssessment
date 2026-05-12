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

use Edutiek\AssessmentService\EssayTask\EssayImport\FullService as ImportService;
use Edutiek\AssessmentService\EssayTask\EssayImport\Row;
use Generator;
use ILIAS\Data\Order;
use ILIAS\Data\Range;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Component\Table\Data as Table;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ilLongEssayAssessmentUploadHandlerGUI;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\DataRetrievalFactory;

class ImportEssayGUI extends BaseGUI
{
    use DataRetrievalFactory;
    private readonly ilLongEssayAssessmentUploadHandlerGUI $upload_handler;
    private readonly ImportService $import;

    /** @var Row[] */
    private array $rows = [];

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->import = $this->essay_task_api->import($this->task_info->getId());
        $this->upload_handler = new ilLongEssayAssessmentUploadHandlerGUI(
            $this->system_api->tempStorage(),
            $this->plugin->dic()->uploadTempFile()
        );
    }

    public function executeCommand(): void
    {
        $this->initTools(true, false);
        $cmd = $this->ctrl->getCmd('showForm');
        if (in_array($cmd, ['showForm', 'saveForm', 'showTable', 'cancel', 'import', 'importOverwrite'], true)) {
            $this->$cmd();
        } else {
            echo 'Invalid cmd';
        }
    }

    private function buildForm(): StandardForm
    {
        $inputs = [
            'title' => $this->ui_factory->input()->field()->section([], $this->plugin->txt('essay_import')),
            'file' => $this->ui_factory->input()->field()->file(
                $this->upload_handler,
                $this->plugin->txt('essay_import_zip_name')
            )->withAcceptedMimeTypes(['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'multipart/zip']),
            'hash' => $this->ui_factory->input()->field()->text($this->plugin->txt('essay_import_hash')),
            'password' => $this->ui_factory->input()->field()->optionalGroup([
                'value' => $this->ui_factory->input()->field()->text($this->plugin->txt('essay_import_password')),
            ], $this->plugin->txt('essay_import_use_password'))->withValue(null),
        ];

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getLinkTarget($this, 'saveForm'), $inputs);
    }

    public function showForm(): void
    {
        $form = $this->buildForm();
        $this->add($form)->show();
    }

    public function saveForm(): void
    {
        $form = $this->buildForm()->withRequest($this->request);
        $result = $form->getInputGroup()->getContent();
        $data = $form->getData();

        if ($data && !empty($data['file'])) {
            $upload_id = (string) current($data['file']);

            $stored = $this->system_api->tempStorage()->saveFile(
                $this->upload_handler->getApiStream($upload_id),
                $this->upload_handler->getApiInfo($upload_id)
            );

            $result = $this->import->processZipFile(
                $stored->getId(),
                $data['password']['value'] ?? null,
                $data['hash'] ?? null
            );

            $this->system_api->tempStorage()->deleteFile($stored->getId());

            if ($result->isOk()) {
                if ($result->hasMessages()) {
                    $this->info($result->getMessagesAsHtml(), true);
                }
                $this->ctrl->redirect($this, 'showTable');
            }
            $this->failure($result->getMessagesAsHtml());
        }

        $this->add($form)->show();
    }

    public function showTable(): void
    {
        $this->rows = $this->import->tableRows();

        $column = $this->ui_factory->table()->column();
        $ok = $this->ui_factory->symbol()->icon()->custom('assets/images/standard/icon_ok.svg', '', 'small');
        $nok = $this->ui_factory->symbol()->icon()->custom('assets/images/standard/icon_not_ok.svg', '', 'small');

        $columns = array_map(fn($c) => match ($c->getType()) {
            'text' => $column->text($c->getTitle()),
            'boolean' => $column->boolean($c->getTitle(), $ok, $nok),
        }, $this->import->tableColumns());

        $this->add($this->plugin_ui_factory->table()->standard($this->plugin->txt('essay_import_table'), $columns, $this->getDataRetrival())
            ->withRequest($this->dic->http()->request()));

        $files = $this->import->relevantFiles();
        $has_errors = in_array(false, array_map(fn($file) => $file->isImportPossible(), $files));
        $overwrites = count(array_filter($files, fn($file) => $file->isImportPossible() && $file->isExisting()));

        $import_button = $this->ui_factory->button()->primary(
            $this->plugin->txt($has_errors ? 'essay_import_zip_only_valid' : 'essay_import'),
            $this->ctrl->getLinkTarget($this, 'import')
        );

        if ($overwrites > 0) {
            $modal = $this->ui_factory->modal()->roundtrip($this->plugin->txt('essay_import'), [
                $this->ui_factory->legacy('<span>' . sprintf($this->plugin->txt('essay_import_confirmation'), $overwrites) . '</span>'),
            ], [], $this->ctrl->getLinkTarget($this, 'import'))->withActionButtons([
                $this->ui_factory->button()->primary($this->plugin->txt('essay_import_no_overwrite'), $this->ctrl->getLinkTarget($this, 'import')),
                $this->ui_factory->button()->standard($this->plugin->txt('essay_import_overwrite'), $this->ctrl->getLinkTarget($this, 'importOverwrite'))
            ]);

            $this->add($import_button->withOnClick($modal->getShowSignal()));
            $this->add($modal);
        } else {
            $this->add($import_button);
        }

        $this->add($this->ui_factory->button()->standard($this->lng->txt('cancel'), $this->ctrl->getLinkTarget($this, 'cancel')));
        $this->show();
    }

    public function import(bool $overwrite = false): void
    {
        $imported = $this->import->importFiles($overwrite);
        if ($imported == 1) {
            $this->success($this->plugin->txt('essay_imported_one'), true);
        } elseif ($imported > 0) {
            $this->success(sprintf($this->plugin->txt('essay_imported_n'), $imported), true);
        } else {
            $this->failure($this->plugin->txt('essay_imported_none'), true);
        }

        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterAdminGUI::class));
    }

    public function importOverwrite(): void
    {
        $this->import(true);
    }

    public function cancel(): void
    {
        $this->import->cleanup();
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterAdminGUI::class));
    }

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
}
