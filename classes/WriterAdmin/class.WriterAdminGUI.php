<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\DI\Exceptions\Exception;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay;
use ILIAS\Plugin\LongEssayAssessment\Data\GradeLevel;
use ILIAS\Plugin\LongEssayAssessment\Data\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\Data\ObjectSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\TimeExtension;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use \ilUtil;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\WriterAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI: ilRepositorySearchGUI
 */
class WriterAdminGUI extends BaseGUI
{
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
				$rep_search->setCallback($this, "assignWriters");
				$this->ctrl->setReturn($this, 'showStartPage');
				$ret = $this->ctrl->forwardCommand($rep_search);
				break;
			default:
				$cmd = $this->ctrl->getCmd('showStartPage');
				switch ($cmd)
				{
					case 'showStartPage':
					case 'addWriter':
					case 'excludeWriter':
					case 'editExtension':
					case 'updateExtension':
					case 'authorizeWriting':
					case 'repealExclusion':
					case 'deleteWriterData':
					case 'removeWriter':
                    case 'exportSteps':
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

		\ilRepositorySearchGUI::fillAutoCompleteToolbar(
			$this,
			$this->toolbar,
			array()
		);

		// search button
		$delete_writer_data_button = $this->uiFactory->button()->standard(
			$this->plugin->txt("search_participants"),
			$this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI', 'start')
		);
		$this->toolbar->addComponent($delete_writer_data_button);

		// spacer
		$this->toolbar->addSeparator();

		$delete_writer_data_modal = $this->buildDeleteWriterDataModal();
		$delete_writer_data_button = $this->uiFactory->button()->standard($this->plugin->txt("delete_writer_data"), "#")
			->withOnClick($delete_writer_data_modal->getShowSignal());
		$this->toolbar->addComponent($delete_writer_data_button);

		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();

		$list_gui = new WriterAdminListGUI($this, "showStartPage", $this->plugin);
		$list_gui->setWriters($writer_repo->getWritersByTaskId($this->object->getId()));
		$list_gui->setExtensions($writer_repo->getTimeExtensionsByTaskId($this->object->getId()));
		$list_gui->setEssays($essay_repo->getEssaysByTaskId($this->object->getId()));

        $this->tpl->setContent($this->renderer->render($delete_writer_data_modal) . $list_gui->getContent());
     }

	private function excludeWriter(){
		global $DIC;
		if(($id = $this->getWriterId()) === null)
		{
			ilUtil::sendFailure($this->plugin->txt("missing_writer_id"), true);
			$this->ctrl->redirect($this, "showStartPage");
		}
		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$writer = $writer_repo->getWriterById($id);

		if($writer === null || $writer->getTaskId() !== $this->object->getId()){
			ilUtil::sendFailure($this->plugin->txt("missing_writer"), true);
			$this->ctrl->redirect($this, "showStartPage");
		}
		$essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
		$essay = $essay_repo->getEssayByWriterIdAndTaskId($writer->getId(), $this->object->getId());

		if($essay !== null && $essay->getEditStarted() !== null){
			$datetime = new \ilDateTime(time(), IL_CAL_UNIX);
			$essay->setWritingExcluded($datetime->get(IL_CAL_DATETIME));
			$essay->setWritingExcludedBy($DIC->user()->getId());
			$essay_repo->updateEssay($essay);
			$this->createExclusionLogEntry($writer);
		}else{
			// Writer hasn't started yet and is causally deleted
			$writer_repo->deleteWriter($writer->getId());
		}

		ilUtil::sendSuccess($this->plugin->txt("exclude_writer_success"), true);
		$this->ctrl->redirect($this, "showStartPage");
	}

	private function repealExclusion(){
		global $DIC;
		if(($id = $this->getWriterId()) === null)
		{
			ilUtil::sendFailure($this->plugin->txt("missing_writer_id"), true);
			$this->ctrl->redirect($this, "showStartPage");
		}
		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$writer = $writer_repo->getWriterById($id);
		$essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
		$essay = $essay_repo->getEssayByWriterIdAndTaskId($writer->getId(), $this->object->getId());

		if($writer === null || $writer->getTaskId() !== $this->object->getId() || $essay === null){
			ilUtil::sendFailure($this->plugin->txt("missing_essay"), true);
			$this->ctrl->redirect($this, "showStartPage");
		}

		if($essay->getWritingExcluded() === null){
			ilUtil::sendFailure($this->plugin->txt("essay_not_excluded"), true);
			$this->ctrl->redirect($this, "showStartPage");
		}

		$essay->setWritingExcluded(null);
		$essay->setWritingExcludedBy(null);
		$essay_repo->updateEssay($essay);
		$this->createExclusionRepealLogEntry($writer);

		ilUtil::sendSuccess($this->plugin->txt("exclude_writer_repeal_success"), true);
		$this->ctrl->redirect($this, "showStartPage");
	}

