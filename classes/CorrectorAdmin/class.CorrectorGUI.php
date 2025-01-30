<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use Generator;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\SmallView;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common\UserDataBaseHelper;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\UI\Component\Listing\Unordered;
use ILIAS\UI\Component\Component;
use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use ILIAS\Plugin\LongEssayAssessment\UI\Tree\RepositorySelectModal;

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

    private CorrectorRepository $corrector_repo;
    private WriterRepository $writer_repo;
    private EssayRepository $essay_repo;
    private UserDataBaseHelper $user_data;
    private TaskRepository $task_repo;
    private ?int $required_correctors = null;
    private \ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory $table_factory;
    private \ILIAS\Plugin\LongEssayAssessment\Data\DataService $data_service;
    private CorrectorAdminService $service;
    protected ArrayBasedRequestWrapper $post;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->corrector_repo = $this->localDI->getCorrectorRepo();
        $this->writer_repo = $this->localDI->getWriterRepo();
        $this->essay_repo = $this->localDI->getEssayRepo();
        $this->task_repo = $this->localDI->getTaskRepo();
        $this->user_data = $this->localDI->services()->common()->userDataHelper();
        $this->table_factory = $this->localDI->getTableFactory();
        $this->data_service = $this->localDI->getDataService($this->object->getId());
        $this->post = $this->http->wrapper()->post();
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
        if ($this->object_services->iliasContext()->isInCourse()) {
            $add_tutors_modal = $this->uiFactory->modal()->interruptive('', '', '')
                                                ->withAsyncRenderUrl($this->ctrl->getLinkTarget($this, 'addAllCourseTutors'));
            $button = $this->uiFactory->button()->standard($this->plugin->txt("add_all_course_tutors"), '')
                                      ->withOnClick($add_tutors_modal->getShowSignal());
            $this->toolbar->addComponent($button);
            $this->addModal($add_tutors_modal);
        }

        $select = $this->buildRepositorySelect();
        list($btn, $modal) = $select->getToolbarComponents($this->plugin->txt("copy_from_xlas"));
        $this->toolbar->addComponent($btn);
        $this->addModal($modal);

        // spacer
        $this->toolbar->addSeparator();

        // mail to correctors
        $modal = $this->uiFactory->modal()->roundtrip('', [])
                                 ->withAsyncRenderUrl($this->ctrl->getFormAction($this, 'mailToCorrectorsAsync'));
        $button = $this->uiFactory->button()->standard($this->plugin->txt("mail_to_correctors"), '')
                                  ->withOnClick($modal->getShowSignal());
        $this->addModal($modal);
        $this->toolbar->addComponent($button);

        $this->setContent($this->renderer->render($table->getComponents()));
    }

    public function getRequiredCorrectors() : int
    {
        if($this->required_correctors !== null) {
            return $this->required_correctors;
        }
        $correction = $this->task_repo->getCorrectionSettingsById($this->object->getId());
        return $this->required_correctors = $correction->getRequiredCorrectors();
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
        $columns["progress"] = function (bool $orderable) use ($item) {
            $all = $item->getFirst() + ($item->getSecond() ?? 0);
            $complete = $item->getAuthorized();
            $all = $all > 0 ? $all : 1;

            if($orderable) {
                return $complete / $all;
            }

            $progress = $this->uiFactory->chart()->progressMeter()->mini($all, $complete);
            return $this->renderer->render($progress);
        };

        return $columns;
    }

    public function getColumns(?array $additional_parameters) : array
    {
        $tf = $this->uiFactory->table();

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
        $columns["progress"] = $tf->column()->text($this->plugin->txt('progress'))->withIsSortable($sortable);
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
        $corrector = $this->corrector_repo->getCorrectorsByTaskId($this->object->getId());
        $corrector = array_filter($corrector, fn ($item) => $ids === null || in_array($item->getId(), $ids));
        $this->user_data->preload(array_map(fn (Corrector $item) => $item->getUserId(), $corrector));

        foreach($corrector as $item) {
            $assignments = $this->corrector_repo->getAssignmentsByCorrectorId($item->getId());
            $summaries = $this->essay_repo->getCorrectorSummariesByTaskIdAndCorrectorId($this->object->getId(), $item->getId());
            $first = count(array_filter($assignments, fn (CorrectorAssignment $ass) => $ass->getPosition() === 0));
            $second = count(array_filter($assignments, fn (CorrectorAssignment $ass) => $ass->getPosition() === 1));
            $not_started = count($assignments) - count($summaries);
            $authorized = count(array_filter($summaries, fn (CorrectorSummary $sum) => $sum->getCorrectionAuthorized() !== null));
            $open = count($summaries) - $authorized;

            yield new CorrectorItem(
                $item->getId(),
                $this->user_data->getFullname($item->getUserId()),
                $this->user_data->getLogin($item->getUserId()),
                $first,
                $second,
                $not_started,
                $open,
                $authorized
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
            $ass = $this->corrector_repo->getAssignmentsByCorrectorId($id);
            if(count($ass) > 0) {
                $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('remove_writer_pending_assignments'), true);
                $this->ctrl->redirect($this, "showItems");
            }
        }

        foreach($ids as $id) {
            $this->corrector_repo->deleteCorrector($id);
        }

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("remove_corrector_success"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    public function assignmentsModal(CorrectorItem $item) : RoundTrip
    {
        $assignments = $this->corrector_repo->getAssignmentsByCorrectorId($item->getId());
        $writer_ids = array_map(fn (CorrectorAssignment $ass) => $ass->getWriterId(), $assignments);
        $writers = [];

        foreach($this->writer_repo->getWritersByTaskId($this->object->getId(), $writer_ids) as $writer) {
            $writers[$writer->getId()] = $writer;
        }

        $summaries = [];
        foreach($this->essay_repo->getCorrectorSummariesByTaskIdAndCorrectorId($this->object->getId(), $item->getId()) as $summary) {
            $summaries[$summary->getEssayId()] = $summary;
        }

        $this->user_data->preload(array_map(fn (Writer $writer) => $writer->getUserId(), $writers));

        $first = [];
        $second = [];

        foreach($assignments as $assignment) {
            $writer = $writers[$assignment->getWriterId()];
            $essay = $this->essay_repo->getEssayByWriterIdAndTaskId($writer->getId(), $this->object->getId());
            $summary = $essay !== null ? $summaries[$essay->getId()] ?? null : null;

            $name = $this->user_data->getPresentation($writer->getUserId(), false, " - ");
            $status = $this->data_service->formatCorrectionResult($summary, false, false);

            if($assignment->getPosition() === 0) {
                $first[$name] = $status;
            } else {
                $second[$name] = $status;
            }
        }

        if($this->getRequiredCorrectors() == 2) {
            $components = [
                $this->uiFactory->panel()->standard($this->plugin->txt('corrector_first_assignments'), [
                    !empty($first)
                        ? $this->uiFactory->listing()->characteristicValue()->text($first)
                        : $this->uiFactory->legacy($this->plugin->txt("corrector_no_assignments")),
                ]),
                $this->uiFactory->panel()->standard($this->plugin->txt('corrector_second_assignments'), [
                    !empty($second)
                        ? $this->uiFactory->listing()->characteristicValue()->text($second)
                        : $this->uiFactory->legacy($this->plugin->txt("corrector_no_assignments")),
                ]),
            ];
        } else {
            $components = [
                !empty($first)
                    ? $this->uiFactory->listing()->characteristicValue()->text($first)
                    : $this->uiFactory->legacy($this->plugin->txt("corrector_no_assignments"))
            ];
        }

        return $this->uiFactory->modal()->roundtrip($this->plugin->txt("corrector_assignments") . ": " . $item->getName(), $components);
    }

    /**
     * @return void
     */
    public function addAllCourseTutors()
    {
        $user_ids = $this->object_services->iliasContext()->getCourseTutors();
        $this->common_services->userDataHelper()->preload($user_ids);
        // Confirmation
        if ($this->request->getMethod() != 'POST') {
            ;
            $items =[];
            foreach ($user_ids as $user_id) {
                $items[] = $this->uiFactory->modal()->interruptiveItem()->standard(
                    $user_id,
                    $this->common_services->userDataHelper()->getPresentation($user_id)
                );
            }
            $modal = $this->uiFactory->modal()->interruptive(
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
            $this->service->getOrCreateCorrectorFromUserId($id);
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
            $this->service->getOrCreateCorrectorFromUserId($id);
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
        $writers = array_map(fn ($row) => $row->getUserId(), $this->corrector_repo->getCorrectorsByTaskId($this->object->getId()));

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
        $all = $this->service->getCorrectors();
        $open = $this->service->getCorrectorsWithOpenAuthorizations();

        // Selection Modal
        if ($this->request->getMethod() != 'POST') {
            $fields= ['selection' => $this->uiFactory->input()->field()->radio($this->lng->txt('select'))
                                                     ->withOption('all', $this->plugin->txt('all_correctors') . ' (' . count($all) . ')')
                                                     ->withOption('open', $this->plugin->txt('correctors_with_open_corrections') . ' (' . count($open) . ')')
                                                     ->withValue('all')
            ];
            $form = $this->localDI->getUIFactory()->field()->blankForm(
                $this->ctrl->getFormAction($this, "mailToCorrectorsAsync"),
                $fields
            )->withAsyncOnEnter();
            $modal = $this->uiFactory->modal()->roundtrip(
                $this->plugin->txt('mail_to_correctors'),
                $form
            )->withActionButtons([
                $this->uiFactory->button()->primary($this->plugin->txt('write_mail'), "")
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
        $logins = $this->localDI->services()->common()->userDataHelper()->getLogins($user_ids);
        $this->openMailForm($logins, 'showItems');
    }

    protected function buildRepositorySelect() : RepositorySelectModal
    {
        return $this->localDI->getUIFactory()->tree()->repositorySelect(
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
            $id = \ilObject2::_lookupObjectId($select->getSelectedId());
            $correctors = $this->corrector_repo->getCorrectorsByTaskId($id);

            foreach($correctors as $corrector) {
                $this->service->getOrCreateCorrectorFromUserId($corrector->getUserId());
            }

            $this->tpl->setOnScreenMessage("success", $this->plugin->txt('assign_corrector_success'), true);
            $this->ctrl->redirect($this, "showItems");
        } else {
            $select->showAsync();
        }
    }

    public function listCorrectors(int $ref_id) : Component
    {
        $id = \ilObject2::_lookupObjectId($ref_id);
        $correctors = $this->corrector_repo->getCorrectorsByTaskId($id);

        if(empty($correctors)) {
            return $this->uiFactory->legacy($this->plugin->txt("no_correctors"));
        }

        $this->user_data->preload(array_map(fn (Corrector $x) => $x->getUserId(), $correctors));

        return $this->uiFactory->listing()->unordered(
            array_map(fn (Corrector $x) => $this->user_data->getPresentation($x->getUserId()), $correctors)
        );
    }
}
