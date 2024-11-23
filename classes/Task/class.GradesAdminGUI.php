<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\GradeLevel;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
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

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\GradesAdminGUI: ilObjLongEssayAssessmentGUI
 */
class GradesAdminGUI extends BaseGUI implements DataTableParent
{
    use ConfirmationIds, SmallView;

    protected \ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory $table_factory;
    protected TaskRepository $task_repo;
    protected ObjectRepository $object_repo;
    protected CorrectorAdminService $corrector_service;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->corrector_service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->object_repo = $this->localDI->getObjectRepo();
        $this->task_repo = $this->localDI->getTaskRepo();
        $this->table_factory = $this->localDI->getTableFactory();
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showItems');
        switch ($cmd) {
            case 'updateItem':
            case 'showItems':
            case "editItem":
            case 'deleteItem':
            case 'copyGradeLevelModalTree':
            case 'copyGradeLevelModalAsync':
            case 'copyGradeLevel':
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
        $can_delete = true;
        $settings = $this->task_repo->getTaskSettingsById($this->object->getId());
        $authorized = $this->corrector_service->authorizedCorrectionsExists();

        if ($settings->getCorrectionStart() !== null) {
            $correction_start = new \ilDateTime($settings->getCorrectionStart(), IL_CAL_DATETIME);
            $today = new \ilDateTime(time(), IL_CAL_UNIX);
            $can_delete = !\ilDate::_after($today, $correction_start);
        }

        if ($authorized) {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt("grade_level_cannot_edit_used_info"));
        } elseif (empty($this->getTableItems())) {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt("grade_levels_empty_notice"));
        }
        $table = $this->table_factory->dataTable("grade_table", $this);
        $table->setTitle($this->plugin->txt('grade_levels'));

        if (!$authorized) {
            $table->addActionToToolbar($this->toolbar, $table->getActionByName("add_grade_level"), true);

            $this->addModal($modal = $this->getCopyGradeLevelModal());
            $this->toolbar->addComponent($this->uiFactory->button()->standard($this->plugin->txt("copy_grade_level"), "#")->withOnClick($modal->getShowSignal()));
        }

        $table->executeAction();

        $this->setContent($this->renderer->render($table->getComponents()));
    }

    public function getColumnMapping(Item $item, ?array $additional_parameters) : array
    {
        return [
            "title" => $item->getGrade(),
            "points" => $item->getMinPoints(),
            "passed" => $item->isPassed(),
            "code" =>$item->getCode()
        ];
    }

    public function getColumns(?array $additional_parameters) : array
    {
        $tf = $this->uiFactory->table();

        $small_view = $this->smallView($additional_parameters);
        $sortable = !$small_view;

        return [
            "title" => $tf->column()->text($this->plugin->txt('grade_level'))->withIsSortable($sortable),
            "points" => $tf->column()->number($this->plugin->txt('min_points'))->withDecimals(2)->withIsSortable($sortable),
            "passed" => $tf->column()->boolean($this->plugin->txt('passed'), $this->lng->txt('yes'), $this->lng->txt('no'))->withIsSortable($sortable),
            "code" => $tf->column()->text($this->plugin->txt('grade_level_code'))->withIsSortable($sortable),
        ];
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters) : ?int
    {
        return -1;
    }

    public function getTableActions() : array
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
            fn (GradeItem $x) => true,
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
            fn (GradeItem $item) => $item->getGrade(),
            fn (GradeItem $x) => true,
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
            fn (GradeItem $x) => true,
            Action\Type::Single
        );
    }

    public function save(GradeItem $item, array $data)
    {
        if ($item->getId() === 0) {
            $grade_level = GradeLevel::model();
            $grade_level->setObjectId($this->object->getId());
        } else {
            $grade_level = $this->object_repo->getGradeLevelById($item->getId());
        }
        $grade_level->setGrade($data['grade'])
                    ->setMinPoints($data['points'])
                    ->setCode($data['code'])
                    ->setPassed($data['passed']);

        $this->object_repo->save($grade_level);
        $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
    }

    public function buildFields(GradeItem $item) : array
    {
        $factory = $this->uiFactory->input()->field();
        $fields = [];
        $fields['grade'] = $factory->text($this->plugin->txt("grade_level"))
                                   ->withRequired(true)
                                   ->withValue($item->getGrade());

        $fields['code'] = $factory->text($this->plugin->txt("grade_level_code"), $this->plugin->txt("grade_level_code_caption"))
                                  ->withRequired(false)
                                  ->withValue(!empty($item->getCode()) ? $item->getCode() : "");

        $fields['points'] = $this->localDI->getUIFactory()
                                          ->field()
                                          ->numeric($this->plugin->txt('min_points'), $this->plugin->txt("min_points_caption"))
                                          ->withStep(0.01)
                                          ->withRequired(true)
                                          ->withValue($item->getMinPoints());

        $fields['passed'] =$factory->checkbox($this->plugin->txt('passed'), $this->plugin->txt("passed_caption"))
                                   ->withRequired(true)
                                   ->withValue($item->isPassed());
        return $fields;
    }

    protected function tableItemFromData(GradeLevel $item): GradeItem
    {
        return new GradeItem($item->getId(), $item->getGrade(), $item->getMinPoints(), $item->isPassed(), $item->getCode());
    }

    public function getTableItem(int $id) : Item
    {
        return $this->tableItemFromData($this->object_repo->getGradeLevelById($id));
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = []) : Generator
    {
        if ($this->http->wrapper()->query()->has("xlas_copy_ref")) {
            $ref_id = $this->http->wrapper()->query()->retrieve("xlas_copy_ref", $this->refinery->kindlyTo()->int());
            $obj_id = \ilObject2::_lookupObjectId($ref_id);
            //check ref access_rights
            foreach ($this->object_repo->getGradeLevelsByObjectId($obj_id) as $object) {
                yield $this->tableItemFromData($object);
            }
            return;
        }

        if ($ids === [0]) {
            yield $this->tableItemFromData(GradeLevel::model());
            return;
        }
        foreach ($this->object_repo->getGradeLevelsByObjectId($this->object->getId()) as $object) {
            if(empty($ids) || in_array($object->getId(), $ids)) {
                yield $this->tableItemFromData($object);
            }
        }
    }

    protected function delete()
    {
        $this->checkAuthorizedCorrections();
        $ids = $this->confirmationIds();

        array_map(fn (int $x) => $this->getGradeLevel($x, true), $ids);//Permission check

        foreach ($ids as $id) {
            $this->object_repo->deleteGradeLevel($id);
        }
        $this->corrector_service->recalculateGradeLevel();
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("delete_grade_level_successful"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    protected function checkRecordInObject(?GradeLevel $record, bool $throw_permission_error = true): bool
    {
        if ($record !== null && $this->object->getId() === $record->getObjectId()) {
            return true;
        }

        if ($throw_permission_error) {
            $this->raisePermissionError();
        }
        return false;
    }

    protected function checkAuthorizedCorrections()
    {
        if ($this->corrector_service->authorizedCorrectionsExists()) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("grade_level_cannot_edit_used"), true);
            $this->ctrl->clearParameters($this);
            $this->ctrl->redirect($this);
        }
    }

    protected function getGradeLevel(int $id, bool $throw_permission_error = true): ?GradeLevel
    {
        $record = $this->object_repo->getGradeLevelById($id);
        if ($throw_permission_error) {
            $this->checkRecordInObject($record, true);
        }
        return $record;
    }

    protected function getGradeLevelId(): ?int
    {
        if (isset($_GET["grade_level"])) {
            return (int) $_GET["grade_level"];
        } else {
            return null;
        }
    }

    protected function getCopyGradeLevelModal(
        ?int $start_ref_id = null,
        ?int $current_ref_id = null,
        bool $is_subtree = false,
        ?ReplaceSignal $replace_signal = null
    ): RoundTrip {
        $here = $this->object->getRefId();
        $current_ref_id = $current_ref_id ?? $here;
        $tree = $this->localDI->getUIFactory()->tree()->repository(
            $start_ref_id,
            $current_ref_id,
            $is_subtree
        );
        $tree->setVisibleTypes(array_merge(['xlas'], $tree->getRepoContainerTypes()));
        $tree->setClickableTypes(['xlas']);

        $tree->setClickableCallback(function ($ref_id, $type) use ($here) {
            return $this->access->checkAccess('maintain_task', '', $ref_id, $type) && $ref_id !== $here;
        });

        $modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("copy_grade_level"), [
            $tree->getComponent()
        ]);
        if ($replace_signal === null) {
            $replace_signal = $modal->getReplaceSignal();
        }

        $tree->setExpandCallback(function ($ref_id) use ($replace_signal) {
            $this->ctrl->setParameter($this, "xlas_start_ref", $ref_id);
            $this->ctrl->setParameter($this, "xlas_return_signal", $replace_signal);
            return $this->ctrl->getLinkTarget($this, "copyGradeLevelModalTree", null, true);
        });

        $tree->setOnclickCallback(function ($ref_id) use ($replace_signal) {
            $this->ctrl->setParameter($this, "xlas_copy_ref", $ref_id);
            $this->ctrl->setParameter($this, "xlas_return_signal", $replace_signal);
            return $this->ctrl->getLinkTarget($this, "copyGradeLevelModalAsync", null, true);
        });

        $tree->setOnclickSignal($replace_signal);

        return $modal;
    }


    protected function copyGradeLevelModalTree()
    {
        $request_wrapper = $this->http->wrapper()->query();
        $start_ref_id = null;
        $current_ref_id = null;
        if ($request_wrapper->has('xlas_start_ref')) {
            $start_ref_id = $request_wrapper->retrieve('xlas_start_ref', $this->refinery->kindlyTo()->int());
        }
        if ($request_wrapper->has('xlas_current_ref')) {
            $current_ref_id = $request_wrapper->retrieve('xlas_current_ref', $this->refinery->kindlyTo()->int());
        }
        $replace_signal = null;
        if ($request_wrapper->has('xlas_return_signal')) {
            $replace_signal_str = $request_wrapper->retrieve('xlas_return_signal', $this->refinery->kindlyTo()->string());
            $replace_signal = new ReplaceSignal($replace_signal_str);
        }

        $modal = $this->getCopyGradeLevelModal($start_ref_id, $current_ref_id, true, $replace_signal);

        $this->http->saveResponse($this->http->response()->withBody(
            Streams::ofString($this->renderer->renderAsync([$modal->getContent()]))
        ));
        $this->http->sendResponse();
        $this->http->close();
    }



    protected function buildGradeLevelTable(array $grade_levels, string $title = "", bool $small_view = true): \ILIAS\UI\Component\Table\Data
    {
        $tf = $this->uiFactory->table();

        $data_retrieval = new class($grade_levels, $small_view) implements DataRetrieval {
            /**
             * @var GradeLevel[]
             */
            protected array $records;
            protected bool $small_view;

            public function __construct(array $grade_levels, bool $small_view)
            {
                $this->records = $grade_levels;
                $this->small_view = $small_view;
            }

            public function getRows(
                DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                ?array $filter_data,
                ?array $additional_parameters
            ): \Generator {
                foreach ($this->records as $idx => $record) {
                    $row_id = $record->getId();
                    $data = [
                        "title" => $record->getGrade(),
                        "points" => $record->getMinPoints(),
                        "passed" => $record->isPassed(),
                        "code" =>$record->getCode()
                    ];

                    yield $row_builder->buildDataRow($row_id, $data);
                }
            }

            public function getTotalRowCount(
                ?array $filter_data,
                ?array $additional_parameters
            ): ?int {
                return $this->small_view ? -1 : count($this->records);
            }
        };

        $sortable = !$small_view;

        $table = $tf->data(
            $title,
            [
                "title" => $tf->column()->text($this->plugin->txt('grade_level'))->withIsSortable($sortable),
                "points" => $tf->column()->number($this->plugin->txt('min_points'))->withIsSortable($sortable),
                "passed" => $tf->column()->boolean($this->plugin->txt('passed'), $this->lng->txt('yes'), $this->lng->txt('no'))->withIsSortable($sortable),
                "code" => $tf->column()->text($this->plugin->txt('grade_level_code'))->withIsSortable($sortable),
            ],
            $data_retrieval
        )->withRequest($this->request)->withFilter(null);
        return $table;
    }

    protected function copyGradeLevelModalAsync()
    {
        global $DIC;

        if ($this->corrector_service->authorizedCorrectionsExists()) {
            exit();
        }

        $query = $DIC->http()->wrapper()->query();

        if ($query->has("xlas_return_signal")) {
            $replace_signal_str = $query->retrieve("xlas_return_signal", $this->refinery->kindlyTo()->string());

        } else {
            throw new \ilException("Missing xlas_return_signal query parameter.");
        }

        $replace_signal = new ReplaceSignal($replace_signal_str);

        if ($query->has("xlas_copy_ref")) {
            $ref_id = $query->retrieve("xlas_copy_ref", $this->refinery->kindlyTo()->int());
            $obj_id = \ilObject2::_lookupObjectId($ref_id);
            $this->ctrl->clearParameterByClass(get_class($this), "xlas_copy_ref");

            $grade_levels = $this->object_repo->getGradeLevelsByObjectId($obj_id);
            $title = $this->plugin->txt("grade_levels") . ": " . \ilObject2::_lookupTitle($obj_id);

            $this->ctrl->saveParameter($this, "xlas_return_signal");
            $this->ctrl->setParameter($this, "xlas_reload_ref", $ref_id);
            $reload = $this->ctrl->getLinkTarget($this, "copyGradeLevelModalAsync", null, true);

            $this->ctrl->clearParameterByClass(get_class($this), "xlas_return_signal");
            $this->ctrl->setParameter($this, "xlas_copy_ref", $ref_id);
            $copy = $this->ctrl->getLinkTarget($this, "copyGradeLevel");

            $message = $this->uiFactory->messageBox()->info($this->plugin->txt('copy_grade_level_info'));

            $modal = $this->uiFactory->modal()->roundtrip(
                $this->plugin->txt('copy_grade_level'),
                [$message, $this->buildGradeLevelTable($grade_levels, $title)]
            )->withActionButtons([
                $this->uiFactory->button()->primary($this->lng->txt('copy'), $copy),
                $this->uiFactory->button()->standard($this->lng->txt('back'), "#")->withOnClick($replace_signal->withAsyncRenderUrl($reload))
            ]);
        } else {
            // this should expand the tree up to the selected node
            $ref_id = null;
            if ($query->has("xlas_reload_ref")) {
                $ref_id = $query->retrieve("xlas_reload_ref", $this->refinery->kindlyTo()->int());
            }
            $modal = $this->getCopyGradeLevelModal(null, $ref_id, false, $replace_signal);
        }

        echo($this->renderer->renderAsync($modal));
        exit();
    }

    protected function copyGradeLevel()
    {
        global $DIC;

        if ($this->corrector_service->authorizedCorrectionsExists()) {
            exit();
        }

        $query = $DIC->http()->wrapper()->query();

        if ($query->has("xlas_copy_ref")) {
            $ref_id = $query->retrieve("xlas_copy_ref", $this->refinery->kindlyTo()->int());
            $new_grade_levels = $this->object_repo->getGradeLevelsByObjectId(\ilObject2::_lookupObjectId($ref_id));
            $this->object_repo->deleteGradeLevelByObjectId($this->object->getId());

            foreach ($new_grade_levels as $grade_level) {
                $new_grade_level = clone $grade_level;
                $new_grade_level->setObjectId($this->object->getId());
                $new_grade_level->setId(0);
                $this->object_repo->save($new_grade_level);
            }
            $this->corrector_service->recalculateGradeLevel();
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt('copy_grade_level_successful'), true);
            $this->ctrl->redirect($this, "showItems");

        } else {
            throw new \ilException("Missing xlas_return_signal query parameter.");
        }
    }
}