	private function removeWriter(){
		if(($id = $this->getWriterId()) === null)
		{
			ilUtil::sendFailure($this->plugin->txt("missing_writer_id"), true);
			$this->ctrl->redirect($this, "showStartPage");
		}

		$essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$corr_repo = LongEssayAssessmentDI::getInstance()->getCorrectorRepo();

		$writer = $writer_repo->getWriterById($id);

		if($writer === null || $writer->getTaskId() !== $this->object->getId()){
			ilUtil::sendFailure($this->plugin->txt("missing_writer"), true);
			$this->ctrl->redirect($this, "showStartPage");
		}

		$essay_repo->deleteEssayByWriterId($id);
		$writer_repo->deleteWriter($id);
		$corr_repo->deleteCorrectorAssignmentByWriter($id);

		ilUtil::sendSuccess($this->plugin->txt("remove_writer_success"), true);
		$this->ctrl->redirect($this, "showStartPage");
	}

	public function assignWriters(array $a_usr_ids, $a_type = null)
	{
		if (count($a_usr_ids) <= 0) {
			ilUtil::sendFailure($this->plugin->txt("no_writer_set"), true);
			$this->ctrl->redirect($this,"showStartPage");
		}

		foreach($a_usr_ids as $id) {
            $this->localDI->getWriterAdminService($this->object->getId())
                ->getOrCreateWriterFromUserId((int) $id);
		}

		if(count($a_usr_ids) == 1){
			$anchor =  "user_" . $a_usr_ids[0];
		}

		ilUtil::sendSuccess($this->plugin->txt("assign_writer_success"), true);
		$this->ctrl->redirect($this,"showStartPage", $anchor ?? "");
	}

	private function getWriterId(): ?int
	{
		$query = $this->request->getQueryParams();
		if(isset($query["writer_id"])) {
			return (int) $query["writer_id"];
		}
		return null;
	}

	public function filterUserIdsByLETMembership($a_user_ids)
	{
		$user_ids = [];
		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$writers = array_map(fn ($row) => $row->getUserId(), $writer_repo->getWritersByTaskId($this->object->getId()));

		foreach ($a_user_ids as $user_id){
			if(!in_array((int)$user_id, $writers)){
				$user_ids[] = $user_id;
			}
		}

		return $user_ids;
	}


