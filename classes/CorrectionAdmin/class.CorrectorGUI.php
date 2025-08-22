<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use Generator;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\SmallView;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\UI\Component\Listing\Unordered;
use ILIAS\UI\Component\Component;
use ILIAS\Plugin\LongEssayAssessment\UI\Tree\RepositorySelectModal;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory as TableFactory;
use Edutiek\AssessmentService\Assessment\Corrector\FullService as CorrectorService;
use Edutiek\AssessmentService\Task\CorrectorAssignments\FullService as AssignmentsService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use Edutiek\AssessmentService\Assessment\Data\Corrector;
use Edutiek\AssessmentService\Task\Data\CorrectorAssignment;
use Edutiek\AssessmentService\Task\AssessmentStatus\FullService as AssessmentStatusService;
use Edutiek\AssessmentService\Task\Format\FullService as TaskFormatService;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use ILIAS\Plugin\LongEssayAssessment\System\Context\Service as ContextService;
use Edutiek\AssessmentService\System\Data\UserData;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorGUI: ilRepositorySearchGUI
 */
class CorrectorGUI extends BaseGUI implements DataTableParent
{
    use SmallView, ConfirmationIds;

    private ?int $required_correctors = null;
    private \ilTree $tree;
    private CorrectionSettings $correction_settings;
    private TableFactory $table_factory;
    private CorrectorService $corrector_service;
    private AssignmentsService $assignments_service;
    private UserService $user_service;
    private AssessmentStatusService $assessment_status_service;
    private TaskFormatService $task_format;
    private WriterService $writer_service;
    private ContextService $context_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->tree = $this->dic->repositoryTree();
        $this->table_factory = $this->plugin_ui_factory->table();
        $this->correction_settings = $this->assessment_api->correctionSettings()->get();
        $this->corrector_service = $this->assessment_api->corrector();
        $this->assignments_service = $this->task_api->correctorAssignments();
        $this->user_service = $this->system_api->user();
        $this->assessment_status_service = $this->task_api->assessmentStatus();
        $this->task_format = $this->task_api->format();
        $this->writer_service = $this->assessment_api->writer();
        $this->context_service = $this->plugin->dic()->context($this->object->getRefId());
    }

    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            case 'ilrepositorysearchgui':
                $this->tabs->activateSubTab('tab_corrector_list');
                $rep_search = new \ilRepositorySearchGUI();
                $rep_search->addUserAccessFilterCallable([$this, 'addCorrectorsFilter']);
                $rep_search->setCallback($this, "addCorrectorsCallback");
                $this->ctrl->setReturn($this, 'showItems');
                $ret = $this->ctrl->forwardCommand($rep_search);
                break;
            default:
                $cmd = $this->ctrl->getCmd('showStartPage');
                switch ($cmd) {
                    case 'showItems':
                    case 'remove':
                    case 'addAllCourseTutors':
                    case 'mailToCorrectorsAsync':
                    case 'copyCorrectors':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }
    }

    public function showItems()
    {
        $modal = [];
        $table = $this->table_factory->dataTable("correctors", $this);
        $table->setTitle($this->plugin->txt("correctors"));
        $table->executeAction();

        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));

        \ilRepositorySearchGUI::fillAutoCompleteToolbar(
            $this,
            $this->toolbar,
            array(
                'auto_complete_name' => $this->lng->txt('user'),
                'submit_name' => $this->lng->txt('add'),
                'add_search' => true,
                'add_from_container' => $this->object->getRefId()
            )
        );


        // add all course tutors
        if ($this->context_service->isInCourse()) {
            $modal[] = $add_tutors_modal = $this->ui_factory->modal()->interruptive('', '', '')
                                                ->withAsyncRenderUrl($this->ctrl->getLinkTarget($this, 'addAllCourseTutors'));
            $button = $this->ui_factory->button()->standard($this->plugin->txt("add_all_course_tutors"), '')
                                      ->withOnClick($add_tutors_modal->getShowSignal());
            $this->toolbar->addComponent($button);
        }

        $select = $this->buildRepositorySelect();
        list($btn, $modal_copy) = $select->getToolbarComponents($this->plugin->txt("copy_from_xlas"));
        $this->toolbar->addComponent($btn);
        $modal[] = $modal_copy;

            // spacer
        $this->toolbar->addSeparator();

        // mail to correctors
        $modal[] = $modal_mail = $this->ui_factory->modal()->roundtrip('', [])
                                 ->withAsyncRenderUrl($this->ctrl->getFormAction($this, 'mailToCorrectorsAsync'));
        $button = $this->ui_factory->button()->standard($this->plugin->txt("mail_to_correctors"), '')
                                  ->withOnClick($modal_mail->getShowSignal());
        $this->toolbar->addComponent($button);

        $this->tpl->setContent($this->renderer->render(array_merge($modal, $table->getComponents())));
    }

    public function getRequiredCorrectors() : int
    {
        return $this->correction_settings->getRequiredCorrectors();
    }

    public function getColumnMapping(Item $item, ?array $additional_parameters) : array
    {
        /**
         * @var CorrectorItem $item
         */

        $columns =  [
            "name" => $item->getName(),
            "login" => $item->getLogin(),
            "first" => $item->getFirst(),
            ];
        if($this->getRequiredCorrectors() > 1) {
            $columns["second"] = $item->getSecond();
        }
        $columns["not_started"] = $item->getNotStarted();
        $columns["open"] = $item->getOpen();
        $columns["authorized"] = $item->getAuthorized();

        return $columns;
    }

    public function getColumns(?array $additional_parameters) : array
    {
        $tf = $this->ui_factory->table();

        $small_view = $this->smallView($additional_parameters);
        $sortable = !$small_view;

        $columns = ["name" => $tf->column()->text($this->lng->txt('name'))->withIsSortable($sortable),
                    "login" => $tf->column()->text($this->lng->txt('login'))->withIsSortable($sortable)];

        if($this->getRequiredCorrectors() == 1) {
            $columns["first"] = $tf->column()->text($this->plugin->txt('corrector_single_assignments'))->withIsSortable($sortable);
        } else {
            $columns["first"] = $tf->column()->text($this->plugin->txt('corrector_first_assignments'))->withIsSortable($sortable);
            $columns["second"] = $tf->column()->text($this->plugin->txt('corrector_second_assignments'))->withIsSortable($sortable);
        }
        $columns["not_started"] = $tf->column()->text($this->plugin->txt('grading_not_started'))->withIsSortable($sortable);
        $columns["open"] = $tf->column()->text($this->plugin->txt('grading_open'))->withIsSortable($sortable);
        $columns["authorized"] = $tf->column()->text($this->plugin->txt('grading_authorized'))->withIsSortable($sortable);
        return $columns;
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters) : ?int
    {
        return -1;
    }

    public function getTableActions() : array
    {
        return [$this->assignmentsAction(), $this->mailAction(), $this->removeAction()];
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null) : Generator
    {
        $corrector = $this->corrector_service->all();
        $corrector = array_filter($corrector, fn (Corrector $item) => $ids === null || in_array($item->getId(), $ids));
        $users = $this->user_service->getUsersByIds(array_map(fn (Corrector $item) => $item->getUserId(), $corrector));
        $correction_summaries = $this->assessment_status_service->allCorrectorCorrectionSummaries(array_map(fn(Corrector $x) => $x->getId(), $corrector));

        foreach($corrector as $item) {
            $user_data = $users[$item->getUserId()]??null;
            $correction_summary = $correction_summaries[$item->getId()] ?? null;

            yield new CorrectorItem(
                $item->getId(),
                $user_data?->getFullname(false)??"",
                $user_data?->getLogin()??"",
                $correction_summary?->getFirstCorrections()??0,
                $correction_summary?->getSecondCorrections()??0,
                $correction_summary?->getNotStarted()??0,
                $correction_summary?->getOpenCorrections()??0,
                $correction_summary?->getAuthorized()??0
            );
        }
    }

    public function getTableItem(int $id) : Item
    {
        return new Item(0);
    }

    private function removeAction() : Action\Confirmation
    {
        return $this->table_factory->action()->confirmation(
            "remove",
            $this->lng->txt("remove"),
            $this->plugin->txt("remove_corrector"),
            $this->plugin->txt("remove_corrector_confirmation"),
            $this->ctrl->getFormAction($this, "remove"),
            fn (CorrectorItem $item) => $item->getName() . "[" . $item->getLogin() . "]",
            fn (CorrectorItem $item) => ($item->getFirst() + $item->getSecond()) === 0,
            Action\Type::Standard
        );
    }

    private function mailAction() : Action\Direct
    {
        return $this->table_factory->action()->direct(
            "mail",
            $this->plugin->txt("write_mail"),
            fn (array $items) => $this->openMailForm(array_map(fn (CorrectorItem $item) => $item->getLogin(), $items), "showItems"),
            fn (CorrectorItem $item) => true,
            Action\Type::Standard
        );
    }

    private function assignmentsAction() : Action\Modal
    {
        return $this->table_factory->action()->modal(
            "assignments",
            $this->plugin->txt("corrector_show_assignments"),
            [$this, "assignmentsModal"],
            fn (CorrectorItem $item) => true,
            Action\Type::Single
        );
    }

    public function remove()
    {
        $ids = $this->confirmationIds();

        if(empty($ids)) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_corrector'), true);
            $this->ctrl->redirect($this, "showItems");
        }

        foreach($ids as $id) {
            $ass = $this->assignments_service->allByCorrectorId($id);
            if(count($ass) > 0) {
                $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('remove_writer_pending_assignments'), true);
                $this->ctrl->redirect($this, "showItems");
            }
        }

        foreach($ids as $id) {
            $corrector = $this->corrector_service->oneById($id);
            $this->corrector_service->remove($corrector);
        }

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("remove_corrector_success"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    public function assignmentsModal(CorrectorItem $item) : RoundTrip
    {
        $assignments = $this->assignments_service->allByCorrectorId($item->getId());
        $writer_ids = array_map(fn (CorrectorAssignment $ass) => $ass->getWriterId(), $assignments);
        $writers = [];
        $summaries = [];

        foreach($this->writer_service->all() as $writer) {
            if(in_array($writer->getId(), $writer_ids))
            $writers[$writer->getId()] = $writer;
        }


        foreach (array_unique(array_map(fn(CorrectorAssignment $ca) => $ca->getTaskId(), $assignments)) as $task_id) {
            foreach($this->task_api->summary($task_id)->all() as $summary) {
                $summaries[$summary->getWriterId()][$task_id] = $summary;
            }
        }

        $users = $this->user_service->getUsersByIds(array_map(fn (Writer $writer) => $writer->getUserId(), $writers));

        $first = [];
        $second = [];
        /**
         * @var CorrectorAssignment $assignment
         */
        foreach($assignments as $assignment) {
            $writer = $writers[$assignment->getWriterId()];
            $summary = $summaries[$assignment->getWriterId()][$assignment->getTaskId()] ?? null;
            $name = $users[$writer->getUserId()]?->getFullname(true) ?? " - ";
            $status = $this->task_format->correctionResult($summary, false, false);

            if($assignment->getPosition() === 0) {
                $first[$name] = $status;
            } else {
                $second[$name] = $status;
            }
        }

        if($this->getRequiredCorrectors() == 2) {
            $components = [
                $this->ui_factory->panel()->standard($this->plugin->txt('corrector_first_assignments'), [
                    !empty($first)
                        ? $this->ui_factory->listing()->characteristicValue()->text($first)
                        : $this->ui_factory->legacy($this->plugin->txt("corrector_no_assignments")),
                ]),
                $this->ui_factory->panel()->standard($this->plugin->txt('corrector_second_assignments'), [
                    !empty($second)
                        ? $this->ui_factory->listing()->characteristicValue()->text($second)
                        : $this->ui_factory->legacy($this->plugin->txt("corrector_no_assignments")),
                ]),
            ];
        } else {
            $components = [
                !empty($first)
                    ? $this->ui_factory->listing()->characteristicValue()->text($first)
                    : $this->ui_factory->legacy($this->plugin->txt("corrector_no_assignments"))
            ];
        }

        return $this->ui_factory->modal()->roundtrip($this->plugin->txt("corrector_assignments") . ": " . $item->getName(), $components);
    }

    /**
     * @return void
     */
    public function addAllCourseTutors()
    {
        $user_ids = $this->context_service->getCourseTutors();
        $user_data = $this->user_service->getUsersByIds($user_ids);

        // Confirmation
        if ($this->request->getMethod() != 'POST') {
            ;
            $items =[];
            foreach ($user_ids as $user_id) {
                $items[] = $this->ui_factory->modal()->interruptiveItem()->standard(
                    $user_id,
                    $user_data[$user_id]?->getFullname(true)??" - "
                );
            }
            $modal = $this->ui_factory->modal()->interruptive(
                $this->plugin->txt('add_all_course_tutors'),
                $this->plugin->txt('confirm_add_all_course_tutors'),
                $this->ctrl->getLinkTarget($this, 'addAllCourseTutors')
            )->withAffectedItems($items)
                                     ->withActionButtonLabel($this->lng->txt('add'));
            echo $this->renderer->render($modal);
            exit;
        }

        // Action
        foreach($user_ids as $id) {
            $this->corrector_service->getByUserId($id);
        }


        $this->tpl->setOnScreenMessage("success", $this->plugin->txt('tutors_added'), true);
        $this->ctrl->redirect($this, 'showItems');
    }

    /**
     * Callback for adding correctors by ilRepositorySearchGUI
     */
    public function addCorrectorsCallback(array $a_usr_ids, $a_type = null)
    {
        if (count($a_usr_ids) <= 0) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_corrector_id'), true);
            $this->ctrl->redirect($this, "showItems");
        }

        foreach($a_usr_ids as $id) {
            $this->corrector_service->getByUserId($id);
        }

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt('assign_corrector_success'), true);
        $this->ctrl->redirect($this, "showItems");
    }

    /**
     * Filter for searching correctors by lRepositorySearchGUI
     */
    public function addCorrectorsFilter($a_user_ids)
    {
        $user_ids = [];
        $writers = array_map(fn ($row) => $row->getUserId(), $this->corrector_service->all());

        foreach ($a_user_ids as $user_id) {
            if(!in_array((int)$user_id, $writers)) {
                $user_ids[] = $user_id;
            }
        }

        return $user_ids;
    }

    /**
     * Choose in a modal which correctors will be addressed
     * @see Services/Mail/README.md
     */
    private function mailToCorrectorsAsync()
    {
        $all = $this->corrector_service->all();
        $open = $this->assessment_status_service->getCorrectorsWithOpenAuthorizations();

        // Selection Modal
        if ($this->request->getMethod() != 'POST') {
            $fields= ['selection' => $this->ui_factory->input()->field()->radio($this->lng->txt('select'))
                                                     ->withOption('all', $this->plugin->txt('all_correctors') . ' (' . count($all) . ')')
                                                     ->withOption('open', $this->plugin->txt('correctors_with_open_corrections') . ' (' . count($open) . ')')
                                                     ->withValue('all')
            ];
            $form = $this->plugin_ui_factory->field()->blankForm(
                $this->ctrl->getFormAction($this, "mailToCorrectorsAsync"),
                $fields
            )->withAsyncOnEnter();
            $modal = $this->ui_factory->modal()->roundtrip(
                $this->plugin->txt('mail_to_correctors'),
                $form
            )->withActionButtons([
                $this->ui_factory->button()->primary($this->plugin->txt('write_mail'), "")
                                ->withOnClick($form->getSubmitSignal())
            ]);
            echo $this->renderer->renderAsync($modal);
            exit;
        }

        // Action
        $post = $this->request->getParsedBody();
        $correctors = [];
        switch($post['form/input_0'] ?? '') {
            case 'all':
                $correctors = $all;
                break;
            case 'open':
                $correctors = $open;
                break;
        }
        $user_ids = [];
        foreach ($correctors as $corrector) {
            $user_ids[] = $corrector->getUserId();
        }
        $logins = array_map(fn(UserData $u) => $u->getLogin(), $this->user_service->getUsersByIds($user_ids));
        $this->openMailForm($logins, 'showItems');
    }

    protected function buildRepositorySelect() : RepositorySelectModal
    {
        return $this->plugin_ui_factory->tree()->repositorySelect(
            $this->object->getRefId(),
            $this->plugin->txt("correctors"),
            [$this, "listCorrectors"],
            $this->ctrl->getLinkTarget($this, 'copyCorrectors', null, true)
        )->setPermission("maintain_correctors");
    }

    protected function copyCorrectors()
    {
        $select = $this->buildRepositorySelect();

        if($select->hasSelected()) {
            $id = $this->context_service->lookupAssIdFromReference($select->getSelectedId());
            $service = $this->plugin->dic()->assessment($id, $this->user->getId());
            $correctors = $service->corrector()->all();

            foreach($correctors as $corrector) {
                $this->corrector_service->getByUserId($corrector->getUserId());
            }

            $this->tpl->setOnScreenMessage("success", $this->plugin->txt('assign_corrector_success'), true);
            $this->ctrl->redirect($this, "showItems");
        } else {
            $select->showAsync();
        }
    }

    public function listCorrectors(int $ref_id) : Component
    {
        $id = $this->context_service->lookupAssIdFromReference($ref_id);
        $service = $this->plugin->dic()->assessment($id, $this->user->getId());
        $correctors = $service->corrector()->all();

        if(empty($correctors)) {
            return $this->ui_factory->legacy($this->plugin->txt("no_correctors"));
        }

        $user_data = $this->user_service->getUsersByIds(array_map(fn (Corrector $x) => $x->getUserId(), $correctors));

        return $this->ui_factory->listing()->unordered(
            array_map(fn (Corrector $x) => $user_data[$x->getUserId()]?->getFullname()??" - ", $correctors)
        );
    }
}
