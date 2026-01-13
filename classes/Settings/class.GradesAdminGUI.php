<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use ILIAS\UI\Implementation\Component\ReplaceSignal;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\UI\Implementation\Component\Table\Table;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\SmallView;
use Generator;
use ILIAS\MetaData\Editor\Full\Services\Tables\TableFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\UI\Implementation\Component\Modal\RoundTrip;
use ILIAS\Plugin\LongEssayAssessment\UI\Tree\RepositorySelectModal;
use ILIAS\UI\Component\Component;
use ILIAS\Export\ImportStatus\Exception\ilException;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\Data\GradeLevel;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Settings
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\GradesAdminGUI: ilObjLongEssayAssessmentGUI
 */
class GradesAdminGUI extends BaseGUI implements DataTableParent
{
    use ConfirmationIds;
    use SmallView;

    private ?int $copy_context = null;
    private \Edutiek\AssessmentService\Assessment\GradeLevel\FullService $grade_service;
    private \Edutiek\AssessmentService\Task\AssessmentStatus\FullService $assessment_status;
    private bool $can_edit;
    private \Edutiek\AssessmentService\System\Entity\FullService $entity_service;
    protected \ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory $table_factory;
    private bool $is_disabled;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->table_factory = $this->plugin_ui_factory->table();
        $this->grade_service = $this->assessment_api->gradeLevel();
        $this->assessment_status = $this->task_api->assessmentStatus();
        $this->entity_service = $this->system_api->entity();
        $this->is_disabled = $this->disabled_group->isDisabled('tab_grades', 'grades');
        $this->can_edit = !$this->assessment_status->hasAuthorizedSummaries() && !$this->is_disabled;
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $this->initTools(false, true);

        $cmd = $this->ctrl->getCmd('showItems');
        switch ($cmd) {
            case 'updateItem':
            case 'showItems':
            case "editItem":
            case 'delete':
            case 'copyGrades':
                $this->$cmd();
                break;
            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Show the items
     */
    protected function showItems()
    {
        $components = [];

        $components[] = $table = $this->table_factory->dataTable("grade_table", $this);
        $table->setTitle($this->plugin->txt('grade_levels'));

        if ($this->can_edit) {
            $table->addActionToToolbar($this->toolbar, $table->getActionByName("add_grade_level"), true);

            $select = $this->buildRepositorySelect();
            list($btn, $components[]) = $select->getToolbarComponents($this->plugin->txt("copy_grade_level"));
            $this->toolbar->addComponent($btn);

            $table->executeAction();
        } else {
            if (!$this->is_disabled) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt("grade_level_cannot_edit_used_info"));
            }
            $table->disableAction(true);
        }