	protected function buildExtensionForm($data):\ILIAS\UI\Component\Input\Container\Form\Standard{
		if($id = $this->getWriterId()){
			$section_title = $this->plugin->txt('edit_time_extension');
		}
		else {
			$section_title = $this->plugin->txt('add_time_extension');
		}

		$factory = $this->uiFactory->input()->field();

		$sections = [];

		$fields = [];

		$fields['extension'] = $factory->numeric($this->lng->txt('minutes'), $this->plugin->txt("time_extension_caption"))
			->withRequired(true)
			->withValue($data["extension"]);

		$sections['form'] = $factory->section($fields, $section_title);
		$this->ctrl->saveParameter($this, "writer_id");
		return $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this,"updateExtension"), $sections);
	}

	/**
	 * Edit and save the settings
	 */
	protected function editExtension($form = null)
	{
		if($form === null){


			if ($id = $this->getWriterId())
			{
				$record = $this->getExtension($id);
				$form = $this->buildExtensionForm([
					"extension" => $record->getMinutes()
				]);
			}else {
				// TODO: ERROR
			}
		}

		$this->tpl->setContent($this->renderer->render($form));
	}


	protected function updateExtension(){
		$form = $this->buildExtensionForm([
			"extension" => 0
		]);

		if ($this->request->getMethod() == "POST") {
			$form = $form->withRequest($this->request);
			$data = $form->getData();

			if(($id = $this->getWriterId()) !== null ){
				$record = $this->getExtension($id);
			}else {
				//TODO: ERROR
			}

			$task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
			$settings = $task_repo->getTaskSettingsById($this->object->getId());

			if(isset($data["form"]["extension"])
				&& $settings->getCorrectionStart() !== null
				&& $settings->getWritingEnd() !== null)
			{

				$solution_available = new \ilDateTime($settings->getSolutionAvailableDate(), IL_CAL_DATETIME);
				$writing_end = new \ilDateTime($settings->getWritingEnd(), IL_CAL_DATETIME);
				$extension_date = clone $writing_end;
				$extension_date->increment(\ilDate::MINUTE, $data["form"]["extension"]);

				if(!\ilDate::_before($extension_date, $solution_available)){
					ilUtil::sendFailure($this->plugin->txt("time_exceeds_solution_availability"), false);
					$this->editExtension($form);
					return;
				}
			}

			// inputs are ok => save data
			if (isset($data)) {
				$record->setMinutes($data["form"]["extension"]);
				$obj_repo  = LongEssayAssessmentDI::getInstance()->getWriterRepo();

				if($record->getMinutes() === 0){
					$obj_repo->deleteTimeExtension($record->getWriterId(), $record->getTaskId());
				}elseif($record->getId() !== 0){
					$obj_repo->updateTimeExtension($record);
				}else {
					$obj_repo->createTimeExtension($record);
				}

				$this->createExtensionLogEntry($record);

				ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
				$this->ctrl->redirect($this, "showStartPage", "writer_" . $id);
			}else {
				ilUtil::sendFailure($this->lng->txt("validation_error"), false);
				$this->editExtension($form);
			}
		}
	}

	protected function authorizeWriting(){
		global $DIC;

		if (($id = $this->getWriterId()) === null){
			ilUtil::sendSuccess($this->plugin->txt('writing_autorized'), true);
			$this->ctrl->redirect($this, "showStartPage");
		}

		$essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
		$essay = $essay_repo->getEssayByWriterIdAndTaskId($id, $this->object->getId());

		if($essay === null){
			throw new Exception("No Essay found for writer.");
		}

		$datetime = new \ilDateTime(time(), IL_CAL_UNIX);
		$essay->setWritingAuthorized($datetime->get(IL_CAL_DATETIME));
		$essay->setWritingAuthorizedBy($DIC->user()->getId());

		$essay_repo->updateEssay($essay);

		$this->createAuthorizeLogEntry($essay);

		ilUtil::sendSuccess($this->plugin->txt('writing_authorized'), true);
		$this->ctrl->redirect($this, "showStartPage", "writer_" . $id);
	}

	protected function deleteWriterData(){
		$essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
		$corr_repo = LongEssayAssessmentDI::getInstance()->getCorrectorRepo();

		$essay_repo->deleteEssayByTaskId($this->object->getId());
		$writer_repo->deleteWriterByTaskId($this->object->getId());
		$task_repo->deleteLogEntryByTaskId($this->object->getId());
		$task_repo->deleteAlertByTaskId($this->object->getId());
		$corr_repo->deleteCorrectorAssignmentByTask($this->object->getId());


		/* TODO: Besprechen ob manuell hinzugefügte Teilnehmer bei nicht selbständigem Start erhalten bleiben sollen.
		$object_repo = LongEssayAssessmentDI::getInstance()->getObjectRepo();
		$settings = $object_repo->getObjectSettingsById($this->object->getId());

		// when self participation is active also delete all writer
		if($settings->getParticipationType() === ObjectSettings::PARTICIPATION_TYPE_INSTANT){
			$writer_repo->deleteWriterByTaskId($this->object->getId());
		}*/

		ilUtil::sendSuccess($this->plugin->txt("delete_writer_data_success"), true);
		$this->ctrl->redirect($this, "showStartPage");
	}

	protected function getExtension(int $writer_id): ?TimeExtension
	{
		$writer_repo  = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$record = $writer_repo->getTimeExtensionByWriterId($writer_id, $this->object->getId());

		if(!$record){
			return (new TimeExtension())->setWriterId($writer_id)->setTaskId($this->object->getId());
		}

		return $record;
	}

	private function createAuthorizeLogEntry(Essay $essay){
		global $DIC;

		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
		$writer = $writer_repo->getWriterById($essay->getWriterId());

		$lng = $DIC->language();
		$description = \ilLanguage::_lookupEntry(
			$lng->getDefaultLanguage(),
			$this->plugin->getPrefix(),
			$this->plugin->getPrefix() . "_writing_authorized_log_description"
		);
		$names = \ilUserUtil::getNamePresentation([$writer->getUserId(), $essay->getWritingAuthorizedBy()], false, false, "", true);

		$log_entry = new LogEntry();
		$log_entry->setEntry(sprintf($description, $names[$writer->getUserId()] ?? "unknown", $names[$essay->getWritingAuthorizedBy()] ?? "unknown"))
			->setTaskId($this->object->getId())
			->setTimestamp($essay->getWritingAuthorized())
			->setCategory(LogEntry::CATEGORY_AUTHORIZE);

		$task_repo->createLogEntry($log_entry);
	}

	private function createExtensionLogEntry(TimeExtension $time_extension){
		global $DIC;

		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
		$writer = $writer_repo->getWriterById($time_extension->getWriterId());

		$lng = $DIC->language();
		$description = \ilLanguage::_lookupEntry(
			$lng->getDefaultLanguage(),
			$this->plugin->getPrefix(),
			$this->plugin->getPrefix() . "_time_extension_log_description"
		);

		$datetime = new \ilDateTime(time(), IL_CAL_UNIX);
		$names = \ilUserUtil::getNamePresentation([$writer->getUserId(), $DIC->user()->getId()], false, false, "", true);

		$log_entry = new LogEntry();
		$log_entry->setEntry(sprintf($description, $names[$writer->getUserId()] ?? "unknown", $names[$DIC->user()->getId()] ?? "unknown", $time_extension->getMinutes()))
			->setTaskId($this->object->getId())
			->setTimestamp($datetime->get(IL_CAL_DATETIME))
			->setCategory(LogEntry::CATEGORY_EXTENSION);

		$task_repo->createLogEntry($log_entry);
	}

	private function createExclusionLogEntry(Writer $writer){
		global $DIC;
		$task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();

		$lng = $DIC->language();

		$description = \ilLanguage::_lookupEntry(
			$lng->getDefaultLanguage(),
			$this->plugin->getPrefix(),
			$this->plugin->getPrefix() . "_writer_exclusion_log_description"
		);

		$datetime = new \ilDateTime(time(), IL_CAL_UNIX);
		$names = \ilUserUtil::getNamePresentation([$writer->getUserId(), $DIC->user()->getId()], false, false, "", true);

		$log_entry = new LogEntry();
		$log_entry->setEntry(sprintf($description, $names[$writer->getUserId()] ?? "unknown", $names[$DIC->user()->getId()] ?? "unknown"))
			->setTaskId($this->object->getId())
			->setTimestamp($datetime->get(IL_CAL_DATETIME))
			->setCategory(LogEntry::CATEGORY_EXCLUSION);

		$task_repo->createLogEntry($log_entry);
	}

	private function createExclusionRepealLogEntry(Writer $writer){
		global $DIC;
		$task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();

		$lng = $DIC->language();

		$description = \ilLanguage::_lookupEntry(
			$lng->getDefaultLanguage(),
			$this->plugin->getPrefix(),
			$this->plugin->getPrefix() . "_writer_exclusion_repeal_log_description"
		);

		$datetime = new \ilDateTime(time(), IL_CAL_UNIX);
		$names = \ilUserUtil::getNamePresentation([$writer->getUserId(), $DIC->user()->getId()], false, false, "", true);

		$log_entry = new LogEntry();
		$log_entry->setEntry(sprintf($description, $names[$writer->getUserId()] ?? "unknown", $names[$DIC->user()->getId()] ?? "unknown"))
			->setTaskId($this->object->getId())
			->setTimestamp($datetime->get(IL_CAL_DATETIME))
			->setCategory(LogEntry::CATEGORY_EXCLUSION);

		$task_repo->createLogEntry($log_entry);
	}

	private function buildDeleteWriterDataModal()
	{
		return $this->uiFactory->modal()->interruptive(
			$this->plugin->txt("delete_writer_data"),
			$this->plugin->txt("delete_writer_data_confirmation"),
			$this->ctrl->getLinkTarget($this, "deleteWriterData")
		)->withActionButtonLabel("remove");
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
}
