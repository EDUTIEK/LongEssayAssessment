<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use Edutiek\LongEssayAssessmentService\Corrector\Service;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorContext;
use ILIAS\Plugin\LongEssayAssessment\Data\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\CorrectorAssignment;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\CorrectorAdminListGUI;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\CorrectorListGUI;
use \ilUtil;

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

	public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
	{
		parent::__construct($objectGUI);
		$this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
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
                    case 'confirmRemoveAuthorizations':
                    case 'removeAuthorizations':
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

        $essays = $essay_repo->getEssaysByTaskId($this->object->getId());
        $stitches = [];
        foreach ($essays as $essay){
            if($this->service->isStitchDecisionNeeded($essay)){
                $stitches[] = $essay->getId();
            }
        }
        $correction_settings = $di->getTaskRepo()->getCorrectionSettingsById($this->object->getId());


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


		$list_gui = new CorrectorAdminListGUI($this, "showStartPage", $this->plugin, $correction_settings);
		$list_gui->setWriters($writers_repo->getWritersByTaskId($this->object->getId()));
		$list_gui->setCorrectors($corrector_repo->getCorrectorsByTaskId($this->object->getId()));
		$list_gui->setEssays($essays);
		$list_gui->setAssignments($corrector_repo->getAssignmentsByTaskId($this->object->getId()));
		$list_gui->setCorrectionStatusStitches($stitches);

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

	protected function changeCorrector() {
		if ($this->request->getMethod() == "POST") {
			$data = $_POST;

			// inputs are ok => save data
			if (array_key_exists("corrector", $data) && count($data["corrector"]) > 0 && array_key_exists("writer_id", $_GET)) {
				$writer_id = $_GET["writer_id"];
				$corr_repo = LongEssayAssessmentDI::getInstance()->getCorrectorRepo();
				$corr_repo->deleteCorrectorAssignmentByWriter(intval($writer_id));
				$pos = 0;
				foreach ($data["corrector"] as $corr_id){
					if($corr_id !== "" && $corr_id !== "-1"){
						$assignment = new CorrectorAssignment();
						$assignment->setWriterId(intval($writer_id));
						$assignment->setCorrectorId(intval($corr_id));
						$assignment->setPosition($pos);
						$corr_repo->createCorrectorAssignment($assignment);
					}
					$pos++;
				}
				ilUtil::sendSuccess($this->plugin->txt("corrector_assignment_changed"), true);
				$anchor = "writer_" . $writer_id;
			} else {
				ilUtil::sendFailure($this->lng->txt("validation_error"), true);
			}
			$this->ctrl->redirect($this, "showStartPage", $anchor ?? "");
		}
	}

    protected function confirmRemoveAuthorizations()
    {
        if (empty($writer_id = $this->getWriterId()) || empty($writer = $this->localDI->getWriterRepo()->getWriterById($writer_id))) {
            $this->ctrl->redirect($this);
        }
        $name = \ilObjUser::_lookupFullname($writer->getUserId()) . ' [' . $writer->getPseudonym() . ']';

        $cancel = $this->uiFactory->button()->standard($this->lng->txt('cancel'), $this->ctrl->getLinkTarget($this));
        $this->ctrl->setParameter($this, 'writer_id', $writer_id);
        $ok = $this->uiFactory->button()->standard($this->lng->txt('ok'), $this->ctrl->getLinkTarget($this, 'removeAuthorizations'));

        $this->tpl->setContent($this->renderer->render($this->uiFactory->messageBox()->confirmation(
            sprintf($this->plugin->txt('confirm_remove_authorizations_for'), $name))->withButtons([$ok, $cancel])));
    }


    protected function removeAuthorizations()
    {
        if (empty($writer_id = $this->getWriterId()) || empty($writer = $this->localDI->getWriterRepo()->getWriterById($writer_id))) {
            $this->ctrl->redirect($this);
        }
        $name = \ilObjUser::_lookupFullname($writer->getUserId()) . ' [' . $writer->getPseudonym() . ']';

        if ($this->service->removeAuthorizations($writer)) {
            ilutil::sendSuccess(sprintf($this->plugin->txt('remove_authorizations_for_done'), $name), true);
        }
        else {
            ilutil::sendFailure(sprintf($this->plugin->txt('remove_authorizations_for_failed'), $name), true);
        }
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

}
