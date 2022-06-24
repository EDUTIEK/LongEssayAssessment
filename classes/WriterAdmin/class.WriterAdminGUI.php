<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use ILIAS\DI\Exceptions\Exception;
use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\GradeLevel;
use ILIAS\Plugin\LongEssayTask\Data\TimeExtension;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use \ilUtil;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayTask\WriterAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\WriterAdmin\WriterAdminGUI: ilObjLongEssayTaskGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayTask\WriterAdmin\WriterAdminGUI: ilRepositorySearchGUI
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
					case 'deleteWriter':
					case 'editExtension':
					case 'updateExtension':
					case 'authorizeWriting':
						$this->$cmd();
						break;

					default:
						$this->tpl->setContent('unknown command: ' . $cmd);
				}
		}
    }


	private function showToolbar(){

		\ilRepositorySearchGUI::fillAutoCompleteToolbar(
			$this,
			$this->toolbar,
			array()
		);

		// spacer
		$this->toolbar->addSeparator();

		// search button
		$this->toolbar->addButton(
			$this->plugin->txt("search_participants"),
			$this->ctrl->getLinkTargetByClass(
				'ilRepositorySearchGUI',
				'start'
			)
		);
	}

    /**
     * Show the items
     */
    protected function showStartPage()
    {
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
		$this->showToolbar();

		$writer_repo = LongEssayTaskDI::getInstance()->getWriterRepo();
		$essay_repo = LongEssayTaskDI::getInstance()->getEssayRepo();

		$list_gui = new WriterAdminListGUI($this, "showStartPage", $this->plugin);
		$list_gui->setWriters($writer_repo->getWritersByTaskId($this->object->getId()));
		$list_gui->setExtensions($writer_repo->getTimeExtensionsByTaskId($this->object->getId()));
		$list_gui->setEssays($essay_repo->getEssaysByTaskId($this->object->getId()));
		$list_gui->setHistory($essay_repo->getLastWriterHistoryPerUserByTaskId($this->object->getId()));

        $this->tpl->setContent($list_gui->getContent());
     }

	private function deleteWriter(){
		if(($id = $this->getWriterId()) === null)
		{
			ilUtil::sendFailure($this->plugin->txt("missing_writer_id"), true);
			$this->ctrl->redirect($this, "showStartPage");
		}
		$writer_repo = LongEssayTaskDI::getInstance()->getWriterRepo();
		$writer = $writer_repo->getWriterById($id);

		if($writer === null || $writer->getTaskId() !== $this->object->getId()){
			ilUtil::sendFailure($this->plugin->txt("missing_writer"), true);
			$this->ctrl->redirect($this, "showStartPage");
		}

		$writer_repo->deleteWriter($writer->getId());
		ilUtil::sendSuccess($this->plugin->txt("remove_writer_success"), true);
		$this->ctrl->redirect($this, "showStartPage");
	}

	public function assignWriters(array $a_usr_ids, $a_type = null)
	{
		if (count($a_usr_ids) <= 0) {
			ilUtil::sendFailure($this->plugin->txt("no_writer_set"), true);
			$this->ctrl->redirect($this,"showStartPage");
		}

		foreach($a_usr_ids as $id){
			$writer_repo = LongEssayTaskDI::getInstance()->getWriterRepo();

			$writer = new Writer();
			$writer->setTaskId($this->object->getId())
			 ->setUserId((int)$id)
			 ->setPseudonym("participant ".$id);

			$writer_repo->createWriter($writer);
		}

		ilUtil::sendSuccess($this->plugin->txt("assign_writer_success"), true);
		$this->ctrl->redirect($this,"showStartPage");
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
		$writer_repo = LongEssayTaskDI::getInstance()->getWriterRepo();
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

		$fields['extension'] =$factory->numeric($this->lng->txt('minutes'), $this->plugin->txt("time_extension_caption"))
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

			// inputs are ok => save data
			if (isset($data)) {
				$record->setMinutes($data["form"]["extension"]);
				$obj_repo  = LongEssayTaskDI::getInstance()->getWriterRepo();

				if($record->getMinutes() === 0){
					$obj_repo->deleteTimeExtension($record->getWriterId(), $record->getTaskId());
				}elseif($record->getId() !== 0){
					$obj_repo->updateTimeExtension($record);
				}else {
					$obj_repo->createTimeExtension($record);
				}

				ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
				$this->ctrl->redirect($this, "showStartPage");
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

		$essay_repo = LongEssayTaskDI::getInstance()->getEssayRepo();
		$essay = $essay_repo->getEssayByWriterIdAndTaskId($id, $this->object->getId());

		if($essay === null){
			throw new Exception("No Essay found for writer.");
		}

		$datetime = new \ilDateTime(time(), IL_CAL_UNIX);
		$essay->setWritingAuthorized($datetime->get(IL_CAL_DATETIME));
		$essay->setWritingAuthorizedBy($DIC->user()->getId());

		$essay_repo->updateEssay($essay);

		ilUtil::sendSuccess($this->plugin->txt('writing_autorized'), true);
		$this->ctrl->redirect($this, "showStartPage");
	}

	protected function getExtension(int $writer_id): ?TimeExtension
	{
		$writer_repo  = LongEssayTaskDI::getInstance()->getWriterRepo();
		$record = $writer_repo->getTimeExtensionByWriterId($writer_id, $this->object->getId());

		if(!$record){
			return (new TimeExtension())->setWriterId($writer_id)->setTaskId($this->object->getId());
		}

		return $record;
	}
}