<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\UI\Component\Input\Container\Form\Form;

/**
 * Definition of the task solution
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\SolutionSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class SolutionSettingsGUI extends BaseGUI
{
	/**
	 * Execute a command
	 * This should be overridden in the child classes
	 * note: permissions are already checked in the object gui
	 */
	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd('editSettings');
		switch ($cmd)
		{
			case "editSettings":
				$this->$cmd();
				break;

			default:
				$this->tpl->setContent('unknown command: ' . $cmd);
		}
	}

	/**
	 * Edit and save the settings
	 * Use classic Property form for richtext editing
	 */
	protected function editSettings()
	{
		$di = LongEssayAssessmentDI::getInstance();
		$task_repo = $di->getTaskRepo();
		$taskSettings = $task_repo->getTaskSettingsById($this->object->getId());
		$resource = $task_repo->getSolutionResource($this->object->getId());

		if(!$taskSettings->isSolutionAvailable())
		{
            $this->tpl->setOnScreenMessage("info", $this->lng->txt("solution_is_not_available"), false);
		}

		$form = $this->buildSolutionSettings($taskSettings, $resource);

		if($this->request->getMethod() === "POST"){
			$form = $form->withRequest($this->request);

			if (($data = $form->getData()) !== null) {
				$this->updateSolutionSettings($data["form"], $taskSettings, $resource);

                $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
                $this->ctrl->redirect($this, "editSettings");
			}
		}
		$this->tpl->setContent($this->renderer->render($form));
	}

	/**
	 * Update ContentSettings
	 *
	 * @param array $a_data
	 * @param \ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings $a_task_settings
	 * @return void
	 */
	protected function updateSolutionSettings(array $a_data, TaskSettings $a_task_settings, ?Resource $resource)
	{
		$di = LongEssayAssessmentDI::getInstance();
		$task_repo = $di->getTaskRepo();

		$a_task_settings->setSolution($a_data["task_solution"]);


		if($resource !== null && isset($a_data["resource_file"][0]))
		{
			$resource->setFileId($a_data["resource_file"][0]);
			$task_repo->save($resource);
		}elseif ($resource === null && isset($a_data["resource_file"][0])){
			$task_repo->save(
				(new Resource())
					->setTaskId($this->object->getId())
					->setType(Resource::RESOURCE_TYPE_SOLUTION)
                    ->setAvailability(Resource::RESOURCE_AVAILABILITY_AFTER)
					->setFileId($a_data["resource_file"][0])
			);
		}elseif($resource !== null && !isset($a_data["resource_file"][0])){
			$task_repo->deleteResource($resource->getId());
		}

		$task_repo->save($a_task_settings);
	}

	/**
	 * Build TaskSettings Form
	 *
	 * @param TaskSettings $taskSettings
	 * @return Form
	 */
	protected function buildSolutionSettings(TaskSettings $taskSettings, ?Resource $resource): Form
	{
		$factory = $this->uiFactory->input()->field();
		$ui_service = $this->localDI->getUIService();

		$sections = [];

		// Object
		$fields = [];

		$fields['task_solution'] = $this->localDI->getUIFactory()->field()
			->textareaModified($this->plugin->txt("task_solution"),$this->plugin->txt("task_solution_info"))
			->withValue($taskSettings->getSolution() ?? "")
			->withAdditionalTransformation($ui_service->stringTransformationByRTETagSet());

		$fields['resource_file'] = $factory->file(new ResourceUploadHandlerGUI($this->dic->resourceStorage(),
			$this->localDI->getTaskRepo()), "",
			$this->plugin->txt("task_solution_file_info") . "<br>" . $ui_service->getMaxFileSizeString())
			->withAcceptedMimeTypes(['application/pdf'])
			->withValue($resource !== null && $resource->getFileId() !== null ? [$resource->getFileId()] : []);

		$sections['form'] = $factory->section($fields, $this->plugin->txt('tab_solution_settings'));

		$ui_service->addTinyMCEToTextareas();

		return $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
	}
}