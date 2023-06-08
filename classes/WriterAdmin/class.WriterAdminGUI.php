<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\DI\Exceptions\Exception;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Location;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\TimeExtension;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
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
					case 'editLocationMulti':
					case 'editLocation':
					case 'updateLocation':
					case 'showEssay':
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
    protected function showStartPage(Roundtrip $multi_modal = null)
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
		$task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();

		$list_gui = new WriterAdminListGUI($this, "showStartPage", $this->plugin);
		$list_gui->setWriters($writer_repo->getWritersByTaskId($this->object->getId()));
		$list_gui->setExtensions($writer_repo->getTimeExtensionsByTaskId($this->object->getId()));
		$list_gui->setEssays($essay_repo->getEssaysByTaskId($this->object->getId()));
		$list_gui->setLocations($task_repo->getLocationsByTaskId($this->object->getId()));

		if($multi_modal !== null){
			$list_gui->setMultiCommandModal($multi_modal);
		}

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
			$essay_repo->save($essay);
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
		$essay_repo->save($essay);
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
				}else {
					$obj_repo->save($record);
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

		$essay_repo->save($essay);

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

		$task_repo->save($log_entry);
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

		$task_repo->save($log_entry);
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

		$task_repo->save($log_entry);
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

		$task_repo->save($log_entry);
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

	protected function buildLocationForm($value = null): BlankForm
	{
		$task_repo = $this->localDI->getTaskRepo();
		$locations = $task_repo->getLocationsByTaskId($this->object->getId());
		$options = [];
		foreach ($locations as $location){
			$options[$location->getId()] = $location->getTitle();
		}
		$location_input = $this->uiFactory->input()->field()->select($this->plugin->txt("location"), $options);

		if($value !== null){
			$location_input = $location_input->withValue($value);
		}
		return $this->localDI->getUIFactory()->field()->blankForm(
			$this->ctrl->getFormAction($this, "updateLocation"),
			["location" => $location_input]
		);
	}

	protected function updateLocation(){
		$form = $this->buildLocationForm()->withRequest($this->request);

		if($data = $form->getData()){
			$essay_repo = $this->localDI->getEssayRepo();
			$task_repo = $this->localDI->getTaskRepo();
			$location = $task_repo->ifLocationExistsById((int)$data["location"]) ? (int)$data["location"] : null;

			$essays = $this->getEssaysFromWriterIds();

			foreach($essays as $writer_id => $essay){
				if($essay === null) {
					$essay = Essay::model();
					$essay->setTaskId($this->object->getId())
						->setWriterId($writer_id)
						->setUuid($essay->generateUUID4())
						->setRawTextHash('');;
				}
				$essay_repo->save($essay->setLocation($location));
			}

			ilUtil::sendSuccess($this->plugin->txt("location_assigned"), true);
			$this->ctrl->setParameter($this, "writer_id", "");
			$this->ctrl->setParameter($this, "writer_ids", "");
			$this->ctrl->redirect($this, "showStartPage");
		}else
		{
			$this->showStartPage(
				$this->uiFactory->modal()->roundtrip($this->plugin->txt("assign_location"), $form)->withActionButtons([
					$this->uiFactory->button()->primary($this->lng->txt("submit"), "")->withOnClick($form->getSubmitSignal())
				])
			);
		}
	}

	protected function editLocationMulti(){
		$this->ctrl->saveParameter($this, "writer_ids");
		$form = $this->buildLocationForm();
		$modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("assign_location"), $form)->withActionButtons([
			$this->uiFactory->button()->primary($this->lng->txt("submit"), "")->withOnClick($form->getSubmitSignal())
		]);
		echo($this->renderer->renderAsync($modal));
		exit();
	}

	protected function editLocation()
	{
		$essays = $this->getEssaysFromWriterIds();
		$value = count($essays) > 0 && ($essay =  array_pop($essays)) !== null? $essay->getLocation() : null;
		$this->ctrl->saveParameter($this, "writer_id");
		$form = $this->buildLocationForm($value);
		$modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("change_location"), $form)->withActionButtons([
			$this->uiFactory->button()->primary($this->lng->txt("submit"), "")->withOnClick($form->getSubmitSignal())
		]);
		echo($this->renderer->renderAsync($modal));
		exit();
	}

	/**
	 * @return Essay[]
	 */
	protected function getEssaysFromWriterIds(): array
	{
		$ids = [];
		$query_params = $this->request->getQueryParams();
		$essay_repo = $this->localDI->getEssayRepo();

		if(isset($query_params["writer_id"]) && $query_params["writer_id"] !== ""){
			$ids[] = (int) $query_params["writer_id"];
		}elseif (isset($query_params["writer_ids"])){
			foreach(explode('/', $query_params["writer_ids"]) as $value){
				$ids[] = (int) $value;
			}
		}
		$essays = [];
		foreach ($essay_repo->getEssaysByTaskId($this->object->getId()) as $essay){
			$essays[$essay->getWriterId()] = $essay;
		}
		$ret = [];

		foreach($ids as $id){
			$ret[$id] = $essays[$id] ?? null;
		}

		return $ret;
	}

	protected function showEssay()
	{
		$essays = $this->getEssaysFromWriterIds();
		$value = count($essays) > 0 && ($essay =  array_pop($essays)) !== null? $essay->getWrittenText() : null;

		$this->ctrl->saveParameter($this, "writer_id");
		$link = $this->ctrl->getFormAction($this, "showEssay", "", true);

		$sight_modal = $this->uiFactory->modal()->roundtrip($this->plugin->txt("submission"),
			$this->uiFactory->legacy($value ? $this->localDI->getDataService($this->object->getId())->cleanupRichText($value): "")
		);
		$reload_button = $this->uiFactory->button()->standard($this->lng->txt("refresh"), "")->withOnLoadCode(
			function ($id) use ($link) {
				return
					"$('#{$id}').click(function() { 
						n_url = '{$link}';
						il.UI.core.replaceContent($(this).closest('.modal').attr('id'), n_url, 'component');
						return false;
					}
				);";
			}
		);

		echo($this->renderer->renderAsync($sight_modal->withActionButtons([$reload_button])));
		exit();
	}
}
