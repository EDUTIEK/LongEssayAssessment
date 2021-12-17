<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\ObjectSettings;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
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
        $di = LongEssayTaskDI::getInstance();
        $task_repo = $di->getTaskRepo();
        $taskSettings = $task_repo->getTaskSettingsById($this->object->getId());

        $form = $this->buildTaskSettings($taskSettings);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
            $result = $form->getInputGroup()->getContent();

            if ($result->isOK()) {
                $this->updateTaskSettings($data, $taskSettings);

                ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
                $this->ctrl->redirect($this, "editSettings");
            }else{
                // TODO: Add or search lang var
                ilUtil::sendFailure($this->lng->txt("validation_error"), true);
            }
        }
        $this->tpl->setContent($this->renderer->render($form));
    }

    /**
     * Update TaskSettings
     *
     * @param array $a_data
     * @param TaskSettings $a_task_settings
     * @return void
     */
    protected function updateTaskSettings(array $a_data, TaskSettings $a_task_settings)
    {
        $di = LongEssayTaskDI::getInstance();
        $task_repo = $di->getTaskRepo();

        $this->object->setTitle($a_data['object']['title']);
        $this->object->setDescription($a_data['object']['description']);
        $this->object->setOnline($a_data['object']['online']);
        $this->object->setParticipationType($a_data['object']['participation_type']);
        $this->object->update();

        $date = $a_data['task']['writing_start'];
        $a_task_settings->setWritingStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
        $date = $a_data['task']['writing_end'];
        $a_task_settings->setWritingEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
        $date = $a_data['task']['correction_start'];
        $a_task_settings->setCorrectionStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
        $date = $a_data['task']['correction_end'];
        $a_task_settings->setCorrectionEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
        $date = $a_data['task']['review_start'];
        $a_task_settings->setReviewStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
        $date = $a_data['task']['review_end'];
        $a_task_settings->setReviewEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);

        $task_repo->updateTaskSettings($a_task_settings);
    }

    /**
     * Build TaskSettings Form
     *
     * @param TaskSettings $taskSettings
     * @return \ILIAS\UI\Component\Input\Container\Form\Standard
     */
    protected function buildTaskSettings(TaskSettings $taskSettings): \ILIAS\UI\Component\Input\Container\Form\Standard
    {
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
        return $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }
}