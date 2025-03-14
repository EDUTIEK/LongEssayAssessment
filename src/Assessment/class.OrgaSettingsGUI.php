<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment;

use Edutiek\AssessmentService\Assessment\Data\Location;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\Location\FullService as LocationService;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaSettingsService;
use Edutiek\AssessmentService\EssayTask\Data\WritingType;
use Edutiek\AssessmentService\EssayTask\WritingSettings\FullService as WritingSettingsService;
use ILIAS\Plugin\LongEssayAssessment\Common\BaseGUI;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ilObjLongEssayAssessment;
use ilGlobalTemplateInterface as Gti;

/**
 * Organisational Settings
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Assessment\OrgaSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class OrgaSettingsGUI extends BaseGUI
{
    private OrgaSettingsService $orga_settings_service;
    private LocationService $location_service;
    private WritingSettingsService $writing_settings_service;

    public function __construct(ilObjLongEssayAssessment $object) {
        parent::__construct($object);
        
        $this->orga_settings_service = $this->assessment_api->orgaSettings();
        $this->writing_settings_service = $this->essay_task_api->writingSettings();
        $this->location_service = $this->assessment_api->location();
    }

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
        $orga_settings = $this->orga_settings_service->get();
        $writing_settings = $this->writing_settings_service->get();
        $form = $this->buildForm();

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
            $result = $form->getInputGroup()->getContent();

            if ($result->isOK()) {
                $this->updateSettings($data);
            }
        }
        $this->tpl->setContent($this->renderer->render($form));
        $this->plugin_ui_service->addTinyMCEToTextareas(); // Has to be called last for the noRTEditor Tags to be effective
    }

    /**
     * Update TaskSettings
     *
     * @param array $a_data
     * @param OrgaSettings $orga_settings
     * @param Location[] $locations
     * @return void
     */
    private function updateSettings(array $a_data): bool
    {
        $orga_settings = $this->orga_settings_service->get();
        $writing_settings = $this->writing_settings_service->get();
        
        $this->object->setTitle($a_data['object']['title']);
        $this->object->setDescription($a_data['object']['description']);
        $this->object->setOnline($a_data['object']['online']);

        $orga_settings->setParticipationType($a_data['object']['participation_type']);
        $writing_settings->setWritingType(WritingType::from((string) $a_data['object']['writing_type']));

        $date = $a_data['task']['writing_start'];
        $orga_settings->setWritingStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
        $date = $a_data['task']['writing_end'];
        $orga_settings->setWritingEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);

        $limit = null;
        if (!empty($a_data['task']['writing_limit'])) {
            $limit = (int) ($a_data['task']['writing_limit']['days'] ?? 0) * 24 * 60;
            if ($a_data['task']['writing_limit']['hours_minutes'] instanceof \DateTimeInterface) {
                list($hours, $minutes) = explode(':', $a_data['task']['writing_limit']['hours_minutes']->format('H:i'));
                $limit += (int) $hours * 60 + (int) $minutes;
            }

        }
        $orga_settings->setWritingLimitMinutes($limit > 0 ? $limit : null);

        $orga_settings->setKeepAvailable((bool) ($a_data['task']['keep_essay_available']));

        $date = null;
        $orga_settings->setSolutionAvailable(!empty($a_data['task']['solution_available']));
        if ($orga_settings->getSolutionAvailable()) {
            $date = $a_data['task']['solution_available']['solution_available_date'];
        }
        $orga_settings->setSolutionAvailableDate($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);

        $orga_settings->setStatisticsAvailable((bool) ($a_data['task']['statistics_available']));

        $date = $a_data['task']['correction_start'];
        $orga_settings->setCorrectionStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
        $date = $a_data['task']['correction_end'];
        $orga_settings->setCorrectionEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);

        $date = null;
        $orga_settings->setResultAvailableType((string) ($a_data['task']['result_available_type'][0] ?? TaskSettings::RESULT_AVAILABLE_REVIEW));
        if ($orga_settings->getResultAvailableType() == TaskSettings::RESULT_AVAILABLE_DATE) {
            // note: the type differs from the other dates due to the nesting in the selectable group
            $date = $a_data['task']['result_available_type'][1]['result_available_date'];
        }
        $orga_settings->setResultAvailableDate($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);


        if(!empty($a_data['task']['review'])) {
            $orga_settings->setReviewEnabled(true);
            $date = $a_data['task']['review']['review_start'];
            $orga_settings->setReviewStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
            $date = $a_data['task']['review']['review_end'];
            $orga_settings->setReviewEnd($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);

            if(!empty($a_data['task']['review']['review_notification'])) {
                $orga_settings->setReviewNotification(true);
                $orga_settings->setReviewNotifText($a_data['task']['review']['review_notification']['review_notification_text']);
            } else {
                $orga_settings->setReviewNotification(false);
            }

        } else {
            $orga_settings->setReviewEnabled(false);
        }


        $task_description = $a_data['content']['task_description'];
        $orga_settings->setDescription((string) $this->data->trimRichText($task_description));

        $closing_message = $a_data['content']['closing_message'];
        $orga_settings->setClosingMessage((string)$this->data->trimRichText($closing_message));

        // consistency checks
        $failures = $this->orga_settings_service->validate($orga_settings);

        if (empty($failures)) {
            $this->object->update();
            $this->orga_settings_service->save($orga_settings);
            $this->location_service->saveTitles((array) ($a_data['task']['location'] ?? []));

            $this->tpl->setOnScreenMessage(Gti::MESSAGE_TYPE_SUCCESS, $this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }


        $failures[] = $this->plugin->txt('message_form_not_saved');
        $this->tpl->setOnScreenMessage(Gti::MESSAGE_TYPE_FAILURE, implode('<br>', $failures));
        return false;
    }

    /**
     * Build TaskSettings Form
     */
    private function buildForm(): Standard
    {
        $orga_settings = $this->orga_settings_service->get();
        $writing_settings = $this->writing_settings_service->get();
        $factory = $this->ui_factory->input()->field();

        $sections = [];

        // Object
        $fields_object = [];
        $fields_object['title'] = $factory->text($this->lng->txt("title"))
            ->withRequired(true)
            ->withValue($this->object->getTitle());

        $fields_object['description'] = $factory->textarea($this->lng->txt("description"))
            ->withValue($this->object->getDescription())
            ->withAdditionalOnLoadCode($this->plugin_ui_service->noRTEOnloadCode());// Exclude from RTE

        $fields_object['online'] = $factory->checkbox($this->lng->txt('online'))
            ->withValue($this->object->isOnline());

        $fields_object['participation_type'] = $factory->radio($this->plugin->txt('participation_type'))
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

        $fields_object['writing_type'] = $factory->radio($this->plugin->txt('writing_type'))
            ->withOption(
                WritingType::ESSAY_EDITOR->value,
                $this->plugin->txt('writing_type_essay_editor'),
                $this->plugin->txt('writing_type_essay_editor_info')
            )
            ->withOption(
                WritingType::PDF_UPLOAD->value,
                $this->plugin->txt('writing_type_pdf_upload'),
                $this->plugin->txt('writing_type_pdf_upload_info')
            )
            ->withValue($writing_settings->getWritingType()->value);

        // Content
        $fields_content = [];
        $fields_content['task_description'] = $this->plugin_ui_factory->field()
            ->textareaModified($this->plugin->txt("task_description"), $this->plugin->txt("task_description_info"))
            ->withValue($orga_settings->getDescription() ?? "")
            ->withAdditionalTransformation($this->plugin_ui_service->stringTransformationByRTETagSet());

        $fields_content['closing_message'] = $this->plugin_ui_factory->field()
            ->textareaModified($this->plugin->txt("closing_message"), $this->plugin->txt("closing_message_info"))
            ->withValue($orga_settings->getClosingMessage() ?? "")
            ->withAdditionalTransformation($this->plugin_ui_service->stringTransformationByRTETagSet());

        // Task
        $fields_settings = [];
        $fields_settings['writing_start'] = $factory->dateTime(
            $this->plugin->txt("writing_start"),
            $this->plugin->txt("writing_start_info")
        )
            ->withUseTime(true)
            ->withValue((string) $orga_settings->getWritingStart());

        $fields_settings['writing_end'] = $factory->dateTime(
            $this->plugin->txt("writing_end"),
            $this->plugin->txt("writing_end_info")
        )
            ->withUseTime(true)
            ->withValue((string) $orga_settings->getWritingEnd());

        $limit = (int) $orga_settings->getWritingLimitMinutes();
        $days = floor($limit / (24 * 60));
        $hours = floor(($limit - $days * 24 * 60) / 60);
        $minutes = $limit % 60;

        $fields_settings['writing_limit'] = $factory->optionalGroup(
            [
                'days' => $factory->numeric(
                    $this->plugin->txt("writing_limit_days"),
                )->withValue($days > 0 ? $days: null),
                'hours_minutes' => $factory->dateTime(
                    $this->plugin->txt("writing_limit_hours_minutes"),
                )->withTimeOnly(true)
                    ->withValue(new \DateTimeImmutable(sprintf('%02d:%02d:00', $hours, $minutes, 0), New \DateTimeZone($this->user->getTimeZone())))
            ],
            $this->plugin->txt('writing_limit'),
            $this->plugin->txt('writing_limit_info')
        );
        if ($limit === 0) {
            $fields_settings['writing_limit'] = $fields_settings['writing_limit']->withValue(null);
        }

        $fields_settings['location'] =  $factory->tag(
            $this->plugin->txt("locations"),
            $this->location_service->exampleTitles(),
            $this->plugin->txt("locations_info")
        )
            ->withTagMaxLength(255)
            ->withValue($this->location_service->allTitles());

        $fields_settings['keep_essay_available'] = $factory->checkbox(
            $this->plugin->txt('keep_essay_available'),
            $this->plugin->txt('keep_essay_available_info')
        )
            ->withValue($orga_settings->getKeepAvailable());

        $fields_settings['solution_available'] = $factory->optionalGroup(
            [
                'solution_available_date' => $factory->dateTime(
                    $this->plugin->txt("solution_available_date"),
                    $this->plugin->txt("solution_available_date_info")
                )
                    ->withUseTime(true)
                    ->withValue((string) $orga_settings->getSolutionAvailableDate())
            ],
            $this->plugin->txt('solution_available'),
            $this->plugin->txt('solution_available_info')
        );
        // strange but effective
        if (!$orga_settings->isSolutionAvailable()) {
            $fields_settings['solution_available'] = $fields_settings['solution_available']->withValue(null);
        }

        $fields_settings['correction_start'] = $factory->dateTime(
            $this->plugin->txt("correction_start"),
            $this->plugin->txt("correction_start_info")
        )
            ->withUseTime(true)
            ->withValue((string) $orga_settings->getCorrectionStart());

        $fields_settings['correction_end'] = $factory->dateTime(
            $this->plugin->txt("correction_end"),
            $this->plugin->txt("correction_end_info")
        )
            ->withUseTime(true)
            ->withValue((string) $orga_settings->getCorrectionEnd());

        $fields_settings['result_available_type'] = $factory->switchableGroup(
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
                            ->withValue((string) $orga_settings->getResultAvailableDate())
                    ],
                    $this->plugin->txt('result_available_after')
                )
            ],
            $this->plugin->txt('result_available_type'),
            $this->plugin->txt('result_available_type_info'),
        )->withValue($orga_settings->getResultAvailableType());

        $fields_settings['statistics_available']  = $factory->checkbox(
            $this->plugin->txt("writer_statistics_enabled"),
            $this->plugin->txt("writer_statistics_info")
        )->withValue($orga_settings->getStatisticsAvailable());

        $review_settings = [
            'review_start' =>  $factory->dateTime(
                $this->plugin->txt("review_start"),
                $this->plugin->txt("review_start_info")
            )
                ->withUseTime(true)
                ->withValue((string) $orga_settings->getReviewStart()),
            'review_end' =>  $factory->dateTime(
                $this->plugin->txt("review_end"),
                $this->plugin->txt("review_end_info")
            )
                ->withUseTime(true)
                ->withValue((string) $orga_settings->getReviewEnd()),
            'review_notification' => $factory->optionalGroup(
                [
                    "review_notification_text" => $factory->textarea(
                        $this->plugin->txt("review_notification_text"),
                        $this->plugin->txt("review_notification_text_info")
                    )
                        ->withAdditionalOnLoadCode($this->plugin_ui_service->noRTEOnloadCode())
                        ->withValue($orga_settings->getReviewNotifText() ?? ""),
                ],
                $this->plugin->txt("review_notification_enabled"),
                $this->plugin->txt("review_notification_info")
            )
        ];

        if(!$orga_settings->getReviewNotification()) {
            $review_settings['review_notification'] = $review_settings['review_notification']->withValue(null);
        }

        $fields_settings['review']  = $factory->optionalGroup(
            $review_settings,
            $this->plugin->txt("review_enabled"),
            $this->plugin->txt("review_info")
        );

        if(!$orga_settings->isReviewEnabled()) {
            $fields_settings['review'] = $fields_settings['review']->withValue(null);
        }

        $sections['object'] = $factory->section($fields_object, $this->plugin->txt('object_settings'));
        $sections['content'] = $factory->section($fields_content, $this->plugin->txt('content'));
        $sections['task'] = $factory->section($fields_settings, $this->plugin->txt('task_settings'))->withAdditionalTransformation(
            $this->refinery->custom()->constraint(function (array $var) {
                if(($var['result_available_type'][0] ?? "") === TaskSettings::RESULT_AVAILABLE_REVIEW){
                    return !empty($var['review']);
                }
                return true;

            }, $this->plugin->txt("result_available_review_error"))
        );

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }
}
