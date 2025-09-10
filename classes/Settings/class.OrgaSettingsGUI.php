<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use DateTimeImmutable;
use DateTimeZone;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\Data\ParticipationType;
use Edutiek\AssessmentService\Assessment\Data\ResultAvailableType;
use Edutiek\AssessmentService\Assessment\Location\FullService as LocationService;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaSettingsService;
use Edutiek\AssessmentService\Assessment\Properties\FullService as PropertiesService;
use Edutiek\AssessmentService\EssayTask\Data\WritingType;
use Edutiek\AssessmentService\EssayTask\WritingSettings\FullService as WritingSettingsService;
use Edutiek\AssessmentService\System\Entity\FullService as EntityService;
use Edutiek\AssessmentService\System\Transform\FullService as TransformService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use Edutiek\AssessmentService\Assessment\WorkingTime\ValidationError;
use Edutiek\AssessmentService\Assessment\Data\DisabledGroup;

/**
 * Organisational Settings
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\OrgaSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class OrgaSettingsGUI extends BaseGUI
{
    private OrgaSettingsService $orga_settings_service;
    private LocationService $location_service;
    private WritingSettingsService $writing_settings_service;
    private PropertiesService $properties_service;
    private EntityService $entity_service;
    private TransformService $transform_service;
    private DateTimeZone $user_timezone;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->properties_service = $this->assessment_api->properties();
        $this->orga_settings_service = $this->assessment_api->orgaSettings();
        $this->writing_settings_service = $this->essay_task_api->writingSettings();
        $this->location_service = $this->assessment_api->location();
        $this->entity_service = $this->system_api->entity();
        $this->transform_service = $this->system_api->transform();
        $this->user_timezone = new DateTimeZone($this->user->getTimeZone());
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $this->initForNonTask();

        $cmd = $this->ctrl->getCmd('editSettings');
        switch ($cmd) {
            case "editSettings":
                $this->$cmd();
                break;
            case 'saveModal':
                $this->saveModal();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    private function editSettings(): void
    {
        $form = $this->buildForm();
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
            $result = $form->getInputGroup()->getContent();

            if ($result->isOK()) {
                $this->updateSettings($data);
            }
        }

        $this->add($form);
        $this->show();
    }

    private function updateSettings(array $data): void
    {
        $properties = $this->properties_service->get();
        $orga_settings = $this->orga_settings_service->get();
        $writing_settings = $this->writing_settings_service->get();
        
        $properties->setTitle($data['object']['title']);
        $properties->setDescription($data['object']['description']);

        $orga_settings->setOnline($data['object']['online']);
        $orga_settings->setTemplate($data['object']['template']);
        $orga_settings->setParticipationType(ParticipationType::tryFrom(
            $data['object']['participation_type']) ?? ParticipationType::INSTANT);

        $writing_settings->setWritingType(WritingType::from((string) $data['object']['writing_type']));

        $orga_settings->setDescription($this->transform_service->trimRichText($data['content']['task_description']));
        $orga_settings->setClosingMessage($this->transform_service->trimRichText($data['content']['closing_message']));

        $orga_settings->setWritingStart($data['task']['writing_start']);
        $orga_settings->setWritingEnd($date = $data['task']['writing_end']);

        $limit = null;
        if (!empty($data['task']['writing_limit'])) {
            $limit = (int) ($data['task']['writing_limit']['days'] ?? 0) * 24 * 60;
            if ($data['task']['writing_limit']['hours_minutes'] instanceof \DateTimeInterface) {
                list($hours, $minutes) = explode(':', $data['task']['writing_limit']['hours_minutes']->format('H:i'));
                $limit += (int) $hours * 60 + (int) $minutes;
            }
        }
        $orga_settings->setWritingLimitMinutes($limit > 0 ? $limit : null);
        $orga_settings->setKeepAvailable(!empty($data['task']['keep_essay_available']));
        $orga_settings->setSolutionAvailable(!empty($data['task']['solution_available']));
        $orga_settings->setSolutionAvailableDate($data['task']['solution_available']['solution_available_date'] ?? null);
        $orga_settings->setStatisticsAvailable(!empty($data['task']['statistics_available']));
        $orga_settings->setCorrectionStart($data['task']['correction_start'] ?? null);
        $orga_settings->setCorrectionEnd($data['task']['correction_end'] ?? null);

        $orga_settings->setResultAvailableType(ResultAvailableType::tryFrom((
            $data['task']['result_available_type'][0] )?? ResultAvailableType::REVIEW));
        $orga_settings->setResultAvailableDate($data['task']['result_available_type'][1]['result_available_date'] ?? null);

        $orga_settings->setReviewEnabled(!empty($data['task']['review']));
        if ($orga_settings->getReviewEnabled()) {
            $orga_settings->setReviewStart($data['task']['review']['review_start'] ?? null);
            $orga_settings->setReviewEnd($data['task']['review']['review_end'] ?? null);
            $orga_settings->setReviewNotification(!empty($data['task']['review']['review_notification']));
            if ($orga_settings->getReviewNotification()) {
                $orga_settings->setReviewNotifText($data['task']['review']['review_notification']['review_notification_text'] ?? null);
            }
        }

        if ($this->orga_settings_service->validate($orga_settings)) {
            $this->properties_service->save($properties);
            $this->entity_service->secure($orga_settings, OrgaSettings::class);
            $this->orga_settings_service->save($orga_settings);
            $this->location_service->saveTitles((array) ($data['task']['location'] ?? []));

            $this->success($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->failure(implode('<br>',
            array_map(fn(ValidationError $error) => $this->plugin->txt('failure_'. $error->value),
            $orga_settings->getValidationErrors())));
    }

    private function buildForm(): Standard
    {
        $properties = $this->properties_service->get();
        $orga_settings = $this->orga_settings_service->get();
        $writing_settings = $this->writing_settings_service->get();

        $factory = $this->ui_factory->input()->field();
        $section = fn($x, $title) => $factory->section($this->disabled_group->disableBySetting($x), $title);
        $sections = [];

        $fields_object = [];
        $fields_object['title'] = $factory->text($this->lng->txt("title"))
            ->withRequired(true)
            ->withValue($properties->getTitle());

        $fields_object['description'] = $factory->textarea($this->lng->txt("description"))
            ->withValue($properties->getDescription());// Exclude from RTE

        $fields_object['online'] = $factory->checkbox($this->lng->txt('online'))
            ->withValue($orga_settings->getOnline());

        if ($orga_settings->getSrcTemplateName()) {
            $fields_object['src_template'] = $factory->text($this->plugin->txt('src_template'))
                ->withValue($orga_settings->getSrcTemplateName())
                ->withDisabled(true);
        }

        if ($this->assessment_api->permissions($this->object->getId())->canEditTemplates()) {
            $fields_object['template'] = $factory->checkbox($this->plugin->txt('is_template'))
                ->withValue($orga_settings->getTemplate());
        }

        $fields_object['multi_tasks'] = $factory->checkbox($this->plugin->txt('multi_tasks'),
            $this->plugin->txt('multi_tasks_info'))
            ->withValue($orga_settings->getMultiTasks())
            ->withDisabled(true);

        $fields_object['participation_type'] = $factory->radio($this->plugin->txt('participation_type'))
            ->withOption(
                ParticipationType::FIXED->value,
                $this->plugin->txt('participation_type_fixed'),
                $this->plugin->txt('participation_type_fixed_info')
            )
            ->withOption(
                ParticipationType::INSTANT->value,
                $this->plugin->txt('participation_type_instant'),
                $this->plugin->txt('participation_type_instant_info')
            )
            ->withValue($orga_settings->getParticipationType()->value);

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

        $fields_content = [];
        $fields_content['task_description'] = $this->plugin_ui_factory->field()
            ->tinyMCE($this->plugin->txt("task_description"), $this->plugin->txt("task_description_info"))
            ->withValue($orga_settings->getDescription() ?? "");

        $fields_content['closing_message'] = $this->plugin_ui_factory->field()
            ->tinyMCE($this->plugin->txt("closing_message"), $this->plugin->txt("closing_message_info"))
            ->withValue($orga_settings->getClosingMessage() ?? "");

        $fields_settings = [];
        $fields_settings['writing_start'] = $factory->dateTime(
            $this->plugin->txt("writing_start"),
            $this->plugin->txt("writing_start_info")
        )
            ->withUseTime(true)
            ->withValue($orga_settings->getWritingStart()?->setTimezone($this->user_timezone));

        $fields_settings['writing_end'] = $factory->dateTime(
            $this->plugin->txt("writing_end"),
            $this->plugin->txt("writing_end_info")
        )
            ->withUseTime(true)
            ->withValue($orga_settings->getWritingEnd()?->setTimezone($this->user_timezone));

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
                    ->withValue(new DateTimeImmutable(sprintf('%02d:%02d:00', $hours, $minutes), $this->user_timezone))
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
                    ->withValue($orga_settings->getSolutionAvailableDate()?->setTimezone($this->user_timezone))
            ],
            $this->plugin->txt('solution_available'),
            $this->plugin->txt('solution_available_info')
        );
        // strange but effective
        if (!$orga_settings->getSolutionAvailable()) {
            $fields_settings['solution_available'] = $fields_settings['solution_available']->withValue(null);
        }

        $fields_settings['correction_start'] = $factory->dateTime(
            $this->plugin->txt("correction_start"),
            $this->plugin->txt("correction_start_info")
        )
            ->withUseTime(true)
            ->withValue($orga_settings->getCorrectionStart()?->setTimezone($this->user_timezone));

        $fields_settings['correction_end'] = $factory->dateTime(
            $this->plugin->txt("correction_end"),
            $this->plugin->txt("correction_end_info")
        )
            ->withUseTime(true)
            ->withValue($orga_settings->getCorrectionEnd()?->setTimezone($this->user_timezone));

        $fields_settings['result_available_type'] = $factory->switchableGroup(
            [
                ResultAvailableType::FINALISED->value => $factory->group(
                    [],
                    $this->plugin->txt('result_available_finalised'),
                ),
                ResultAvailableType::REVIEW->value => $factory->group(
                    [],
                    $this->plugin->txt('result_available_review'),
                ),
                ResultAvailableType::DATE->value => $factory->group(
                    [
                        'result_available_date' =>  $factory->dateTime(
                            $this->plugin->txt("result_available_date"),
                            $this->plugin->txt('result_available_date_info')
                        )
                            ->withUseTime(true)
                            ->withValue($orga_settings->getResultAvailableDate()?->setTimezone($this->user_timezone))
                    ],
                    $this->plugin->txt('result_available_after')
                )
            ],
            $this->plugin->txt('result_available_type'),
            $this->plugin->txt('result_available_type_info'),
        )->withValue($orga_settings->getResultAvailableType()->value);

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
                ->withValue($orga_settings->getReviewStart()?->setTimezone($this->user_timezone)),
            'review_end' =>  $factory->dateTime(
                $this->plugin->txt("review_end"),
                $this->plugin->txt("review_end_info")
            )
                ->withUseTime(true)
                ->withValue($orga_settings->getReviewEnd()?->setTimezone($this->user_timezone)),
            'review_notification' => $factory->optionalGroup(
                [
                    "review_notification_text" => $factory->textarea(
                        $this->plugin->txt("review_notification_text"),
                        $this->plugin->txt("review_notification_text_info")
                    )
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

        if(!$orga_settings->getReviewEnabled()) {
            $fields_settings['review'] = $fields_settings['review']->withValue(null);
        }

        $sections['object'] = $section($fields_object, $this->plugin->txt('object_settings'));
        $sections['content'] = $section($fields_content, $this->plugin->txt('content'));
        $sections['task'] = $section($fields_settings, $this->plugin->txt('task_settings'))->withAdditionalTransformation(
            $this->refinery->custom()->constraint(function (array $var) {
                if(($var['result_available_type'][0] ?? "") === ResultAvailableType::REVIEW->value) {
                    return !empty($var['review']);
                }
                return true;

            }, $this->plugin->txt("result_available_review_error"))
        );

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }
}