        if (empty($this->getTableItems())) {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt("grade_levels_empty_notice"));
        }

        $this->tpl->setContent($this->renderer->render($components));
    }

    public function getColumnMapping(Item $item, ?array $additional_parameters): array
    {
        return [
            "title" => $item->getGrade(),
            "points" => $item->getMinPoints(),
            "passed" => $item->isPassed(),
            "code" => $item->getCode(),
            "statement" => $item->getStatement(),
        ];
    }

    public function getColumns(?array $additional_parameters): array
    {
        $tf = $this->ui_factory->table();

        $small_view = $this->smallView($additional_parameters);
        $sortable = !$small_view;

        return [
            "points" => $tf->column()->number($this->plugin->txt('min_points'))->withDecimals(2)->withIsSortable($sortable),
            "title" => $tf->column()->text($this->plugin->txt('grade_level'))->withIsSortable($sortable),
            "passed" => $tf->column()->boolean($this->plugin->txt('passed'), $this->lng->txt('yes'), $this->lng->txt('no'))->withIsSortable($sortable),
            "code" => $tf->column()->text($this->plugin->txt('grade_level_code'))->withIsSortable($sortable),
            "statement" => $tf->column()->text($this->plugin->txt('grade_level_statement'))->withIsSortable(false),
        ];
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        $items = iterator_to_array($this->getTableItems(null, $filter_data));
        return count($items);
    }

    public function getTableActions(): array
    {
        return [
            $this->editAction(),
            $this->deleteAction(),
            $this->createAction()
        ];
    }

    protected function createAction()
    {
        return $this->table_factory->action()->form(
            "add_grade_level",
            $this->plugin->txt('add_grade_level'),
            $this->lng->txt('save'),
            [$this, "buildFields"],
            [$this, "save"],
            fn(GradeItem $x) => $this->can_edit,
            Action\Type::Global
        );
    }

    protected function deleteAction()
    {
        return $this->table_factory->action()->confirmation(
            "delete_grade_level",
            $this->lng->txt('delete'),
            $this->lng->txt('delete'),
            $this->plugin->txt('delete_grade_level_confirmation'),
            $this->ctrl->getFormAction($this, 'delete'),
            fn(GradeItem $item) => $item->getGrade(),
            fn(GradeItem $x) => $this->can_edit,
            Action\Type::Standard
        );
    }

    protected function editAction()
    {
        return $this->table_factory->action()->form(
            "edit_grade_level",
            $this->lng->txt('edit'),
            $this->lng->txt('save'),
            [$this, "buildFields"],
            [$this, "save"],
            fn(GradeItem $x) => $this->can_edit,
            Action\Type::Single
        );
    }

    public function save(GradeItem $item, array $data)
    {
        if ($item->getId() === 0) {
            $grade_level = $this->grade_service->new();
        } else {
            $grade_level = $this->grade_service->one($item->getId());
        }
        $grade_level->setGrade($data['grade'])
                    ->setMinPoints($data['points'])
                    ->setCode($data['code'])
                    ->setPassed($data['passed'])
                    ->setStatement($data['statement']);

        $this->entity_service->secure($grade_level, GradeLevel::class);
        $this->grade_service->save($grade_level);
        $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    public function buildFields(GradeItem $item): array
    {
        $factory = $this->ui_factory->input()->field();
        $plugin_factory = $this->plugin_ui_factory->field();
        $fields = [];
        $fields['grade'] = $factory->text($this->plugin->txt("grade_level"))
                                   ->withRequired(true)
                                   ->withValue($item->getGrade());

        $fields['points'] = $plugin_factory->numeric($this->plugin->txt('min_points'), $this->plugin->txt("min_points_caption"))
                                           ->withStep(0.01)
                                           ->withRequired(true)
                                           ->withValue($item->getMinPoints());

        $fields['passed'] = $factory->checkbox($this->plugin->txt('passed'), $this->plugin->txt("passed_caption"))
                                   ->withRequired(true)
                                   ->withValue($item->isPassed());

        $fields['code'] = $factory->text($this->plugin->txt("grade_level_code"), $this->plugin->txt("grade_level_code_caption"))
            ->withRequired(false)
            ->withValue(!empty($item->getCode()) ? $item->getCode() : "");

        $fields['statement'] = $factory->textarea($this->plugin->txt("grade_level_statement"), $this->plugin->txt("grade_level_statement_caption"))
            ->withRequired(false)
            ->withValue($item->getStatement() ?? "");

        return $fields;
    }

    protected function tableItemFromData(GradeLevel $item): GradeItem
    {
        return new GradeItem($item->getId(), $item->getGrade(), $item->getMinPoints(), $item->getPassed(), $item->getCode(), $item->getStatement());
    }

    public function getTableItem(int $id): Item
    {
        return $this->tableItemFromData($this->grade_service->one($id) ?? $this->grade_service->new());
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = []): Generator
    {
        if ($this->copy_context !== null) {
            $copy_object = new \ilObjLongEssayAssessment($this->copy_context);
            $copy_assessment_api = $this->plugin->dic()->assessment($copy_object->getAssId(), $this->user->getId());
            $copy_grade_service = $copy_assessment_api->gradeLevel();

            foreach ($copy_grade_service->all() as $object) {
                yield $this->tableItemFromData($object);
            }
            return;
        }

        if ($ids === [0]) {
            yield $this->tableItemFromData($this->grade_service->new());
            return;
        }
        foreach ($this->grade_service->all() as $object) {
            if (empty($ids) || in_array($object->getId(), $ids)) {
                yield $this->tableItemFromData($object);
            }
        }
    }

    protected function delete()
    {
        if (!$this->can_edit) {
            throw new ilException("Operation not permitted");
        }

        $ids = $this->confirmationIds();

        foreach ($ids as $id) {
            $grade_level = $this->grade_service->one($id);
            if ($grade_level !== null) {
                $this->grade_service->delete($grade_level);
            }

        }
        # Is not needed anymore because grade level are shown dynamically for summaries
        # $this->grade_service->recalculateGradeLevel();

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("delete_grade_level_successful"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    protected function copyGrades()
    {
        if (!$this->can_edit) {
            throw new ilException("Operation not permitted");
        }
        $select = $this->buildRepositorySelect();

        if ($select->hasSelected()) {
            $copy_object = new \ilObjLongEssayAssessment($select->getSelectedId());
            $copy_assessment_api = $this->plugin->dic()->assessment($copy_object->getAssId(), $this->user->getId());
            $copy_grade_service = $copy_assessment_api->gradeLevel();

            if ($copy_assessment_api->permissions($select->getSelectedId())->canEditGrades()) {
                foreach ($this->grade_service->all() as $grade) {
                    $this->grade_service->delete($grade);
                }
                foreach ($copy_grade_service->all() as $grade_level) {
                    $new_grade_level = clone $grade_level;
                    $new_grade_level->setAssId($this->object->getAssId());
                    $new_grade_level->setId(0);
                    $this->grade_service->save($new_grade_level);
                }
                # Is not needed anymore because grade level are shown dynamically for summaries
                # $this->grade_service->recalculateGradeLevel();

                $this->tpl->setOnScreenMessage("success", $this->plugin->txt('copy_grade_level_successful'), true);
            } else {
                throw new ilException("Operation not permitted");
            }

            $this->ctrl->redirect($this, "showItems");
        } else {
            $select->showAsync();
        }
    }

    protected function buildRepositorySelect(): RepositorySelectModal
    {
        return $this->plugin_ui_factory->tree()->repositorySelect(
            $this->object->getRefId(),
            $this->plugin->txt("copy_grade_level"),
            [$this, "listGrades"],
            $this->ctrl->getLinkTarget($this, 'copyGrades', null, true)
        )->setPermission("maintain_task")
         ->setMessage($this->plugin->txt('copy_grade_level_info'));
    }

    public function listGrades(int $ref_id): Component
    {
        $this->copy_context = $ref_id;

        $table = $this->table_factory->dataTable("grades_copy", $this);
        $table->setAdditionalParameter($this->setSmallView());

        return $table->getTable();
    }
}
