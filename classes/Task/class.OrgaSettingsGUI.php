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
        $factory = $this->uiFactory->input()->field();
        $fields = [];

        $fields['title'] = $factory->text($this->plugin->txt("title"))
            ->withRequired(true)
            ->withValue($this->object->getTitle());

        $fields['description'] = $factory->textarea($this->plugin->txt("title"))
            ->withValue($this->object->getDescription());

        $fields['online'] = $factory->checkbox($this->lng->txt('online'))
            ->withValue($this->object->isOnline());

        $form = $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $fields);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {
            $this->object->setTitle($data['title']);
            $this->object->setDescription($data['description']);
            $this->object->setOnline($data['online']);
            $this->object->update();

            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}