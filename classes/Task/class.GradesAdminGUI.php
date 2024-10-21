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
use ILIAS\Plugin\LongEssayAssessment\UI\CopyLongEssayAssessmentExplorer;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use ILIAS\UI\Implementation\Component\Modal\RoundTrip;
use JetBrains\PhpStorm\NoReturn;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\GradesAdminGUI: ilObjLongEssayAssessmentGUI
 */
class GradesAdminGUI extends BaseGUI
{
    private \ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper $query;
    private URLBuilder $url_builder;
    private URLBuilderToken $action_parameter_token;
    private URLBuilderToken $row_id_token;
    protected TaskRepository $task_repo;
    protected ObjectRepository $object_repo;
    protected CorrectorAdminService $corrector_service;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->corrector_service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->object_repo = $this->localDI->getObjectRepo();
        $this->task_repo = $this->localDI->getTaskRepo();
        $this->query = $this->dic->http()->wrapper()->query();

        $df = new \ILIAS\Data\Factory();
        $here_uri = $df->uri($this->request->getUri()->__toString());
        $url_builder = new URLBuilder($here_uri);
        $query_params_namespace = ["xlas", "actions"];

        list($this->url_builder, $this->action_parameter_token, $this->row_id_token) =
            $url_builder->acquireParameters(
                $query_params_namespace,
                "table_action",
                "grade_level"
            );

    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showItems');

        if ($this->query->has($this->action_parameter_token->getName())) {
            switch($this->query->retrieve($this->action_parameter_token->getName(), $this->refinery->to()->string())) {
                case "edit":
                    $this->editAsync();
                    break;
                case "delete":
                    $this->deleteAsync();
                    break;
            }
            return;
        }

        switch ($cmd) {
            case 'updateItem':
            case 'showItems':
            case "editItem":
            case 'deleteItem':
            case 'copyGradeLevelModalAsync':
            case 'copyGradeLevel':
            case 'deleteAsync':
            case 'editAsync':
                $this->$cmd();
                break;
            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Get the Table Data
     */
    protected function getItemData()
    {
        $records = $this->object_repo->getGradeLevelsByObjectId($this->object->getId());
        $item_data = [];

        foreach ($records as $record) {

            $important = [
                $this->plugin->txt('min_points').":" => $record->getMinPoints(),
                $this->plugin->txt('passed').":" => $record->isPassed() ? $this->lng->txt('yes') : $this->lng->txt('no')
            ];

            if ($record->getCode() !== null && $record->getCode() !== "") {
                $important[$this->plugin->txt('grade_level_code')] = $record->getCode();
            }

            $item_data[] = [
                'id' => $record->getId(),
                'headline' => $record->getGrade(),
                'subheadline' => '',
                'important' => $important,
            ];
        }

        return $item_data;
    }

    /**
     * Show the items
     */
    protected function showItems()
    {
        $item_data = $this->getItemData();

        $can_delete = true;
        $settings = $this->task_repo->getTaskSettingsById($this->object->getId());
        $authorized = $this->corrector_service->authorizedCorrectionsExists();
        $modals = [];

        if (!$authorized) {
            $modals[] = $modal = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl(
                $this->ctrl->getLinkTarget($this, "editAsync", null, true)
            );

            $this->toolbar->addComponent($this->uiFactory->button()->primary(
                $this->plugin->txt('add_grade_level'),
                ""
            )->withOnClick($modal->getShowSignal()));

            $modals[] = $modal = $this->getCopyGradeLevelModal();
            $this->toolbar->addComponent($this->uiFactory->button()->standard("Notenstufen kopieren", "#")->withOnClick($modal->getShowSignal()));
        }

        if ($settings->getCorrectionStart() !== null) {
            $correction_start = new \ilDateTime($settings->getCorrectionStart(), IL_CAL_DATETIME);
            $today = new \ilDateTime(time(), IL_CAL_UNIX);
            $can_delete = !\ilDate::_after($today, $correction_start);
        }

        if ($authorized) {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt("grade_level_cannot_edit_used_info"));
        } elseif (empty($item_data)) {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt("grade_levels_empty_notice"));
        }

        $actions = [
            'edit' => $this->uiFactory->table()->action()->single(
                $this->lng->txt('edit'),
                $this->url_builder->withParameter($this->action_parameter_token, "edit"),
                $this->row_id_token
            )->withAsync(),
            'delete' =>
                $this->uiFactory->table()->action()->standard( //in both
                    $this->lng->txt('delete'),
                    $this->url_builder->withParameter($this->action_parameter_token, "delete"),
                    $this->row_id_token
                )->withAsync()
        ];

        $grade_levels = $this->object_repo->getGradeLevelsByObjectId($this->object->getId());
        $table = $this->buildGradeLevelTable($grade_levels, $this->plugin->txt('grade_levels'), false)
                      ->withActions($actions);

