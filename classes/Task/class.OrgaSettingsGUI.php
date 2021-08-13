<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\ObjectSettings;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
use \ilUtil;

/**
 * Organisational Settings
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
        $taskSettings = TaskSettings::findOrGetInstance($this->object->getId());

        $factory = $this->uiFactory->input()->field();

        $sections = [];

        // Object
        $fields = [];
        $fields['title'] = $factory->text($this->lng->txt("title"))
            ->withRequired(true)
            ->withValue($this->object->getTitle());

        $fields['description'] = $factory->textarea($this->lng->txt("description"))
            ->withValue($this->object->getDescription());

        $fields['online'] = $factory->checkbox($this->lng->txt('online'))
            ->withValue($this->object->isOnline());

        $fields['participation_type'] = $factory->radio($this->plugin->txt('participation_type'))
            ->withOption(ObjectSettings::PARTICIPATION_TYPE_FIXED,
                $this->plugin->txt('participation_type_fixed'),
                $this->plugin->txt('participation_type_fixed_info'))
            ->withOption(ObjectSettings::PARTICIPATION_TYPE_INSTANT,
                $this->plugin->txt('participation_type_instant'),
                $this->plugin->txt('participation_type_instant_info'))
            ->withValue($this->object->getParticipationType());

        $sections['object'] = $factory->section($fields, $this->plugin->txt('object_settings'));

        // Task
        $fields = [];
        $fields['writing_start'] = $factory->dateTime($this->plugin->txt("writing_start"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getWritingStart());

        $fields['writing_end'] = $factory->dateTime($this->plugin->txt("writing_end"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getWritingEnd());

        $fields['correction_start'] = $factory->dateTime($this->plugin->txt("correction_start"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getCorrectionStart());

        $fields['correction_end'] = $factory->dateTime($this->plugin->txt("correction_end"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getCorrectionEnd());

        $fields['review_start'] = $factory->dateTime($this->plugin->txt("review_start"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getReviewStart());

        $fields['review_end'] = $factory->dateTime($this->plugin->txt("review_end"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getReviewEnd());

        $sections['task'] = $factory->section($fields, $this->plugin->txt('task_settings'));

        $form = $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {
            $this->object->setTitle($data['object']['title']);
            $this->object->setDescription($data['object']['description']);
            $this->object->setOnline($data['object']['online']);
            $this->object->setParticipationType($data['object']['participation_type']);
            $this->object->update();

            $date = $data['task']['writing_start'];
            $taskSettings->setWritingStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
            $date = $data['task']['writing_end'];
            $taskSettings->setWritingEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
            $date = $data['task']['correction_start'];
            $taskSettings->setCorrectionStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
            $date = $data['task']['correction_end'];
            $taskSettings->setCorrectionEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
            $date = $data['task']['review_start'];
            $taskSettings->setReviewStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
            $date = $data['task']['review_end'];
            $taskSettings->setReviewEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
            $taskSettings->save();

            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}