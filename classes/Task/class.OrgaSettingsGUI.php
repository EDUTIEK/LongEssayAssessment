<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use \ilPropertyFormGUI;
use \ilTextInputGUI;
use \ilCheckboxInputGUI;
use \ilUtil;

/**
 * Class OrgaSettingsGUI
 *
 * @package ILIAS\Plugin\LongEssayTask\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\Task\OrgaSettingsGUI: ilObjLongEssayTaskGUI
 */
class OrgaSettingsGUI extends BaseGUI
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
            case "saveSettings":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Edit Properties. This commands uses the form class to display an input form.
     */
    protected function editSettings()
    {
        $form = $this->initPropertiesForm();
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * @return ilPropertyFormGUI
     */
    protected function initPropertiesForm() {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->lng->txt("settings"));

        $title = new ilTextInputGUI($this->plugin->txt("title"), "title");
        $title->setRequired(true);
        $title->setValue($this->object->getTitle());
        $form->addItem($title);

        $description = new ilTextInputGUI($this->plugin->txt("description"), "description");
        $description->setValue($this->object->getDescription());
        $form->addItem($description);

        // items will already have the param values
        $online = new ilCheckboxInputGUI($this->lng->txt('online'), 'online');
        $online->setChecked($this->object->isOnline());
        $form->addItem($online);

        $form->setFormAction($this->ctrl->getFormAction($this, "saveSettings"));
        $form->addCommandButton("saveSettings", $this->lng->txt("update"));

        return $form;
    }

    /**
     * Save the Object Properties
     */
    protected function saveSettings()
    {
        $form = $this->initPropertiesForm();
        $form->setValuesByPost();
        if ($form->checkInput()) {

            $this->object->setTitle($form->getInput('title'));
            $this->object->setDescription($form->getInput('description'));
            $this->object->setOnline($form->getInput('online'));
            $this->object->update();

            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }
        $this->tpl->setContent($form->getHTML());
    }


}