        $this->tpl->setContent($this->renderer->render(array_merge([$table], $modals)));
    }

    protected function deleteAsync()
    {
        $ids = $this->query->retrieve($this->row_id_token->getName(), $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()));
        $items = [];
        foreach ($ids as $id) {
            $grade_level = $this->object_repo->getGradeLevelById($id);

            if($this->checkRecordInObject($grade_level, false)) {
                $items[] = $this->uiFactory->modal()->interruptiveItem()->standard(
                    $id,
                    $grade_level->getGrade() . (!empty($grade_level->getCode()) ? (" (" . $grade_level->getCode() . ")") : "")
                );
            }
        }

        if($this->corrector_service->authorizedCorrectionsExists() || empty($items)) {
            exit();
        }

        echo($this->renderer->renderAsync([
            $this->uiFactory->modal()->interruptive(
                $this->plugin->txt("delete_grade_level"),
                $this->plugin->txt("delete_grade_level_confirmation"),
                $this->ctrl->getFormAction($this, "delete", null, true)
            )->withAffectedItems($items)
            ]));

        echo("");
        exit();
    }

    protected function delete()
    {
        $this->checkAuthorizedCorrections();
        $ids = $this->query->retrieve("interruptive_items", $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()));

        array_map(fn (int $x) => $this->getGradeLevel($x, true), $ids);//Permission check

        foreach ($ids as $id) {
            $this->object_repo->deleteGradeLevel($id);
        }
        $this->corrector_service->recalculateGradeLevel();
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("delete_grade_level_successful"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    protected function editAsync()
    {
        if($this->corrector_service->authorizedCorrectionsExists()) {
            exit();
        }

        $id = $this->getGradeLevelId(); // Try to initialize ID from query

        if($this->query->has($this->row_id_token->getName())) { // Overwrite $id by table action
            $ids = $this->query->retrieve($this->row_id_token->getName(), $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()));
            $id = array_pop($ids);
        }

        if ($id === null) {
            $title = $this->plugin->txt('add_grade_level');
            $grade_level = GradeLevel::model();
            $grade_level->setObjectId($this->object->getId());
        } else {
            $title = $this->plugin->txt('edit_grade_level');
            $grade_level = $this->object_repo->getGradeLevelById($id);
            $this->ctrl->setParameter($this, "grade_level", $id);
        }

        $factory = $this->uiFactory->input()->field();
        $link = $this->ctrl->getFormAction($this, 'editAsync', null, true);
        $fields = [];
        $fields['grade'] = $factory->text($this->plugin->txt("grade_level"))
                                   ->withRequired(true)
                                   ->withValue($grade_level->getGrade());

        $fields['code'] = $factory->text($this->plugin->txt("grade_level_code"), $this->plugin->txt("grade_level_code_caption"))
                                  ->withRequired(false)
                                  ->withValue(!empty($grade_level->getCode()) ? $grade_level->getCode() : "");

        $fields['points'] = $this->localDI->getUIFactory()
                                          ->field()
                                          ->numeric($this->plugin->txt('min_points'), $this->plugin->txt("min_points_caption"))
                                          ->withStep(0.01)
                                          ->withRequired(true)
                                          ->withValue($grade_level->getMinPoints());

        $fields['passed'] =$factory->checkbox($this->plugin->txt('passed'), $this->plugin->txt("passed_caption"))
                                   ->withRequired(true)
                                   ->withValue($grade_level->isPassed());

        $form = $this->localDI->getUIFactory()->field()->asyncForm($link, $fields);

        if($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if(!empty($data = $form->getData())) {
                $grade_level->setGrade($data['grade']);
                $grade_level->setCode($data['code']);
                $grade_level->setMinPoints($data['points']);
                $grade_level->setPassed($data['passed']);

                $this->object_repo->save($grade_level);

                $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
                exit();
            } else {
                echo($this->renderer->render($form));
                exit();
            }
        }

        /**
         * @var RoundTrip $modal
         */
        $modal = $this->uiFactory->modal()->roundtrip($title, [$form])->withActionButtons([
                $this->uiFactory->button()->primary($this->lng->txt('submit'), "")->withOnClick($form->getSubmitAsyncSignal())
            ]);

        echo($this->renderer->renderAsync([
            $modal
        ]));
        exit();
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

    protected function getCopyGradeLevelModal(?ReplaceSignal $replace_signal = null)
    {
        $explorer = new CopyLongEssayAssessmentExplorer($this, "showItems", $this->object);
        $tree = $explorer->getTreeComponent();
        $modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("copy_grade_level"), $tree);

        if ($replace_signal === null) {
            $replace_signal = $modal->getReplaceSignal();
        }

        $explorer->setOnclick($replace_signal, function ($record) use ($replace_signal) {
            $this->ctrl->setParameter($this, "xlas_copy_ref", $record["ref_id"]);
            $this->ctrl->setParameter($this, "xlas_return_signal", $replace_signal);
            return $this->ctrl->getLinkTarget($this, "copyGradeLevelModalAsync", null, true);
        });
        return $modal;
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
                "points" => $tf->column()->number($this->plugin->txt('min_points'))->withDecimals(2)->withIsSortable($sortable),
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

        if($this->corrector_service->authorizedCorrectionsExists()) {
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
            $items = [];
            $grade_levels = $this->object_repo->getGradeLevelsByObjectId($obj_id);
            $title = $this->plugin->txt("grade_levels") . ": " . \ilObject2::_lookupTitle($obj_id);
            $this->ctrl->saveParameter($this, "xlas_return_signal");
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
            $modal = $this->getCopyGradeLevelModal($replace_signal);
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
