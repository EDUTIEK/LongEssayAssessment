<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\ObjectSettings;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use \ilUtil;

/**
 * Definition of the task content
 *
 * @package ILIAS\Plugin\LongEssayTask\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\Task\ContentSettingsGUI: ilObjLongEssayTaskGUI
 */
class ContentSettingsGUI extends BaseGUI
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
        $di = LongEssayTaskDI::getInstance();
        $task_repo = $di->getTaskRepo();
        $taskSettings = $task_repo->getTaskSettingsById($this->object->getId());

        $form = $this->buildTaskSettings($taskSettings);

        // save posted form inputs
        if ($this->request->getMethod() == "POST") {

            if ($form->checkInput()) {
                $form->setValuesByPost();

                $this->updateContentSettings($_POST, $taskSettings);

                ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
                $this->ctrl->redirect($this, "editSettings");
            }else{
                // TODO: Add or search lang var
                ilUtil::sendFailure($this->lng->txt("validation_error"), true);
            }
        }

        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Update ContentSettings
     *
     * @param array $a_data
     * @param TaskSettings $a_task_settings
     * @return void
     */
    protected function updateContentSettings(array $a_data, TaskSettings $a_task_settings)
    {
        $di = LongEssayTaskDI::getInstance();
        $task_repo = $di->getTaskRepo();

        $a_task_settings->setDescription($a_data["task_description"]);
        $a_task_settings->setInstructions($a_data["task_instructions"]);
        $a_task_settings->setSolution($a_data["task_solution"]);

        $task_repo->updateTaskSettings($a_task_settings);
    }

    /**
     * Build TaskSettings Form
     *
     * @param TaskSettings $taskSettings
     * @return \ilPropertyFormGUI
     */
    protected function buildTaskSettings(TaskSettings $taskSettings): \ilPropertyFormGUI
    {
        $form = new \ilPropertyFormGUI();

        $item = new \ilTextAreaInputGUI($this->plugin->txt("task_description"), 'task_description');
        $item->setInfo($this->plugin->txt("task_description_info"));
        $item->setUseRte(true);
        $item->setRteTagSet('standard');
        $item->setValue($taskSettings->getInstructions());
        $form->addItem($item);

        $item = new \ilTextAreaInputGUI($this->plugin->txt("task_instructions"), 'task_instructions');
        $item->setInfo($this->plugin->txt("task_instructions_info"));
        $item->setUseRte(true);
        $item->setRteTagSet('standard');
        $item->setValue($taskSettings->getDescription());
        $form->addItem($item);

        $item = new \ilTextAreaInputGUI($this->plugin->txt("task_solution"), 'task_solution');
        $item->setInfo($this->plugin->txt("task_solution_info"));
        $item->setUseRte(true);
        $item->setRteTagSet('standard');
        $item->setValue($taskSettings->getSolution());
        $form->addItem($item);


        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->plugin->txt('task_definition'));
        $form->addCommandButton('editSettings', $this->lng->txt('save'));
        return $form;
    }
}