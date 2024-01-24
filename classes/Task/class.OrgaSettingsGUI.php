<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Location;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\UI\Component\Input\Container\Form\Standard;

/**
 * Organisational Settings
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\OrgaSettingsGUI: ilObjLongEssayAssessmentGUI
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
        switch ($cmd) {
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
        $di = LongEssayAssessmentDI::getInstance();
        $task_repo = $di->getTaskRepo();
        $taskSettings = $task_repo->getTaskSettingsById($this->object->getId());
        $locations = $task_repo->getLocationsByTaskId($this->object->getId());
        $form = $this->buildTaskSettings($taskSettings, $locations);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
            $result = $form->getInputGroup()->getContent();

            if ($result->isOK()) {
                $this->updateTaskSettings($data, $taskSettings, $locations);

                $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
                $this->ctrl->redirect($this, "editSettings");
            }
        }
        $this->tpl->setContent($this->renderer->render($form));
    }

    /**
     * Update TaskSettings
     *
     * @param array $a_data
     * @param TaskSettings $a_task_settings
     * @param Location[] $locations
     * @return void
     */
    protected function updateTaskSettings(array $a_data, TaskSettings $a_task_settings, array $locations)
    {
        // ilUtil::sendInfo('<pre>'.print_r($a_data, true) .'<pre>', true);
        $di = LongEssayAssessmentDI::getInstance();
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

        $task_description = $a_data['content']['task_description'];
        $a_task_settings->setDescription((string)$task_description);

        $closing_message = $a_data['content']['closing_message'];
        $a_task_settings->setClosingMessage((string)$closing_message);

        $task_repo->save($a_task_settings);
        $this->saveLocations($a_data['task']['location'], $locations);
    }

    /**
     * Build TaskSettings Form
     *
     * @param TaskSettings $taskSettings
     * @param Location[] $locations
     * @return Standard
     */
    protected function buildTaskSettings(TaskSettings $taskSettings, array $locations): Standard
    {
        $factory = $this->uiFactory->input()->field();
        $ui_service = $this->localDI->getUIService();

        $sections = [];

        // Object
        $fields = [];
        $fields['title'] = $factory->text($this->lng->txt("title"))
            ->withRequired(true)
            ->withValue($this->object->getTitle());

        $fields['description'] = $factory->textarea($this->lng->txt("description"))
            ->withValue($this->object->getDescription())
            ->withAdditionalOnLoadCode($ui_service->noRTEOnloadCode());// Exclude from RTE

        $fields['online'] = $factory->checkbox($this->lng->txt('online'))
            ->withValue($this->object->isOnline());

        $fields['participation_type'] = $factory->radio($this->plugin->txt('participation_type'))
            ->withOption(
                ObjectSettings::PARTICIPATION_TYPE_FIXED,
                $this->plugin->txt('participation_type_fixed'),
                $this->plugin->txt('participation_type_fixed_info')
            )
            ->withOption(
                ObjectSettings::PARTICIPATION_TYPE_INSTANT,
                $this->plugin->txt('participation_type_instant'),
                $this->plugin->txt('participation_type_instant_info')
            )
            ->withValue($this->object->getParticipationType());

        $sections['object'] = $factory->section($fields, $this->plugin->txt('object_settings'));


        $fields = [];

        $fields['task_description'] = $this->localDI->getUIFactory()->field()
            ->textareaModified($this->plugin->txt("task_description"), $this->plugin->txt("task_description_info"))
            ->withValue($taskSettings->getDescription() ?? "")
            ->withAdditionalTransformation($ui_service->stringTransformationByRTETagSet());

        $fields['closing_message'] = $this->localDI->getUIFactory()->field()
            ->textareaModified($this->plugin->txt("closing_message"), $this->plugin->txt("closing_message_info"))
            ->withValue($taskSettings->getClosingMessage() ?? "")
            ->withAdditionalOnLoadCode(function ($id) use ($ui_service) {
                $ui_service->addTinyMCEToTextareas();// delay TinyMCE onload code so that description can add its noRTE-Tag before
                return "";
            })
            ->withAdditionalTransformation($ui_service->stringTransformationByRTETagSet());
        ;

        $sections['content'] = $factory->section($fields, $this->plugin->txt('content'));

        // Task
        $fields = [];

        $fields['writing_start'] = $factory->dateTime(
            $this->plugin->txt("writing_start"),
            $this->plugin->txt("writing_start_info")
        )
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getWritingStart());

        $fields['writing_end'] = $factory->dateTime(
            $this->plugin->txt("writing_end"),
            $this->plugin->txt("writing_end_info")
        )
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getWritingEnd());

        $fields['location'] =  $factory->tag(
            $this->plugin->txt("locations"),
            $this->localDI->getTaskRepo()->getLocationExamples(),
            $this->plugin->txt("locations_info")
        )
            ->withTagMaxLength(255)
            ->withValue($this->getLocationStrList($locations));

        $fields['keep_essay_available'] = $factory->checkbox(
            $this->plugin->txt('keep_essay_available'),
            $this->plugin->txt('keep_essay_available_info')
        )
            ->withValue($taskSettings->getKeepEssayAvailable());


        $fields['solution_available'] = $factory->optionalGroup(
            [
            'solution_available_date' => $factory->dateTime(
                $this->plugin->txt("solution_available_date"),
                $this->plugin->txt("solution_available_date_info")
            )
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
            $this->plugin->txt("correction_start_info")
        )
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getCorrectionStart());

        $fields['correction_end'] = $factory->dateTime(
            $this->plugin->txt("correction_end"),
            $this->plugin->txt("correction_end_info")
        )
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getCorrectionEnd());


        $fields['result_available_type'] = $factory->switchableGroup(
            [
                TaskSettings::RESULT_AVAILABLE_FINALISED => $factory->group(
                    [],
                    $this->plugin->txt('result_available_finalised'),
                ),
                TaskSettings::RESULT_AVAILABLE_REVIEW => $factory->group(
                    [],
                    $this->plugin->txt('result_available_review'),
                ),
                TaskSettings::RESULT_AVAILABLE_DATE => $factory->group(
                    [
                    'result_available_date' =>  $factory->dateTime(
                        $this->plugin->txt("result_available_date"),
                        $this->plugin->txt('result_available_date_info')
                    )
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
            $this->plugin->txt("review_start_info")
        )
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getReviewStart());

        $fields['review_end'] = $factory->dateTime(
            $this->plugin->txt("review_end"),
            $this->plugin->txt("review_end_info")
        )
            ->withUseTime(true)
            ->withValue((string) $taskSettings->getReviewEnd());

        $sections['task'] = $factory->section($fields, $this->plugin->txt('task_settings'));
        return $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }

    private function getLocationStrList(array $locations)
    {
        return array_values(array_map(fn (Location $x) => $x->getTitle(), $locations));
    }

    private function saveLocations(array $input_strs, array $saved_objs)
    {
        $task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
        $saved_strs = $this->getLocationStrList($saved_objs);

        foreach($saved_objs as $saved) {
            if(!in_array($saved->getTitle(), $input_strs)) {
                $task_repo->deleteLocation($saved->getId());
            }
        }

        foreach($input_strs as $input) {
            if(!in_array($input, $saved_strs)) {
                $task_repo->save(Location::model()->setTaskId($this->object->getId())->setTitle($input));
            }
        }
    }
}
