<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
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
     */
    protected function editSettings()
    {
        $taskSettings = TaskSettings::findOrGetInstance($this->object->getId());

        $form = new \ilPropertyFormGUI();

        $item = new \ilTextAreaInputGUI($this->plugin->txt("task_description"), 'task_description');
        $item->setInfo($this->plugin->txt("task_description_info"));
        $item->setUseRte(true);
        $item->setRteTagSet('standard');
        $item->setValue($taskSettings->getDescription());
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

        // save posted form inputs
        if ($this->request->getMethod() == "POST") {

            if ($form->checkInput()) {
                $form->setValuesByPost();

                /** @var \ilTextAreaInputGUI $item */
                $item = $form->getItemByPostVar('task_description');
                $taskSettings->setDescription($item->getValue());

                $item = $form->getItemByPostVar('task_instructions');
                $taskSettings->setInstructions($item->getValue());

                $item = $form->getItemByPostVar('task_solution');
                $taskSettings->setSolution($item->getValue());

                $taskSettings->save();

                ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
                $this->ctrl->redirect($this, "editSettings");
            }
        }

        $this->tpl->setContent($form->getHTML());
    }
}