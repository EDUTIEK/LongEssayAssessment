<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\CorrectorAdmin;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\Corrector;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorAssignment;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\Plugin\LongEssayTask\WriterAdmin\CorrectorAdminListGUI;
use ILIAS\Plugin\LongEssayTask\WriterAdmin\CorrectorListGUI;
use \ilUtil;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayTask\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminGUI: ilObjLongEssayTaskGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminGUI: ilRepositorySearchGUI
 */
class CorrectorAdminGUI extends BaseGUI
{

	/** @var CorrectorAdminService */
	protected $service;

	public function __construct(\ilObjLongEssayTaskGUI $objectGUI)
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
					case 'assignWriters':
					case 'changeCorrector':
					case 'removeCorrector':
                    case 'exportCorrections':
                    case 'exportResults':
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
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
		$assign_writers_action = $this->ctrl->getLinkTarget($this, "assignWriters");
        $export_corrections_action =  $this->ctrl->getLinkTarget($this, "exportCorrections");
        $export_results_action =  $this->ctrl->getLinkTarget($this, "exportResults");

        $button = \ilLinkButton::getInstance();
        $button->setUrl($assign_writers_action);
        $button->setCaption($this->plugin->txt("assign_writers"), false);
        $button->setPrimary(true);
        $this->toolbar->addButtonInstance($button);

        $button = \ilLinkButton::getInstance();
        $button->setUrl($export_corrections_action);
        $button->setCaption($this->plugin->txt("export_corrections"), false);
        $this->toolbar->addButtonInstance($button);

        $button = \ilLinkButton::getInstance();
        $button->setUrl($export_results_action);
        $button->setCaption($this->plugin->txt("export_results"), false);
        $this->toolbar->addButtonInstance($button);


        $di = LongEssayTaskDI::getInstance();
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

		$di = LongEssayTaskDI::getInstance();
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

		$assign_writers_action = $this->ctrl->getLinkTarget($this, "assignWriters");

		$button = \ilLinkButton::getInstance();
		$button->setUrl($assign_writers_action);
		$button->setCaption($this->plugin->txt("assign_writers"), false);
		$button->setPrimary(false);
		$this->toolbar->addButtonInstance($button);
	}

	public function assignCorrectors(array $a_usr_ids, $a_type = null)
	{
		if (count($a_usr_ids) <= 0) {
			ilUtil::sendFailure($this->plugin->txt("missing_corrector_id"), true);
			$this->ctrl->redirect($this,"showCorrectors");
		}

		foreach($a_usr_ids as $id){
			$corrector_repo = LongEssayTaskDI::getInstance()->getCorrectorRepo();

			$corrector = new Corrector();
			$corrector->setTaskId($this->object->getId())
				->setUserId((int)$id);

			$corrector_repo->createCorrector($corrector);
		}

		ilUtil::sendSuccess($this->plugin->txt("assign_corrector_success"), true);
		$this->ctrl->redirect($this,"showCorrectors");
	}

	public function filterUserIdsByLETMembership($a_user_ids)
	{
		$user_ids = [];
		$corrector_repo = LongEssayTaskDI::getInstance()->getCorrectorRepo();
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
		$corrector_repo = LongEssayTaskDI::getInstance()->getCorrectorRepo();
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


	protected function assignWriters(){
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

	protected function changeCorrector(){
		if ($this->request->getMethod() == "POST") {
			$data = $_POST;

			// inputs are ok => save data
			if (array_key_exists("corrector", $data) && count($data["corrector"]) > 0 && array_key_exists("writer_id", $_GET)) {
				$writer_id = $_GET["writer_id"];
				$corr_repo = LongEssayTaskDI::getInstance()->getCorrectorRepo();
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


    private function getCorrectorId(): ?int
	{
		$query = $this->request->getQueryParams();
		if(isset($query["corrector_id"])) {
			return (int) $query["corrector_id"];
		}
		return null;
	}

}