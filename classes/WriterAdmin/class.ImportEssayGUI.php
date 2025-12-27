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
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ilLongEssayAssessmentUploadHandlerGUI;

class ImportEssayGUI extends BaseGUI implements DataRetrieval
{
    private readonly ilLongEssayAssessmentUploadHandlerGUI $upload_handler;
    private readonly ImportService $import;

    /** @var Row[] */
    private array $rows = [];

    public function __construct(BaseObjectData $object)
    {
        // todo: add access check
        parent::__construct($object);

        // todo: get task_id as parameter
        $task_id = $this->task_api->manager()->first()->getId();

        $this->import = $this->essay_task_api->import($task_id);
        $this->upload_handler = new ilLongEssayAssessmentUploadHandlerGUI(
            $this->system_api->tempStorage(),
            $this->plugin->dic()->uploadTempFile()
        );
    }

    public function executeCommand(): void
    {
        if (in_array($this->ctrl->getCmd(), ['showForm', 'showTable', 'cancel', 'import', 'importOverwrite'], true)) {
            $this->{$this->ctrl->getCmd()}();
        } else {
            echo 'Invalid cmd';
        }
    }

    private function buildForm(): StandardForm
    {
        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getLinkTarget($this, __FUNCTION__), [
            'title' => $this->ui_factory->input()->field()->section([], $this->plugin->txt('import_essays')),
            'file' => $this->ui_factory->input()->field()->file($this->upload_handler, $this->plugin->txt('import_zip_name')),
            'hash' => $this->ui_factory->input()->field()->text($this->plugin->txt('import_hash')),
            'password' => $this->ui_factory->input()->field()->optionalGroup([
                'value' => $this->ui_factory->input()->field()->text($this->plugin->txt('import_password')),
            ], $this->plugin->txt('import_use_password'))->withValue(null),
        ]);
    }

    public function showForm(): void
    {
        $form = $this->buildForm();
        $this->renderContent($form);
    }

    public function saveForm(): void
    {
        $form = $this->buildForm()->withRequest($this->request);
        $data = $form->getData();

        if ($data && !empty($data['file'])) {
            $file_id = (string) current($data['file']);

            $result = $this->import->processZipFile(
                $file_id,
                $data['password']['value'] ?? null,
                $data['hash'] ?? null
            );

            if ($result->isOk()) {
                $this->ctrl->redirect($this, 'showTable');
            }
            $this->system_api->tempStorage()->deleteFile($file_id);
            $this->failure($result->getMessagesAsHtml());
        }
        $this->renderContent($form);
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

        $this->add($this->ui_factory->table()->data($this->plugin->txt('essay_import_table'), $columns, $this)
            ->withRequest($this->dic->http()->request()));

        $files = $this->import->relevantFiles();
        $has_errors = in_array(false, array_map(fn($file) => $file->isImportPossible(), $files));
        $overwrites = count(array_filter($files, fn($file) => $file->isImportPossible() && $file->isExisting()));

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
        $this->success(sprintf($this->plugin->txt('upload_successful'), $imported), true);
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
}
