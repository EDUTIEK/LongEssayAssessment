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
        // ilUtil::sendInfo('<pre>'.print_r($a_data, true) .'<pre>', true);
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

        $a_task_settings->setKeepEssayAvailable((bool) ($a_data['task']['keep_essay_available']));

        $date = null;
        $a_task_settings->setSolutionAvailable(!empty($a_data['task']['solution_available']));
        if ($a_task_settings->isSolutionAvailable()) {
            $date = $a_data['task']['solution_available']['solution_available_date'];
        }
        $a_task_settings->setSolutionAvailableDate($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);

        $date = $a_data['task']['correction_start'];
        $a_task_settings->setCorrectionStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
        $date = $a_data['task']['correction_end'];
        $a_task_settings->setCorrectionEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);

        $date = null;
        $a_task_settings->setResultAvailableType((string) ($a_data['task']['result_available_type'][0] ?? TaskSettings::RESULT_AVAILABLE_REVIEW));
        if ($a_task_settings->getResultAvailableType() == TaskSettings::RESULT_AVAILABLE_DATE) {
            // note: the type differs from the other dates due to the nesting in the selectable group
            $date = $a_data['task']['result_available_type'][1]['result_available_date'];
        }
        $a_task_settings->setResultAvailableDate($date);

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

        $fields['writing_start'] = $factory->dateTime(
            $this->plugin->txt("writing_start"),
            $this->plugin->txt("writing_start_info"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getWritingStart());

        $fields['writing_end'] = $factory->dateTime(
            $this->plugin->txt("writing_end"),
            $this->plugin->txt("writing_end_info"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getWritingEnd());

        $fields['keep_essay_available'] = $factory->checkbox(
            $this->plugin->txt('keep_essay_available'),
            $this->plugin->txt('keep_essay_available_info'))
            ->withValue($taskSettings->getKeepEssayAvailable());


        $fields['solution_available'] = $factory->optionalGroup([
            'solution_available_date' => $factory->dateTime(
                $this->plugin->txt("solution_available_date"),
                $this->plugin->txt("solution_available_date_info"))
                ->withUseTime(true)
                ->withValue((string) $taskSettings->getSolutionAvailableDate())
        ],
        $this->plugin->txt('solution_available'),
        $this->plugin->txt('solution_available_info')
        );
        // strange but effective
        if (!$taskSettings->isSolutionAvailable()) {
            $fields['solution_available'] = $fields['solution_available']->withValue(null);
        }

        $fields['correction_start'] = $factory->dateTime(
            $this->plugin->txt("correction_start"),
            $this->plugin->txt("correction_start_info"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getCorrectionStart());

        $fields['correction_end'] = $factory->dateTime(
            $this->plugin->txt("correction_end"),
            $this->plugin->txt("correction_end_info"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getCorrectionEnd());


        $fields['result_available_type'] = $factory->switchableGroup([
                TaskSettings::RESULT_AVAILABLE_FINALISED => $factory->group([],
                        $this->plugin->txt('result_available_finalised'),
                    ),
                TaskSettings::RESULT_AVAILABLE_REVIEW => $factory->group([],
                        $this->plugin->txt('result_available_review'),
                    ),
                TaskSettings::RESULT_AVAILABLE_DATE => $factory->group([
                    'result_available_date' =>  $factory->dateTime(
                        $this->plugin->txt("result_available_date"),
                        $this->plugin->txt('result_available_date_info'))
                        ->withUseTime(true)
                        ->withValue((string) $taskSettings->getResultAvailableDate())
                        ],
                        $this->plugin->txt('result_available_after')
                    )
            ],
            $this->plugin->txt('result_available_type'),
            $this->plugin->txt('result_available_type_info'),
        )->withValue($taskSettings->getResultAvailableType());

        $fields['review_start'] = $factory->dateTime(
            $this->plugin->txt("review_start"),
            $this->plugin->txt("review_start_info"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getReviewStart());

        $fields['review_end'] = $factory->dateTime(
            $this->plugin->txt("review_end"),
            $this->plugin->txt("review_end_info"))
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getReviewEnd());

        $sections['task'] = $factory->section($fields, $this->plugin->txt('task_settings'));
        return $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }
}