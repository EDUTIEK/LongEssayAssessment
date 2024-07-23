<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use Edutiek\LongEssayAssessmentService\Corrector\Service;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorContext;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\CorrectorAdminListGUI;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\CorrectorListGUI;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\ilLongEssayAssessmentUploadTempFile;
use ILIAS\DI\Exceptions\Exception;
use ilFileDelivery;
use ilObjUser;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI: ilRepositorySearchGUI
 */
class CorrectorAdminGUI extends BaseGUI
{
    protected CorrectorAdminService $service;
    protected CorrectorAssignmentsService  $assignment_service;
    protected CorrectionSettings $settings;
    protected CorrectorRepository $corrector_repo;
    protected WriterRepository $writer_repo;
    protected EssayRepository $essay_repo;
    protected TaskRepository $task_repo;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->assignment_service = $this->localDI->getCorrectorAssignmentService($this->object->getId());
        $this->settings = $this->localDI->getTaskRepo()->getCorrectionSettingsById($this->object->getId());
        $this->corrector_repo = $this->localDI->getCorrectorRepo();
        $this->writer_repo = $this->localDI->getWriterRepo();
        $this->essay_repo = $this->localDI->getEssayRepo();
        $this->task_repo = $this->localDI->getTaskRepo();
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            case 'ilrepositorysearchgui':
                $this->tabs->activateSubTab('tab_corrector_list');
                $rep_search = new \ilRepositorySearchGUI();
                $rep_search->addUserAccessFilterCallable([$this, 'addCorrectorsFilter']);
                $rep_search->setCallback($this, "addCorrectorsCallback");
                $this->ctrl->setReturn($this, 'showCorrectors');
                $ret = $this->ctrl->forwardCommand($rep_search);
                break;
            default:
                $cmd = $this->ctrl->getCmd('showStartPage');
                if(in_array($cmd, ["remove", "change", "add", "ok", "confirm"])) { // Workaround to use fallback cmd for generic cmds from interruptive modals
                    $cmd = $this->request->getQueryParams()["fallbackCmd"] ?? $cmd;
                }
                switch ($cmd) {
                    case 'showStartPage':
                    case 'showCorrectors':
                    case 'confirmAssignWriters':
                    case 'addAllCourseTutors':
                    case 'mailToCorrectorsAsync':
                    case 'mailToSelectedAsync':
                    case 'mailToSingleCorrector':
                    case 'assignWriters':
                    case 'changeCorrector':
                    case 'removeCorrector':
                    case 'exportCorrections':
                    case 'exportResults':
                    case 'viewCorrections':
                    case 'stitchDecision':
                    case 'exportSteps':
                    case 'removeAuthorizations':
                    case 'editAssignmentsAsync':
                    case 'confirmRemoveAuthorizationsAsync':
                    case 'downloadWrittenPdf':
                    case 'downloadCorrectedPdf':
                    case 'downloadReportsPdf':
                    case 'correctorAssignmentSpreadsheetExport':
                    case 'correctorAssignmentSpreadsheetImport':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }
    }


    /**
     * Show the items
     */
    protected function showStartPage()
    {
        $authorized_essay_exists = false;
        $essays = $this->essay_repo->getEssaysByTaskId($this->object->getId());
        $stitches = [];
        foreach ($essays as $essay) {
            if($this->service->isStitchDecisionNeeded($essay)) {
                $stitches[] = $essay->getId();
            }
            if ($essay->getWritingAuthorized()) {
                $authorized_essay_exists = true;
            }
        }

        $writers = $this->writer_repo->getWritersByTaskId($this->object->getId());
        $correctors = $this->corrector_repo->getCorrectorsByTaskId($this->object->getId());
        
        if ($authorized_essay_exists) {
            if (empty($correctors)) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt('info_missing_correctors'), false);
            } elseif(!empty($this->service->countMissingCorrectors())) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt('info_missing_assignments'), false);
            }
        }

        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
        $assign_writers_action = $this->ctrl->getLinkTarget($this, "confirmAssignWriters");
        $export_corrections_action =  $this->ctrl->getLinkTarget($this, "exportCorrections");
        $export_results_action =  $this->ctrl->getLinkTarget($this, "exportResults");
        $stitch_decision_action =  $this->ctrl->getLinkTarget($this, "stitchDecision");
        $download_reports_action = $this->ctrl->getLinkTarget($this, "downloadReportsPdf");

        $button = \ilLinkButton::getInstance();
        $button->setUrl($assign_writers_action);
        $button->setCaption($this->plugin->txt("assign_writers"), false);
        $button->setPrimary(true);
        $this->toolbar->addButtonInstance($button);
        
        $btn_export = $this->uiFactory->button()->standard(
            $this->plugin->txt("assignment_excel_export"),
            $this->ctrl->getLinkTarget($this, "correctorAssignmentSpreadsheetExport")
        );
        $btn_import = $this->uiFactory->button()->standard(
            $this->plugin->txt("assignment_excel_import"),
            $this->ctrl->getLinkTarget($this, "correctorAssignmentSpreadsheetImport")
        );
        $btn_export_ass = $this->uiFactory->button()->toggle(
            $this->plugin->txt("assignment_excel_export_auth"),
            "#",
            "#",
            $this->spreadsheetAssignmentToggle()
        )->withAdditionalOnLoadCode(
            function ($id) {
                return "$('#{$id}').on( 'click', function() {  document.cookie = 'xlas_exass=' + ($( this ).hasClass('on') ? 'on' : 'off'); } );";
            }
        );

        $this->toolbar->addText($this->plugin->txt("assignment_excel"));
        $this->toolbar->addComponent($btn_import);
        $this->toolbar->addComponent($btn_export);
        /** @var \ILIAS\UI\Component\Component $btn_export_ass */
        $this->toolbar->addComponent($btn_export_ass);
        $this->toolbar->addSeparator();

        if ($this->settings->getRequiredCorrectors() > 1) {
            $button = \ilLinkButton::getInstance();
            $button->setUrl(empty($stitches) ? '#' : $stitch_decision_action);
            $button->setCaption($this->plugin->txt("do_stich_decision"), false);
            $button->setDisabled(empty($stitches));
            $this->toolbar->addButtonInstance($button);
            $this->toolbar->addSeparator();
        }
        
        $button = \ilLinkButton::getInstance();
        $button->setUrl($export_corrections_action);
        $button->setCaption($this->plugin->txt("export_corrections"), false);
        $this->toolbar->addButtonInstance($button);

        $button = \ilLinkButton::getInstance();
        $button->setUrl($export_results_action);
        $button->setCaption($this->plugin->txt("export_results"), false);
        $this->toolbar->addButtonInstance($button);

        if ($this->settings->getReportsEnabled()) {
            $button = \ilLinkButton::getInstance();
            $button->setUrl($download_reports_action);
            $button->setCaption($this->plugin->txt("download_correction_reports"), false);
            $this->toolbar->addButtonInstance($button);
        }

        $this->toolbar->addSeparator();

        $list_gui = new CorrectorAdminListGUI($this, "showStartPage", $this->plugin, $this->settings);
        $list_gui->setWriters($writers);
        $list_gui->setCorrectors($correctors);
        $list_gui->setEssays($essays);
        $list_gui->setAssignments($this->corrector_repo->getAssignmentsByTaskId($this->object->getId()));
        $list_gui->setCorrectionStatusStitches($stitches);
        $list_gui->setLocations($this->task_repo->getLocationsByTaskId($this->object->getId()));
        $list_gui->setSummaries($this->essay_repo->getCorrectorSummariesByTaskId($this->object->getId()));

        $this->tpl->setContent($list_gui->getContent());
    }

    protected function showCorrectors()
    {
        $components = [];
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

        // spacer
        $this->toolbar->addSeparator();

        // add all course tutors
        if ($this->object_services->iliasContext()->isInCourse()) {
            $modal = $this->uiFactory->modal()->interruptive('', '', '')
                                     ->withAsyncRenderUrl($this->ctrl->getLinkTarget($this, 'addAllCourseTutors'));
            $button = $this->uiFactory->button()->standard($this->plugin->txt("add_all_course_tutors"), '')
                                      ->withOnClick($modal->getShowSignal());
            $components[] = $modal;
            $this->toolbar->addComponent($button);
        }

        // mail to correctors
        $modal = $this->uiFactory->modal()->roundtrip('', [])
                                 ->withAsyncRenderUrl($this->ctrl->getFormAction($this, 'mailToCorrectorsAsync'));
        $button = $this->uiFactory->button()->standard($this->plugin->txt("mail_to_correctors"), '')
                                  ->withOnClick($modal->getShowSignal());
        $components[] = $modal;
        $this->toolbar->addComponent($button);

        $list_gui = new CorrectorListGUI($this, "showCorrectors", $this->plugin);
        $list_gui->setWriters($this->writer_repo->getWritersByTaskId($this->object->getId()));
        $list_gui->setCorrectors($this->corrector_repo->getCorrectorsByTaskId($this->object->getId()));
        $list_gui->setAssignments($this->corrector_repo->getAssignmentsByTaskId($this->object->getId()));

        $this->tpl->setContent($list_gui->getContent(). $this->renderer->render($components));
    }

    /**
     * @return void
     */
    public function addAllCourseTutors()
    {
        $user_ids = $this->object_services->iliasContext()->getCourseTutors();

        // Confirmation
        if ($this->request->getMethod() != 'POST') {
            ;
            $items =[];
            foreach ($user_ids as $user_id) {
                $items[] = $this->uiFactory->modal()->interruptiveItem(
                    $user_id,
                    \ilObjUser::_lookupFullname($user_id)
                );
            }
            $modal = $this->uiFactory->modal()->interruptive(
                $this->plugin->txt('add_all_course_tutors'),
                $this->plugin->txt('confirm_add_all_course_tutors'),
                $this->ctrl->getLinkTarget($this, 'addAllCourseTutors')
            )->withAffectedItems($items)
            ->withActionButtonLabel('add');
            echo $this->renderer->render($modal);
            exit;
        }

        // Action
        foreach($user_ids as $id) {
            $this->service->getOrCreateCorrectorFromUserId($id);
        }


        $this->tpl->setOnScreenMessage("success", $this->plugin->txt('tutors_added'), true);
        $this->ctrl->redirect($this, 'showCorrectors');
    }

    private function mailToSingleCorrector()
    {
        $corrector = $this->getCorrectorFromRequest();
        if (!empty($login = ilObjUser::_lookupLogin($corrector->getUserId()))) {
            $this->openMailForm([$login], 'showCorrectors');
        }
        $this->ctrl->redirect($this, 'showCorrectors');
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
                $this->ctrl->getFormAction($this, "mailToCorrectorsAsync"), $fields)->withAsyncOnEnter();
            $modal = $this->uiFactory->modal()->roundtrip(
                $this->plugin->txt('mail_to_correctors'), $form)->withActionButtons([
                    $this->uiFactory->button()->primary($this->plugin->txt('write_mail'), "")
                                              ->withOnClick($form->getSubmitSignal())
            ]);
            echo $this->renderer->renderAsync($modal);
            exit;
        }

        // Action
        $post = $this->request->getParsedBody();
        $correctors = [];
        switch($post['form_input_1'] ?? '') {
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
        $logins = $this->localDI->services()->common()->userHelper()->getLoginsByIds($user_ids);
        $this->openMailForm($logins, 'showCorrectors');
    }

    /**
     * Choose in a modal which correctors will be addressed
     * @see Services/Mail/README.md
     */
    private function mailToSelectedAsync()
    {
        $this->ctrl->saveParameter($this, 'writer_ids');

        // Selection Modal
        if ($this->request->getMethod() != 'POST') {
            $fields = [
                'writer' => $this->uiFactory->input()->field()->checkbox($this->plugin->txt('participant'))
            ];
            for ($i = 1; $i <= $this->settings->getRequiredCorrectors(); $i++) {
                $fields['corrector' . $i] =  $this->uiFactory->input()->field()->checkbox(
                    sprintf($this->plugin->txt('corrector_x'), $i));
            }

            $form = $this->localDI->getUIFactory()->field()->blankForm(
                $this->ctrl->getFormAction($this, "mailToSelectedAsync"), $fields)->withAsyncOnEnter();
            $modal = $this->uiFactory->modal()->roundtrip(
                $this->plugin->txt('mail_for_selected_essays'), $form)->withActionButtons([
                $this->uiFactory->button()->primary($this->plugin->txt('write_mail'), "")
                                ->withOnClick($form->getSubmitSignal())
            ]);
            echo $this->renderer->renderAsync($modal);
            exit;
        }

        // Action
        $post = $this->request->getParsedBody();
        $to_writer = false;
        $to_correctors = [];
        if (isset($post['form_input_1'])) {
            $to_writer = true;
        }
        for ($i = 1; $i <= $this->settings->getRequiredCorrectors(); $i++) {
            if (isset($post['form_input_' . ($i + 1)])) {
                $to_correctors[$i - 1] = true;
            }
        }

        $writer_ids = $this->getWriterIds();
        $assignments = $this->corrector_repo->getAssignmentsByTaskId($this->object->getId());
        $correctors = $this->corrector_repo->getCorrectorsByTaskId($this->object->getId());
        $writers = $this->writer_repo->getWritersByTaskId($this->object->getId());

        $user_ids = [];
        foreach($assignments as $assignment) {
            if (in_array($assignment->getWriterId(), $writer_ids)) {
                if ($to_writer
                    && !empty($writer = $writers[$assignment->getWriterId()])) {
                    $user_ids[] = $writer->getUserId();
                }
                if (isset($to_correctors[$assignment->getPosition()])
                    && !empty($corrector = $correctors[$assignment->getCorrectorId()])) {
                    $user_ids[] = $corrector->getUserId();
                }
            }
        }

        $logins = $this->localDI->services()->common()->userHelper()->getLoginsByIds(array_unique($user_ids));
        $this->openMailForm($logins, 'showStartPage');
    }


    /**
     * Callback for adding correctors by ilRepositorySearchGUI
     */
    public function addCorrectorsCallback(array $a_usr_ids, $a_type = null)
    {
        if (count($a_usr_ids) <= 0) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_corrector_id'), true);
            $this->ctrl->redirect($this, "showCorrectors");
        }

        foreach($a_usr_ids as $id) {
            $this->service->getOrCreateCorrectorFromUserId($id);
        }

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt('assign_corrector_success'), true);
        $this->ctrl->redirect($this, "showCorrectors");
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

    private function removeCorrector()
    {
        $corrector = $this->getCorrectorFromRequest();

        if($corrector === null || $corrector->getTaskId() !== $this->object->getId()) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('missing_corrector'), true);
            $this->ctrl->redirect($this, "showCorrectors");
        }
        $ass = $this->corrector_repo->getAssignmentsByCorrectorId($corrector->getId());

        if(count($ass) > 0) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('remove_writer_pending_assignments'), true);
            $this->ctrl->redirect($this, "showCorrectors");
        }

        $this->corrector_repo->deleteCorrector($corrector->getId());
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("remove_corrector_success"), true);
        $this->ctrl->redirect($this, "showCorrectors");
    }



    protected function confirmAssignWriters()
    {

        $missing = $this->service->countMissingCorrectors();
        if ($missing == 0) {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt('assign_not_needed'), true);
            $this->ctrl->redirect($this, 'showStartPage');
        }
        $available = $this->service->countAvailableCorrectors();
        if ($available == 0) {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt('assign_not_available'), true);
            $this->ctrl->redirect($this, 'showStartPage');
        }

        list($before, $writing, $after) = $this->localDI->getWriterAdminService($this->object->getId())->countPotentialAuthorizations();
        $warnings = [];
        if ($before) {
            $warnings[] = sprintf($this->plugin->txt('potential_authorizations_not_started'), $before);
        }
        if ($writing) {
            $warnings[] = sprintf($this->plugin->txt('potential_authorizations_writing'), $writing);
        }
        if ($after) {
            $warnings[] = sprintf($this->plugin->txt('potential_authorizations_after'), $after);
        }
        if ($warnings) {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt('warning_potential_later_assignments') . '<br>' . implode('<br>', $warnings), false);
        }


        $message =
            sprintf($this->plugin->txt('assign_missing_correctors'), $missing) . '<br />' .
            sprintf($this->plugin->txt('assign_available_correctors'), $available) . '<br />';


        switch ($this->service->getSettings()->getAssignMode()) {
            case CorrectionSettings::ASSIGN_MODE_RANDOM_EQUAL:
            default:
                $message .= $this->plugin->txt('assign_mode_random_equal_info') .  '<br />';
        }
        $message .= $this->plugin->txt('message_corrector_assignment_changeable');

        $gui = new \ilConfirmationGUI();
        $gui->setFormAction($this->ctrl->getFormAction($this));
        $gui->setHeaderText($message);
        $gui->setCancel($this->lng->txt('cancel'), 'showStartPage');
        $gui->setConfirm($this->plugin->txt('assign_writers'), 'assignWriters');

        $this->tpl->setContent($gui->getHTML());

    }

    protected function assignWriters()
    {
        $assigned = $this->service->assignMissingCorrectors();
        if ($assigned == 0) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("0_assigned_correctors"), true);
        } elseif ($assigned == 1) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("1_assigned_corrector"), true);
        } else {
            $this->tpl->setOnScreenMessage("success", sprintf($this->plugin->txt("n_assigned_correctors"), $assigned), true);
        }
        $this->ctrl->redirect($this, "showStartPage");
    }

    protected function viewCorrections()
    {
        $context = new CorrectorContext();
        $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());
        $context->setReview(true);

        $params = $this->request->getQueryParams();
        if (!empty($params['writer_id'])) {
            $context->selectWriterId((int) $params['writer_id']);
        }
        $service = new Service($context);
        $service->openFrontend();
    }

    protected function stitchDecision()
    {
        $context = new CorrectorContext();
        $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());
        $context->setStitchDecision(true);

        $params = $this->request->getQueryParams();
        if (!empty($params['writer_id'])) {
            $context->selectWriterId((int) $params['writer_id']);
        }
        $service = new Service($context);
        $service->openFrontend();
    }

    protected function removeAuthorizations()
    {
        $writer_ids = $this->getWriterIds();
        $valid = [];
        $invalid = [];

        foreach($writer_ids as $writer_id) {
            if(($writer = $this->localDI->getWriterRepo()->getWriterById($writer_id)) !== null) {
                if ($this->service->removeAuthorizations($writer)) {
                    $valid[] = $writer;
                } else {
                    $invalid[] = $writer;
                }
            }
        }
        if(count($invalid) > 0) {
            $names = [];
            foreach ($invalid as $writer) {
                $names[] = \ilObjUser::_lookupFullname($writer->getUserId()) . ' [' . $writer->getPseudonym() . ']';
            }
            $this->tpl->setOnScreenMessage("failure", sprintf($this->plugin->txt('remove_authorizations_for_failed'), implode(", ", $names)), true);
        }
        if(count($valid) > 0) {
            $names = [];
            foreach ($valid as $writer) {
                $names[] = \ilObjUser::_lookupFullname($writer->getUserId()) . ' [' . $writer->getPseudonym() . ']';
            }
            $this->tpl->setOnScreenMessage("success", sprintf($this->plugin->txt('remove_authorizations_for_done'), implode(", ", $names)), true);
        }

        $this->ctrl->clearParameters($this);
        $this->ctrl->redirect($this);
    }


    protected function exportCorrections()
    {
        $filename = ilFileDelivery::returnASCIIFilename($this->plugin->txt('export_corrections_file_prefix') .' ' .$this->object->getTitle()) . '.zip';
        ilFileDelivery::deliverFileAttached($this->service->createCorrectionsExport($this->object), $filename, 'application/zip', false);
    }

    protected function exportResults()
    {
        $filename = ilFileDelivery::returnASCIIFilename($this->plugin->txt('export_results_file_prefix') .' ' . $this->object->getTitle()) . '.csv';
        ilFileDelivery::deliverFileAttached($this->service->createResultsExport(), $filename, 'text/csv', false);
    }

    /**
     * Download a generated pdf from the written essay
     */
    protected function downloadWrittenPdf()
    {
        $params = $this->request->getQueryParams();
        $writer_id = (int) ($params['writer_id'] ?? 0);

        $service = $this->localDI->getWriterAdminService($this->object->getId());
        $repoWriter = $this->localDI->getWriterRepo()->getWriterById($writer_id);

        $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-writing.pdf';
        $this->common_services->fileHelper()->deliverData($service->getWritingAsPdf($this->object, $repoWriter), $filename, 'application/pdf');
    }


    /**
     * Download a generated pdf from the correction
     */
    protected function downloadCorrectedPdf()
    {
        $params = $this->request->getQueryParams();
        $writer_id = (int) ($params['writer_id'] ?? 0);

        $service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $repoWriter = $this->localDI->getWriterRepo()->getWriterById($writer_id);

        $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-correction.pdf';
        $this->common_services->fileHelper()->deliverData($service->getCorrectionAsPdf($this->object, $repoWriter), $filename, 'application/pdf');
    }

    /**
     * Download a generated pdf of the correction reports
     */
    protected function downloadReportsPdf()
    {
        if ($this->settings->getReportsEnabled()) {
            $service = $this->localDI->getCorrectorAdminService($this->object->getId());
            $filename = 'task' . $this->object->getId() . '-reports.pdf';
            $this->common_services->fileHelper()->deliverData($service->getCorrectionReportsAsPdf($this->object), $filename, 'application/pdf');
        }
    }


    private function exportSteps()
    {
        if (empty($repoWriter = $this->localDI->getWriterRepo()->getWriterById((int) $this->getWriterId()))) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("missing_writer_id"), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        $service = $this->localDI->getWriterAdminService($this->object->getId());
        $name = ilFileDelivery::returnASCIIFilename($this->object->getTitle() .'_' . \ilObjUser::_lookupFullname($repoWriter->getUserId()));
        $zipfile = $service->createWritingStepsExport($this->object, $repoWriter, $name);
        if (empty($zipfile)) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("content_not_available"), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        ilFileDelivery::deliverFileAttached($zipfile, $name . '.zip', 'application/zip', false);
    }

    private function getCorrectorFromRequest(): Corrector
    {
        $query = $this->request->getQueryParams();
        if((empty($id = $query["corrector_id"]) ?? null)) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("missing_corrector_id"), true);
            $this->ctrl->redirect($this, "showCorrectors");
        }
        $corrector = $this->corrector_repo->getCorrectorById((int) $id);

        if($corrector === null || $corrector->getTaskId() !== $this->object->getId()) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("missing_corrector"), true);
            $this->ctrl->redirect($this, "showCorrectors");
        }
        return $corrector;
    }

    private function getWriterId(): ?int
    {
        $query = $this->request->getQueryParams();
        if(isset($query["writer_id"])) {
            return (int) $query["writer_id"];
        }
        return null;
    }

    protected function getWriterIds(): array
    {
        $ids = [];
        $query_params = $this->request->getQueryParams();

        if(isset($query_params["writer_id"]) && $query_params["writer_id"] !== "") {
            $this->ctrl->saveParameter($this, "writer_id");
            $ids[] = (int) $query_params["writer_id"];
        } elseif (isset($query_params["writer_ids"])) {
            $this->ctrl->saveParameter($this, "writer_ids");
            foreach(explode('/', $query_params["writer_ids"]) as $value) {
                $ids[] = (int) $value;
            }
        }
        return $ids;
    }

    /**
     * @param array $writer_ids
     * @return BlankForm
     */
    private function buildAssignmentForm(array $writer_ids): Form
    {
        $service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $factory = $this->uiFactory;
        $custom_factory = $this->localDI->getUIFactory();
        $corrector_list = [
            CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT => $this->plugin->txt("unchanged"),
            CorrectorAdminService::BLANK_CORRECTOR_ASSIGNMENT => $this->lng->txt("remove")
        ];

        $corrector_ids = [];

        foreach($this->corrector_repo->getCorrectorsByTaskId($this->object->getId()) as $corrector) {
            $corrector_ids[$corrector->getId()] = $corrector->getUserId();
        }
        $names = \ilUserUtil::getNamePresentation(array_unique($corrector_ids), false, false, "", true);

        foreach ($corrector_ids as $id => $user_id) {
            $corrector_list[$id] = $names[$user_id];
        }

        $fields = [];
        $fields["first_corrector"] = $factory->input()->field()->select(
            $this->settings->getRequiredCorrectors() > 1
                ? $this->plugin->txt("assignment_pos_first")
                : $this->plugin->txt("assignment_pos_single"),
            $corrector_list
        )
            ->withRequired(true)
            ->withValue(CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT)
            ->withAdditionalTransformation($this->refinery->kindlyTo()->int());

        
        if ($this->settings->getRequiredCorrectors() > 1) {
            $fields["second_corrector"] = $factory->input()->field()->select(
                $this->plugin->txt("assignment_pos_second"),
                $corrector_list
            )
                                                  ->withRequired(true)
                                                  ->withValue(CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT)
                                                  ->withAdditionalTransformation($this->refinery->kindlyTo()->int());
        }


        if(count($writer_ids) == 1) { // Pre set the assigned correctors if its just one corrector
            $assignments = [];
            foreach($this->localDI->getCorrectorRepo()->getAssignmentsByWriterId($writer_ids[0]) as $assignment) {
                $assignments[$assignment->getPosition()] = $assignment;
            }

            $fields["first_corrector"] = $fields["first_corrector"]->withValue(
                isset($assignments[0]) ?
                    $assignments[0]->getCorrectorId() :
                    CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT
            );

            if ($this->settings->getRequiredCorrectors() > 1) {
                $fields["second_corrector"] = $fields["second_corrector"]->withValue(
                    isset($assignments[1]) ?
                    $assignments[1]->getCorrectorId() :
                    CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT
                );
            }
        }

        return $custom_factory->field()->blankForm($this->ctrl->getFormAction($this, "editAssignmentsAsync"), $fields)
            ->withAdditionalTransformation($this->refinery->custom()->constraint(
                function (array $var) {
                    if (!isset($var['second_corrector'])) {
                        return true;
                    }
                    if($var["first_corrector"] === CorrectorAdminService::BLANK_CORRECTOR_ASSIGNMENT
                        && $var["second_corrector"] === CorrectorAdminService::BLANK_CORRECTOR_ASSIGNMENT) {
                        return true;
                    }
                    if($var["first_corrector"] === CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT
                        && $var["second_corrector"] === CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT) {
                        return true;
                    }
                    return $var["first_corrector"] != $var["second_corrector"];
                },
                $this->plugin->txt("same_assigned_corrector_error")
            ))
            ->withAdditionalTransformation($this->refinery->custom()->constraint(
                function (array $var) use ($service, $writer_ids) {
                    $result = $service->assignMultipleCorrector(
                        $var["first_corrector"] ?? CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                        $var["second_corrector"] ?? CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                        $writer_ids,
                        true
                    );
                    return count($result['invalid']) === 0;
                },
                $this->plugin->txt("invalid_assignment_combinations_error")
            ));
    }


    protected function editAssignmentsAsync()
    {
        $writer_ids = $this->getWriterIds();
        $form = $this->buildAssignmentForm($writer_ids);

        if($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if (($data = $form->getData()) !== null) {

                $this->service->assignMultipleCorrector(
                    $data["first_corrector"] ?? CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                    $data["second_corrector"] ?? CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                    $writer_ids
                );
                $this->tpl->setOnScreenMessage("success", $this->plugin->txt("corrector_assignment_changed"), true);
                exit();
            } else {
                echo($this->renderer->render($form));
                exit();
            }
        }
        $message_box = $this->uiFactory->messageBox()->info($this->plugin->txt("change_corrector_info"));
        echo($this->renderer->renderAsync(
            $this->uiFactory->modal()->roundtrip(
                $this->plugin->txt("change_corrector"),
                [$message_box, $form]
            )
            ->withActionButtons([$this->uiFactory->button()->primary($this->lng->txt("submit"), "")->withOnClick($form->getSubmitAsyncSignal())])
        ));
        exit();
    }

    protected function confirmRemoveAuthorizationsAsync()
    {
        $writer_ids = $this->getWriterIds();
        $writers = $this->localDI->getWriterRepo()->getWritersByTaskId($this->object->getId());
        $essays = [];
        foreach($this->localDI->getEssayRepo()->getEssaysByTaskId($this->object->getId()) as $essay) {
            $essays[$essay->getWriterId()] = $essay;
        }

        $user_data = \ilUserUtil::getNamePresentation(array_unique(array_map(fn (Writer $x) => $x->getUserId(), $writers)), true, true, "", true);

        $items = [];

        foreach ($writer_ids as $writer_id) {
            $essay = $essays[$writer_id] ?? null;
            if ((!empty($essay->getCorrectionFinalized())
                || !empty($this->localDI->getCorrectorAdminService($essay->getTaskId())->getAuthorizedSummaries($essay)))
                && array_key_exists($writer_id, $writers)) {
                $writer = $writers[$writer_id];
                $items[] = $this->uiFactory->modal()->interruptiveItem(
                    $writer->getId(),
                    $user_data[$writer->getUserId()] . ' [' . $writer->getPseudonym() . ']'
                );
            }
        }

        if(count($items) > 0) {
            $confirm_modal = $this->uiFactory->modal()->interruptive(
                $this->plugin->txt("remove_authorizations"),
                $this->plugin->txt("remove_authorizations_confirmation"),
                $this->ctrl->getFormAction($this, "removeAuthorizations")
            )->withAffectedItems($items)->withActionButtonLabel("ok");
        } else {
            $confirm_modal = $this->uiFactory->modal()->roundtrip(
                $this->plugin->txt("remove_authorizations"),
                $this->uiFactory->messageBox()->failure($this->plugin->txt("remove_authorizations_no_valid_essays"))
            )
                ->withCancelButtonLabel("ok");
        }

        echo($this->renderer->renderAsync($confirm_modal));
        exit();
    }

    public function correctorAssignmentSpreadsheetImport()
    {
        $tempfile = new ilLongEssayAssessmentUploadTempFile($this->storage, $this->dic->filesystem(), $this->dic->upload());

        $form = $this->uiFactory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, "correctorAssignmentSpreadsheetImport"),
            ["excel" => $this->uiFactory->input()->field()->file(
                new \ilLongEssayAssessmentUploadHandlerGUI(
                    $this->storage,
                    $tempfile
                ),
                $this->plugin->txt("assignment_excel_import"),
                $this->plugin->txt("assignment_excel_import_info")
            )->withRequired(true)]
        );

        if($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if($data = $form->getData()) {
                $filename = $data['excel'][0];

                try {
                    $this->assignment_service->importAssignments(ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp/' . $filename);
                    $this->tpl->setOnScreenMessage("success", $this->plugin->txt("corrector_assignment_change_file_success"), true);
                    $tempfile->removeTempFile($filename);
                    $this->ctrl->redirect($this);
                } catch (CorrectorAssignmentsException $exception) {
                    $tempfile->removeTempFile($filename);
                    $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("corrector_assignment_change_file_failure")
                        . '<p class="small">' . nl2br($exception->getMessage()), true) . '</p>';
                } catch (\Exception $exception) {
                    $tempfile->removeTempFile($filename);
                    $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("corrector_assignment_change_file_failure"));
                }
            }
        }

        $this->tpl->setContent($this->renderer->render($form));
    }

    public function correctorAssignmentSpreadsheetExport()
    {
        try {
            $this->assignment_service->exportAssignments($this->spreadsheetAssignmentToggle());
        } catch (Exception $e) {
            // maybe logging
        }
        $this->ctrl->redirect($this);
    }

    protected function spreadsheetAssignmentToggle()
    {
        $cookie = $this->request->getCookieParams();
        if(isset($cookie['xlas_exass'])) {
            return $cookie['xlas_exass'] === "on";
        } else {
            return false;
        }
    }

}
