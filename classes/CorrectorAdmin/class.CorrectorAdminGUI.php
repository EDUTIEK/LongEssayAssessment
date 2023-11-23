<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use Edutiek\LongEssayAssessmentService\Corrector\Service;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorContext;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\CorrectorAdminListGUI;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\CorrectorListGUI;
use ILIAS\UI\Component\Input\Container\Form\Form;
use \ilUtil;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;
use ILIAS\Plugin\LongEssayAssessment\Task\ResourceUploadHandlerGUI;
use ILIAS\Plugin\LongEssayAssessment\ilLongEssayAssessmentUploadTempFile;
use ILIAS\Filesystem\Filesystem;
use ILIAS\DI\Exceptions\Exception;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI: ilRepositorySearchGUI
 */
class CorrectorAdminGUI extends BaseGUI
{

	/** @var CorrectorAdminService */
	protected $service;
    
    /** @var CorrectionSettings */
    protected $settings;

	public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
	{
		parent::__construct($objectGUI);
		$this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->settings = $this->localDI->getTaskRepo()->getCorrectionSettingsById($this->object->getId());
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
				$rep_search = new \ilRepositorySearchGUI();
				$rep_search->addUserAccessFilterCallable([$this, 'filterUserIdsByLETMembership']);
				$rep_search->setCallback($this, "assignCorrectors");
				$this->ctrl->setReturn($this, 'showStartPage');
				$ret = $this->ctrl->forwardCommand($rep_search);
				break;
			default:
				$cmd = $this->ctrl->getCmd('showStartPage');
				switch ($cmd)
				{
					case 'showStartPage':
					case 'showCorrectors':
                    case 'confirmAssignWriters':
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
                    case 'downloadCorrectedPdf':
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
        $di = LongEssayAssessmentDI::getInstance();
        $writers_repo = $di->getWriterRepo();
        $corrector_repo = $di->getCorrectorRepo();
        $essay_repo = $di->getEssayRepo();
        $task_repo = $di->getTaskRepo();

        $essays = $essay_repo->getEssaysByTaskId($this->object->getId());
        $stitches = [];
        foreach ($essays as $essay){
            if($this->service->isStitchDecisionNeeded($essay)){
                $stitches[] = $essay->getId();
            }
        }

        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
		$assign_writers_action = $this->ctrl->getLinkTarget($this, "confirmAssignWriters");
        $export_corrections_action =  $this->ctrl->getLinkTarget($this, "exportCorrections");
        $export_results_action =  $this->ctrl->getLinkTarget($this, "exportResults");
        $stitch_decision_action =  $this->ctrl->getLinkTarget($this, "stitchDecision");

        $button = \ilLinkButton::getInstance();
        $button->setUrl($assign_writers_action);
        $button->setCaption($this->plugin->txt("assign_writers"), false);
        $button->setPrimary(true);
        $this->toolbar->addButtonInstance($button);

        $button = \ilLinkButton::getInstance();
        $button->setUrl(empty($stitches) ? '#' : $stitch_decision_action);
        $button->setCaption($this->plugin->txt("do_stich_decision"), false);
        $button->setDisabled(empty($stitches));
        $this->toolbar->addButtonInstance($button);

        $this->toolbar->addSeparator();

        $button = \ilLinkButton::getInstance();
        $button->setUrl($export_corrections_action);
        $button->setCaption($this->plugin->txt("export_corrections"), false);
        $this->toolbar->addButtonInstance($button);

        $button = \ilLinkButton::getInstance();
        $button->setUrl($export_results_action);
        $button->setCaption($this->plugin->txt("export_results"), false);
        $this->toolbar->addButtonInstance($button);

        $this->toolbar->addSeparator();

        $btn_export = $this->uiFactory->button()->standard(
            $this->plugin->txt("assignment_excel_export"),
            $this->ctrl->getLinkTarget($this, "correctorAssignmentSpreadsheetExport")
        );
        $btn_import = $this->uiFactory->button()->standard(
            $this->plugin->txt("assignment_excel_import"),
            $this->ctrl->getLinkTarget($this, "correctorAssignmentSpreadsheetImport")
        );
        $btn_export_ass = $this->uiFactory->button()->toggle(
            $this->plugin->txt("assignment_excel_export_auth"), "#", "#", $this->spreadsheetAssignmentToggle()
        )->withAdditionalOnLoadCode(function ($id) {
            return "$('#{$id}').on( 'click', function() {  document.cookie = 'xlas_exass=' + ($( this ).hasClass('on') ? 'on' : 'off'); } );";
        }
        );

        $this->toolbar->addText($this->plugin->txt("assignment_excel"));
        $this->toolbar->addComponent($btn_import);
        $this->toolbar->addComponent($btn_export);
        $this->toolbar->addComponent($btn_export_ass);
        $this->toolbar->addSeparator();

		$list_gui = new CorrectorAdminListGUI($this, "showStartPage", $this->plugin, $this->settings);
		$list_gui->setWriters($writers_repo->getWritersByTaskId($this->object->getId()));
		$list_gui->setCorrectors($corrector_repo->getCorrectorsByTaskId($this->object->getId()));
		$list_gui->setEssays($essays);
		$list_gui->setAssignments($corrector_repo->getAssignmentsByTaskId($this->object->getId()));
		$list_gui->setCorrectionStatusStitches($stitches);
        $list_gui->setLocations($task_repo->getLocationsByTaskId($this->object->getId()));

        $this->tpl->setContent($list_gui->getContent());
	}

	protected function showCorrectors(){
		$this->toolbar->setFormAction($this->ctrl->getFormAction($this));
		$this->showCorrectorToolbar();

		$di = LongEssayAssessmentDI::getInstance();
		$writers_repo = $di->getWriterRepo();
		$corrector_repo = $di->getCorrectorRepo();


		$list_gui = new CorrectorListGUI($this, "showCorrectors", $this->plugin);
		$list_gui->setWriters($writers_repo->getWritersByTaskId($this->object->getId()));
		$list_gui->setCorrectors($corrector_repo->getCorrectorsByTaskId($this->object->getId()));
		$list_gui->setAssignments($corrector_repo->getAssignmentsByTaskId($this->object->getId()));

		$this->tpl->setContent($list_gui->getContent());
	}

	private function showCorrectorToolbar(){

		\ilRepositorySearchGUI::fillAutoCompleteToolbar(
			$this,
			$this->toolbar,
			array()
		);

		// spacer
		$this->toolbar->addSeparator();

		// search button
		$this->toolbar->addButton(
			$this->plugin->txt("search_correctors"),
			$this->ctrl->getLinkTargetByClass(
				'ilRepositorySearchGUI',
				'start'
			)
		);
    }

	public function assignCorrectors(array $a_usr_ids, $a_type = null)
	{
		if (count($a_usr_ids) <= 0) {
			ilUtil::sendFailure($this->plugin->txt("missing_corrector_id"), true);
			$this->ctrl->redirect($this,"showCorrectors");
		}

		foreach($a_usr_ids as $id) {
            $this->service->getOrCreateCorrectorFromUserId($id);
		}

		ilUtil::sendSuccess($this->plugin->txt("assign_corrector_success"), true);
		$this->ctrl->redirect($this,"showCorrectors");
	}

	public function filterUserIdsByLETMembership($a_user_ids)
	{
		$user_ids = [];
		$corrector_repo = LongEssayAssessmentDI::getInstance()->getCorrectorRepo();
		$writers = array_map(fn ($row) => $row->getUserId(), $corrector_repo->getCorrectorsByTaskId($this->object->getId()));

		foreach ($a_user_ids as $user_id){
			if(!in_array((int)$user_id, $writers)){
				$user_ids[] = $user_id;
			}
		}

		return $user_ids;
	}

	private function removeCorrector(){
		if(($id = $this->getCorrectorId()) === null)
		{
			ilUtil::sendFailure($this->plugin->txt("missing_corrector_id"), true);
			$this->ctrl->redirect($this, "showCorrectors");
		}
		$corrector_repo = LongEssayAssessmentDI::getInstance()->getCorrectorRepo();
		$corrector = $corrector_repo->getCorrectorById($id);

		if($corrector === null || $corrector->getTaskId() !== $this->object->getId()){
			ilUtil::sendFailure($this->plugin->txt("missing_corrector"), true);
			$this->ctrl->redirect($this, "showCorrectors");
		}
		$ass = $corrector_repo->getAssignmentsByCorrectorId($corrector->getId());

		if(count($ass) > 0){
			ilUtil::sendFailure($this->plugin->txt("remove_writer_pending_assignments"), true);
			$this->ctrl->redirect($this, "showCorrectors");
		}

		$corrector_repo->deleteCorrector($corrector->getId());
		ilUtil::sendSuccess($this->plugin->txt("remove_writer_success"), true);
		$this->ctrl->redirect($this, "showCorrectors");
	}

    protected function confirmAssignWriters() {

        $missing = $this->service->countMissingCorrectors();
        if ($missing == 0) {
            ilUtil::sendInfo($this->plugin->txt('assign_not_needed'), true);
            $this->ctrl->redirect($this, 'showStartPage');
        }
        $available = $this->service->countAvailableCorrectors();
        if ($available == 0)  {
            ilUtil::sendInfo($this->plugin->txt('assign_not_available'), true);
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
            ilUtil::sendInfo($this->plugin->txt('warning_potential_later_assignments') . '<br>' . implode('<br>', $warnings));
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
        $gui->setCancel($this->lng->txt('cancel'),'showStartPage');
        $gui->setConfirm($this->plugin->txt('assign_writers'),'assignWriters');

        $this->tpl->setContent($gui->getHTML());

    }

	protected function assignWriters() {
		$assigned = $this->service->assignMissingCorrectors();
        if ($assigned == 0) {
            ilUtil::sendFailure($this->plugin->txt("0_assigned_correctors"), true);
        }
        elseif ($assigned == 1) {
            ilUtil::sendSuccess($this->plugin->txt("1_assigned_corrector"), true);
        }
        else {
            ilUtil::sendSuccess(sprintf($this->plugin->txt("n_assigned_correctors"), $assigned), true);
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

		foreach($writer_ids as $writer_id){
			if(($writer = $this->localDI->getWriterRepo()->getWriterById($writer_id)) !== null) {
				if ($this->service->removeAuthorizations($writer)) {
					$valid[] = $writer;
				} else {
					$invalid[] = $writer;
				}
			}
		}
		if(count($invalid) > 0){
			$names = [];
			foreach ($invalid as $writer){
				$names[] = \ilObjUser::_lookupFullname($writer->getUserId()) . ' [' . $writer->getPseudonym() . ']';
			}
			ilutil::sendFailure(sprintf($this->plugin->txt('remove_authorizations_for_failed'), implode(", ", $names)), true);
		}
		if(count($valid) > 0){
			$names = [];
			foreach ($valid as $writer){
				$names[] = \ilObjUser::_lookupFullname($writer->getUserId()) . ' [' . $writer->getPseudonym() . ']';
			}
			ilutil::sendSuccess(sprintf($this->plugin->txt('remove_authorizations_for_done'), implode(", ", $names)), true);
		}

		$this->ctrl->clearParameters($this);
        $this->ctrl->redirect($this);
    }


    protected function exportCorrections()
    {
        $filename = \ilUtil::getASCIIFilename($this->plugin->txt('export_corrections_file_prefix') .' ' .$this->object->getTitle()) . '.zip';
        ilUtil::deliverFile($this->service->createCorrectionsExport($this->object), $filename, 'application/zip', true, true);
    }

    protected function exportResults()
    {
        $filename = \ilUtil::getASCIIFilename($this->plugin->txt('export_results_file_prefix') .' ' . $this->object->getTitle()) . '.csv';
        ilUtil::deliverFile($this->service->createResultsExport(), $filename, 'text/csv', true, true);
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

        $filename = 'task' . $this->object->getId() . '_user' . $this->dic->user()->getId(). '.pdf';
        ilUtil::deliverData($service->getCorrectionAsPdf($this->object, $repoWriter), $filename, 'application/pdf');
    }

    private function exportSteps()
    {
        if (empty($repoWriter = $this->localDI->getWriterRepo()->getWriterById((int) $this->getWriterId()))) {
            ilUtil::sendFailure($this->plugin->txt("missing_writer_id"), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        $service = $this->localDI->getWriterAdminService($this->object->getId());
        $name = \ilUtil::getASCIIFilename($this->object->getTitle() .'_' . \ilObjUser::_lookupFullname($repoWriter->getUserId()));
        $zipfile = $service->createWritingStepsExport($this->object, $repoWriter, $name);
        if (empty($zipfile)) {
            ilUtil::sendFailure($this->plugin->txt("content_not_available"), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        ilUtil::deliverFile($zipfile, $name . '.zip', 'application/zip', true, true);
    }

    private function getWriterId(): ?int
    {
        $query = $this->request->getQueryParams();
        if(isset($query["writer_id"])) {
            return (int) $query["writer_id"];
        }
        return null;
    }

    private function getCorrectorId(): ?int
	{
		$query = $this->request->getQueryParams();
		if(isset($query["corrector_id"])) {
			return (int) $query["corrector_id"];
		}
		return null;
	}

	protected function getWriterIds(): array
	{
		$ids = [];
		$query_params = $this->request->getQueryParams();

		if(isset($query_params["writer_id"]) && $query_params["writer_id"] !== ""){
			$this->ctrl->saveParameter($this, "writer_id");
			$ids[] = (int) $query_params["writer_id"];
		}elseif (isset($query_params["writer_ids"])){
			$this->ctrl->saveParameter($this, "writer_ids");
			foreach(explode('/', $query_params["writer_ids"]) as $value){
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
		$corrector_repo = $this->localDI->getCorrectorRepo();
		$corrector_list = [
			CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT => $this->plugin->txt("unchanged"),
			CorrectorAdminService::BLANK_CORRECTOR_ASSIGNMENT => $this->lng->txt("remove")
		];

		$corrector_ids = [];

		foreach($corrector_repo->getCorrectorsByTaskId($this->object->getId()) as $corrector){
			$corrector_ids[$corrector->getId()] = $corrector->getUserId();
		}
		$names = \ilUserUtil::getNamePresentation(array_unique($corrector_ids), false, false, "", true);

		foreach ($corrector_ids as $id => $user_id){
			$corrector_list[$id] = $names[$user_id];
		}

		$fields = [];
		$fields["first_corrector"] = $factory->input()->field()->select(
            $this->settings->getRequiredCorrectors() > 1 
                ? $this->plugin->txt("assignment_pos_first")
                : $this->plugin->txt("assignment_pos_single")
            , $corrector_list)
			->withRequired(true)
			->withValue(CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT)
			->withAdditionalTransformation($this->refinery->kindlyTo()->int());
		
        
        if ($this->settings->getRequiredCorrectors() > 1) {
            $fields["second_corrector"] = $factory->input()->field()->select(
                $this->plugin->txt("assignment_pos_second"), $corrector_list)
                                                  ->withRequired(true)
                                                  ->withValue(CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT)
                                                  ->withAdditionalTransformation($this->refinery->kindlyTo()->int());
        }


		if(count($writer_ids) == 1) { // Pre set the assigned correctors if its just one corrector
			$assignments = [];
			foreach($this->localDI->getCorrectorRepo()->getAssignmentsByWriterId($writer_ids[0]) as $assignment){
				$assignments[$assignment->getPosition()] = $assignment;
			}

			$fields["first_corrector"] = $fields["first_corrector"]->withValue(
				isset($assignments[0]) ?
					$assignments[0]->getCorrectorId() :
					CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT
			);

            if ($this->settings->getRequiredCorrectors() > 1) {
                $fields["second_corrector"] = $fields["second_corrector"]->withValue(isset($assignments[1]) ?
                    $assignments[1]->getCorrectorId() :
                    CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT
                );
            }
		}

		return $custom_factory->field()->blankForm($this->ctrl->getFormAction($this, "editAssignmentsAsync"), $fields)
			->withAdditionalTransformation($this->refinery->custom()->constraint(
				function (array $var){
                    if (!isset($var['second_corrector'])) {
                        return true;
                    }
					if($var["first_corrector"] === CorrectorAdminService::BLANK_CORRECTOR_ASSIGNMENT
						&& $var["second_corrector"] === CorrectorAdminService::BLANK_CORRECTOR_ASSIGNMENT){
						return true;
					}
                    if($var["first_corrector"] === CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT
                        && $var["second_corrector"] === CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT){
                        return true;
                    }
					return $var["first_corrector"] != $var["second_corrector"];
				}, $this->plugin->txt("same_assigned_corrector_error")))
			->withAdditionalTransformation($this->refinery->custom()->constraint(
				function (array $var) use ($service, $writer_ids){
					$result = $service->assignMultipleCorrector(
                        $var["first_corrector"] ?? CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT, 
                            $var["second_corrector"] ?? CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT, 
                        $writer_ids, true);
					return count($result['invalid']) === 0;
				}, $this->plugin->txt("invalid_assignment_combinations_error")));
	}


	protected function editAssignmentsAsync(){
		$writer_ids = $this->getWriterIds();
		$form = $this->buildAssignmentForm($writer_ids);

		if($this->request->getMethod() === "POST") {
			$form = $form->withRequest($this->request);

			if (($data = $form->getData()) !== null) {

				$this->service->assignMultipleCorrector(
                    $data["first_corrector"] ?? CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT, 
                    $data["second_corrector"] ?? CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                    $writer_ids);
				ilUtil::sendSuccess($this->plugin->txt("corrector_assignment_changed"), true);
				exit();
			}else{
				echo($this->renderer->render($form));
				exit();
			}
		}
		$message_box = $this->uiFactory->messageBox()->info($this->plugin->txt("change_corrector_info"));
		echo($this->renderer->renderAsync($this->uiFactory->modal()->roundtrip(
			$this->plugin->txt("change_corrector"), [$message_box, $form])
			->withActionButtons([$this->uiFactory->button()->primary($this->lng->txt("submit"), "")->withOnClick($form->getSubmitAsyncSignal())])
		));
		exit();
	}

	protected function confirmRemoveAuthorizationsAsync()
	{
		$writer_ids = $this->getWriterIds();
		$writers = $this->localDI->getWriterRepo()->getWritersByTaskId($this->object->getId());
		$essays = [];
		foreach($this->localDI->getEssayRepo()->getEssaysByTaskId($this->object->getId()) as $essay){
			$essays[$essay->getWriterId()] = $essay;
		}

		$user_data = \ilUserUtil::getNamePresentation(array_unique(array_map(fn(Writer $x) => $x->getUserId(), $writers)), true, true, "", true);

		$items = [];

		foreach ($writer_ids as $writer_id){
			$essay = $essays[$writer_id] ?? null;
			if ((!empty($essay->getCorrectionFinalized())
				|| !empty($this->localDI->getCorrectorAdminService($essay->getTaskId())->getAuthorizedSummaries($essay)))
				&& array_key_exists($writer_id, $writers))
			{
				$writer = $writers[$writer_id];
				$items[] = $this->uiFactory->modal()->interruptiveItem(
					$writer->getId(), $user_data[$writer->getUserId()] . ' [' . $writer->getPseudonym() . ']'
				);
			}
		}

		if(count($items) > 0){
			$confirm_modal = $this->uiFactory->modal()->interruptive(
				$this->plugin->txt("remove_authorizations"),
				$this->plugin->txt("remove_authorizations_confirmation"),
				$this->ctrl->getFormAction($this, "removeAuthorizations")
			)->withAffectedItems($items)->withActionButtonLabel("ok");
		}else{
			$confirm_modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("remove_authorizations"),
				$this->uiFactory->messageBox()->failure($this->plugin->txt("remove_authorizations_no_valid_essays")))
				->withCancelButtonLabel("ok");
		}

		echo($this->renderer->renderAsync($confirm_modal));
		exit();
	}

    public function correctorAssignmentSpreadsheetImport(){
        $corrector_repo = $this->localDI->getCorrectorRepo();
        $task_repo = $this->localDI->getTaskRepo();
        $tempfile = new ilLongEssayAssessmentUploadTempFile($this->storage, $this->dic->filesystem(), $this->dic->upload());

        $form = $this->uiFactory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, "correctorAssignmentSpreadsheetImport"),
            ["excel" => $this->uiFactory->input()->field()->file(
                new \ilLongEssayAssessmentUploadHandlerGUI($this->storage, $tempfile
                ), $this->plugin->txt("assignment_excel_import"))]
        );

        if($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if($data = $form->getData()) {
                $filename = $data['excel'][0];
                $needed_corr = $task_repo->getCorrectionSettingsById($this->object->getId())->getRequiredCorrectors();

                try{
                    $spreadsheet = new CorrectorAssignmentExcel();
                    $spreadsheet->loadFromFile(\ilUtil::getDataDir() . '/temp/' . $filename);

                    $spreadsheet->setActiveSheet(1);
                    $corrector = [];
                    $r = 2;
                    while (($id = $spreadsheet->getCell($r, 0)) != null) {
                        $login = $spreadsheet->getCell($r, 1);
                        $corrector[$login] = $id;
                        $r++;
                    }
                    $spreadsheet->setActiveSheet(0);
                    $corrector_assignments = [];
                    $r = 2;
                    while (($id = $spreadsheet->getCell($r, 0)) != null) {
                        $assigned = [];
                        foreach(range(0, $needed_corr-1) as $pos){
                            $login = $spreadsheet->getCell($r, 8+$pos);
                            $assigned[$pos] = (int) $corrector[$login] ?? CorrectorAdminService::BLANK_CORRECTOR_ASSIGNMENT;
                        }

                        $corrector_assignments[$id] = $assigned;
                        $r++;
                    }

                    foreach($corrector_assignments as $writer_id => $as){
                        $fa = $as[0];
                        $sa = $needed_corr == 2 ? $as[1] : CorrectorAdminService::UNCHANGED_CORRECTOR_ASSIGNMENT;

                        $this->service->assignMultipleCorrector($fa, $sa, [$writer_id]);
                    }
                    $tempfile->removeTempFile($filename);
                    ilUtil::sendSuccess($this->plugin->txt("corrector_assignment_changed"), true);
                    $this->ctrl->redirect($this);
                }catch (\Exception $exception){
                    $tempfile->removeTempFile($filename);
                    ilUtil::sendFailure($this->plugin->txt("corrector_assignment_change_file_failure"), true);
                }
            }
        }
        ilUtil::sendInfo($this->plugin->txt("change_corrector_info"));
        $this->tpl->setContent($this->renderer->render($form));
    }

    public function correctorAssignmentSpreadsheetExport()
    {
        $corrector_repo = $this->localDI->getCorrectorRepo();
        $writer_repo = $this->localDI->getWriterRepo();
        $task_repo = $this->localDI->getTaskRepo();
        $essay_repo = $this->localDI->getEssayRepo();
        $needed_corr = $task_repo->getCorrectionSettingsById($this->object->getId())->getRequiredCorrectors();
        $participant_title = "Writer";
        $corrector_title = "Corrector";
        $locations = [];

        foreach($task_repo->getLocationsByTaskId($this->object->getId()) as $location){
            $locations[$location->getId()] = $location;
        }

        $essays = [];

        foreach($essay_repo->getEssaysByTaskId($this->object->getId()) as $essay){
            $essays[$essay->getWriterId()] = $essay;
        }
        $corrector = [];
        foreach($corrector_repo->getCorrectorsByTaskId($this->object->getId()) as $r){
            $corrector[$r->getId()] = $r;
        }

        $writer = $writer_repo->getWritersByTaskId($this->object->getId());

        if($this->spreadsheetAssignmentToggle()){

            $authorized_user = [];
            foreach($essays as $writer_id => $essay){
                if($essay->getWritingAuthorized() !== null){
                    $authorized_user[] = $essay->getWriterId();
                }
            }
            $writer = array_filter($writer, fn($x) => in_array($x->getId(), $authorized_user));
        }

        $users = [];

        foreach(\ilObjUser::_getUserData(array_merge(
            array_map(fn (Corrector $x) => $x->getUserId(), $corrector),
            array_map(fn (Writer $x) => $x->getUserId(), $writer),
        )) as $u){
            $users[(int)$u['usr_id']] = $u;
        }

        $assignments = [];

        foreach($corrector_repo->getAssignmentsByTaskId($this->object->getId()) as $assignment){
            $assignments[$assignment->getWriterId()][$assignment->getPosition()] = $assignment;
        }
        $r = 2;
        $spreadsheet = new CorrectorAssignmentExcel();
        $writer_sheet = $spreadsheet->addSheet($participant_title, true);
        $corrector_sheet = $spreadsheet->addSheet($corrector_title, false);
        $spreadsheet->setCell(1, 0, 'Id');
        $spreadsheet->setCell(1, 1, 'Login');
        $spreadsheet->setCell(1, 2, 'Firstname');
        $spreadsheet->setCell(1, 3, 'Lastname');
        $spreadsheet->setCell(1, 4, 'Email');
        $spreadsheet->setCell(1, 5, 'Pseudonym');
        $spreadsheet->setCell(1, 6, 'Location');
        $spreadsheet->setCell(1, 7, 'Words');
        foreach(range(0, $needed_corr-1) as $pos){
            $spreadsheet->setCell(1, 8+$pos, 'Corrector ' . ($pos+1));
        }

        foreach($writer as $w){
            $data = $users[$w->getUserId()] ?? [];
            $ass = $assignments[$w->getId()] ?? [];
            ksort($ass);
            $essay = $essays[$w->getId()] ?? null;
            $location = $essay !== null ? $locations[$essay->getLocation()] ?? null : null;
            $written_text = $essay !== null ? $essay->getWrittenText() : "";
            $location_text = $location !== null ? $location->getTitle() : "";

            $spreadsheet->setCell($r, 0, $w->getId());
            $spreadsheet->setCell($r, 1, $data['login'] ?? "");
            $spreadsheet->setCell($r, 2, $data['firstname'] ?? "");
            $spreadsheet->setCell($r, 3, $data['lastname'] ?? "");
            $spreadsheet->setCell($r, 4, $data['email'] ?? "");
            $spreadsheet->setCell($r, 5, $w->getPseudonym());
            $spreadsheet->setCell($r, 6, $location_text);
            $spreadsheet->setCell($r, 7, str_word_count($written_text));

            foreach(range(0, $needed_corr-1) as $pos){
                $spreadsheet->addDropdownCol($r, 8+$pos, '=\''.$corrector_title.'\'!$B$2:$B$'.(count($corrector)+1));
            }

            foreach ($ass as $a){
                $c = $corrector[$a->getCorrectorId()] ?? null;
                $login = $c !== null && isset($users[$c->getUserId()]) ? $users[$c->getUserId()]['login'] ?? '' : '';
                $spreadsheet->setCell($r, 8+$a->getPosition(), $login);
            }
            $r++;
        }
        $r = 2;
        $spreadsheet->setActiveSheet($corrector_sheet);
        $spreadsheet->setCell(1, 0, "Id");
        $spreadsheet->setCell(1, 1, 'Login');
        $spreadsheet->setCell(1, 2, 'Firstname');
        $spreadsheet->setCell(1, 3, 'Lastname');
        $spreadsheet->setCell(1, 4, 'Email');

        foreach($corrector as $c){
            $data = $users[$c->getUserId()] ?? [];
            $ass = $assignments[$c->getId()] ?? [];
            ksort($ass);

            $spreadsheet->setCell($r, 0, $c->getId());
            $spreadsheet->setCell($r, 1, $data['login'] ?? "");
            $spreadsheet->setCell($r, 2, $data['firstname'] ?? "");
            $spreadsheet->setCell($r, 3, $data['lastname'] ?? "");
            $spreadsheet->setCell($r, 4, $data['email'] ?? "");
            $r++;
        }
        $spreadsheet->setActiveSheet($corrector_sheet);
        $spreadsheet->sendToClient("corrector_assignment.xlsx");
        $this->ctrl->redirect($this);
    }

    protected function spreadsheetAssignmentToggle(){
        $cookie = $this->request->getCookieParams();
        if(isset($cookie['xlas_exass']) )
            return $cookie['xlas_exass'] === "on";
        else{
            return false;
        }
    }

}
