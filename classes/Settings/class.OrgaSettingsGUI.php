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

/**
 * Organizational Settings
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

    private function buildForm(): Standard
    {
        $properties = $this->properties_service->get();
        $orga_settings = $this->orga_settings_service->get();
        $writing_settings = $this->writing_settings_service->get();
        $factory = $this->ui_factory->input()->field();

        $sections = [];

        // Object
        if ($this->fixation_gui->isVisible('object')) {
            $fields_object = [];
            $fields_object['title'] = $factory->text($this->lng->txt("title"))
                ->withRequired(true)
                ->withValue($properties->getTitle());

            $fields_object['description'] = $factory->textarea($this->lng->txt("description"))
                ->withValue($properties->getDescription());

            $fields_object['online'] = $factory->checkbox(
                $this->lng->txt('online'),
                $this->plugin->txt('online_info')
            )
                    ->withValue($orga_settings->getOnline());

            $sections['object'] = $factory->section($fields_object, $this->plugin->txt('object_settings'))
                ->withDisabled($this->fixation_gui->isDisabled('object'));
        }

        // Type
        if ($this->fixation_gui->isVisible('type')) {
            $fields_type = [];

            if ($orga_settings->getSrcTemplateName()) {
                $fields_type['src_template'] = $factory->text($this->plugin->txt('src_template'))
                    ->withValue($orga_settings->getSrcTemplateName())
                    ->withDisabled(true);
            }

            $fields_type['multi_tasks'] = $factory->checkbox(
                $this->plugin->txt('multi_tasks'),
                $this->plugin->txt('multi_tasks_info')
            )
                ->withValue($orga_settings->getMultiTasks())
                ->withDisabled(true);

            $fields_type['participation_type'] = $factory->radio($this->plugin->txt('participation_type'))
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

            $group_editor = $factory->group(
                [
                    'dashboard' => $factory->checkbox(
                        $this->plugin->txt('dashboard'),
                        $this->plugin->txt('dashboard_info')
                    )->withValue($orga_settings->getDashboard())
                ],
                $this->plugin->txt('writing_type_essay_editor'),
                $this->plugin->txt('writing_type_essay_editor_info')
            );

            $group_upload = $factory->group(
                [],
                $this->plugin->txt('writing_type_pdf_upload'),
                $this->plugin->txt('writing_type_pdf_upload_info')
            );

            $fields_type['writing_type'] = $factory->switchableGroup(
                [
                    WritingType::ESSAY_EDITOR->value => $group_editor,
                    WritingType::PDF_UPLOAD->value => $group_upload
                ],
                $this->plugin->txt('writing_type')
            )->withValue($writing_settings->getWritingType()->value);

            $sections['type'] = $factory->section($fields_type, $this->plugin->txt('type_settings'))
                ->withDisabled($this->fixation_gui->isDisabled('type'));
        }

        // Info
        if ($this->fixation_gui->isVisible('info')) {
            $fields_info = [];

            $fields_info['task_description'] = $this->plugin_ui_factory->field()
                ->tinyMCE(
                    $this->plugin->txt("task_description"),
                    $this->plugin->txt("task_description_info")
                )
                ->withValue($orga_settings->getDescription() ?? "")
                ->withDisabled($this->fixation_gui->isDisabled('info'));

            $fields_info['closing_message'] = $this->plugin_ui_factory->field()
                ->tinyMCE(
                    $this->plugin->txt("closing_message"),
                    $this->plugin->txt("closing_message_info")
                )
                ->withValue($orga_settings->getClosingMessage() ?? "")
                ->withDisabled($this->fixation_gui->isDisabled('info'));

            if ($this->fixation_gui->isDisabled('info')) {
                $fields_info['location'] = $this->plugin_ui_factory->field()->info(
                    $this->plugin->txt("locations"),
                )->withInfo($this->plugin_ui_factory->legacy(
                    implode(', ', $this->location_service->allTitlesIndexed())
                ));
            } else {
                $fields_info['location'] = $factory->tag(
                    $this->plugin->txt("locations"),
                    $this->location_service->exampleTitles(),
                    $this->plugin->txt("locations_info")
                )
                    ->withTagMaxLength(255)
                    // tag input requires numeric indexes starting with 0
                    ->withValue(array_values($this->location_service->allTitlesIndexed()));
                // don't disable tag input - it would produce an error when being saved

            }

            $sections['info'] = $factory->section($fields_info, $this->plugin->txt('info_settings'))
                ->withDisabled($this->fixation_gui->isDisabled('info'));
        }


        // Writing
        if ($this->fixation_gui->isVisible('writing')) {
            $fields_writing = [];

            $fields_writing['writing_start'] = $factory->dateTime(
                $this->plugin->txt("writing_start"),
                $this->plugin->txt("writing_start_info")
            )
                ->withUseTime(true)
                ->withValue(
                    $orga_settings->getWritingStart()?->setTimezone(
                        $this->user_timezone
                    )
                );

            $fields_writing['writing_end'] = $factory->dateTime(
                $this->plugin->txt("writing_end"),
                $this->plugin->txt("writing_end_info")
            )
                ->withUseTime(true)
                ->withValue(
                    $orga_settings->getWritingEnd()?->setTimezone($this->user_timezone)
                );

            $limit = (int) $orga_settings->getWritingLimitMinutes();
            $days = floor($limit / (24 * 60));
            $hours = floor(($limit - $days * 24 * 60) / 60);
            $minutes = $limit % 60;

            $fields_writing['writing_limit'] = $factory->optionalGroup(
                [
                    'days' => $factory->numeric(
                        $this->plugin->txt("writing_limit_days"),
                    )->withValue($days > 0 ? $days : null),
                    'hours_minutes' => $factory->dateTime(
                        $this->plugin->txt("writing_limit_hours_minutes"),
                    )->withTimeOnly(true)
                        ->withValue(
                            new DateTimeImmutable(
                                sprintf('%02d:%02d:00', $hours, $minutes),
                                $this->user_timezone
                            )
                        )
                ],
                $this->plugin->txt('writing_limit'),
                $this->plugin->txt('writing_limit_info')
            );
            if ($limit === 0) {
                $fields_writing['writing_limit'] = $fields_writing['writing_limit']->withValue(null);
            }

            $fields_writing['start_password'] = $factory->text(
                $this->plugin->txt("start_password"),
                $this->plugin->txt("start_password_info")
            )
                    ->withMaxLength(50)
                    ->withValue((string) $orga_settings->getStartPassword());

            $forwarding = ["url" => $factory->url(
                $this->plugin->txt("forwarding_url"),
                $this->plugin->txt("forwarding_url_info")
            )
                    ->withValue($orga_settings->getForwardingUrl())
            ];

            $fields_writing['forwarding'] = $factory->optionalGroup(
                $forwarding,
                $this->plugin->txt("forwarding_enabled"),
                $this->plugin->txt("forwarding_info")
            );

            if (empty($orga_settings->getForwardingUrl())) {
                $fields_writing['forwarding'] = $fields_writing['forwarding']->withValue(null);
            }

            $fields_writing['keep_essay_available'] = $factory->checkbox(
                $this->plugin->txt('keep_essay_available'),
                $this->plugin->txt('keep_essay_available_info')
            )
                ->withValue($orga_settings->getKeepAvailable());

            $sections['writing'] = $factory->section($fields_writing, $this->plugin->txt('writing_organisation'))
                ->withDisabled($this->fixation_gui->isDisabled('writing'));
        }

        // Correction
        if ($this->fixation_gui->isVisible('correction')) {
            $fields_correction = [];

            $fields_correction['correction_start'] = $factory->dateTime(
                $this->plugin->txt("correction_start"),
                $this->plugin->txt("correction_start_info")
            )
                ->withUseTime(true)
                ->withValue(
                    $orga_settings->getCorrectionStart()?->setTimezone(
                        $this->user_timezone
                    )
                );

            $fields_correction['correction_end'] = $factory->dateTime(
                $this->plugin->txt("correction_end"),
                $this->plugin->txt("correction_end_info")
            )
                ->withUseTime(true)
                ->withValue(
                    $orga_settings->getCorrectionEnd()?->setTimezone(
                        $this->user_timezone
                    )
                );
            $sections['correction'] = $factory->section($fields_correction, $this->plugin->txt('correction_organisation'))
                ->withDisabled($this->fixation_gui->isDisabled('correction'));
        }

        // Review
        if ($this->fixation_gui->isVisible('review')) {
            $fields_review = [];

            $fields_review['solution_available'] = $factory->optionalGroup(
                [
                    'solution_available_date' => $factory->dateTime(
                        $this->plugin->txt("solution_available_date"),
                        $this->plugin->txt("solution_available_date_info")
                    )
                        ->withUseTime(true)
                        ->withValue(
                            $orga_settings->getSolutionAvailableDate()?->setTimezone(
                                $this->user_timezone
                            )
                        )
                ],
                $this->plugin->txt('solution_available'),
                $this->plugin->txt('solution_available_info')
            );
            // strange but effective
            if (!$orga_settings->getSolutionAvailable()) {
                $fields_review['solution_available'] = $fields_review['solution_available']->withValue(null);
            }

            $fields_review['result_available_type'] = $factory->switchableGroup(
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
                            'result_available_date' => $factory->dateTime(
                                $this->plugin->txt("result_available_date"),
                                $this->plugin->txt('result_available_date_info')
                            )
                                ->withUseTime(true)
                                ->withValue(
                                    $orga_settings->getResultAvailableDate()?->setTimezone(
                                        $this->user_timezone
                                    )
                                )
                        ],
                        $this->plugin->txt('result_available_after')
                    )
                ],
                $this->plugin->txt('result_available_type'),
                $this->plugin->txt('result_available_type_info'),
            )->withValue($orga_settings->getResultAvailableType()->value);

            $fields_review['statistics_available'] = $factory->checkbox(
                $this->plugin->txt("writer_statistics_enabled"),
                $this->plugin->txt("writer_statistics_info")
            )->withValue($orga_settings->getStatisticsAvailable());

            $review_settings = [
                'review_start' => $factory->dateTime(
                    $this->plugin->txt("review_start"),
                    $this->plugin->txt("review_start_info")
                )
                    ->withUseTime(true)
                    ->withValue($orga_settings->getReviewStart()?->setTimezone($this->user_timezone)),
                'review_end' => $factory->dateTime(
                    $this->plugin->txt("review_end"),
                    $this->plugin->txt("review_end_info")
                )
                    ->withUseTime(true)
                    ->withValue($orga_settings->getReviewEnd()?->setTimezone($this->user_timezone)),
            ];

            $fields_review['review'] = $factory->optionalGroup(
                $review_settings,
                $this->plugin->txt("review_enabled"),
                $this->plugin->txt("review_info")
            );

            if (!$orga_settings->getReviewEnabled()) {
                $fields_review['review'] = $fields_review['review']->withValue(null);
            }

            $sections['review'] = $factory->section(
                $fields_review,
                $this->plugin->txt('review_organisation')
            )->withAdditionalTransformation(
                $this->refinery->custom()->constraint(function (array $var) {
                    if (($var['result_available_type'][0] ?? "") === ResultAvailableType::REVIEW->value) {
                        return !empty($var['review']);
                    }
                    return true;
                }, $this->plugin->txt("result_available_review_error"))
            )
            ->withDisabled($this->fixation_gui->isDisabled('review'));
        }

        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this),
            $sections
        );
    }

    private function updateSettings(array $data): void
    {
        $properties = $this->properties_service->get();
        $orga_settings = $this->orga_settings_service->get();
        $writing_settings = $this->writing_settings_service->get();

        if (!$this->fixation_gui->isDisabled('object')) {
            $properties->setTitle($data['object']['title']);
            $properties->setDescription($data['object']['description']);
            $orga_settings->setOnline($data['object']['online']);
        }

        if (!$this->fixation_gui->isDisabled('type')) {
            $orga_settings->setParticipationType(
                ParticipationType::tryFrom(
                    $data['type']['participation_type']
                ) ?? ParticipationType::INSTANT
            );

            $writing_settings->setWritingType(WritingType::from((string) $data['type']['writing_type'][0]));
            if ($data['type']['writing_type'][0] == WritingType::ESSAY_EDITOR->value) {
                $orga_settings->setDashboard(!empty($data['type']['writing_type'][1]['dashboard']));
            } else {
                $orga_settings->setDashboard(false);
            }
        }

        if (!$this->fixation_gui->isDisabled('info')) {
            $orga_settings->setDescription($this->transform_service->trimRichText($data['info']['task_description']));
            $orga_settings->setClosingMessage($this->transform_service->trimRichText($data['info']['closing_message']));
        }

        if (!$this->fixation_gui->isDisabled('writing')) {
            $orga_settings->setWritingStart($data['writing']['writing_start']);
            $orga_settings->setWritingEnd($date = $data['writing']['writing_end']);

            $limit = null;
            if (!empty($data['writing']['writing_limit'])) {
                $limit = (int) ($data['writing']['writing_limit']['days'] ?? 0) * 24 * 60;
                if ($data['writing']['writing_limit']['hours_minutes'] instanceof \DateTimeInterface) {
                    list($hours, $minutes) = explode(
                        ':',
                        $data['writing']['writing_limit']['hours_minutes']->format('H:i')
                    );
                    $limit += (int) $hours * 60 + (int) $minutes;
                }
            }
            $orga_settings->setWritingLimitMinutes($limit > 0 ? $limit : null);

            $orga_settings->setStartPassword(
                empty($data['writing']['start_password']) ? null : (string) $data['writing']['start_password']
            );

            if (!empty($data['writing']['forwarding'])) {
                $orga_settings->setForwardingUrl((string) $data['writing']['forwarding']['url']);
            } else {
                $orga_settings->setForwardingUrl(null);
            }

            $orga_settings->setKeepAvailable(!empty($data['writing']['keep_essay_available']));
        }

        if (!$this->fixation_gui->isDisabled('correction')) {
            $orga_settings->setCorrectionStart($data['correction']['correction_start'] ?? null);
            $orga_settings->setCorrectionEnd($data['correction']['correction_end'] ?? null);
        }

        if (!$this->fixation_gui->isDisabled('review')) {
            $orga_settings->setSolutionAvailable(!empty($data['review']['solution_available']));
            $orga_settings->setSolutionAvailableDate(
                $data['review']['solution_available']['solution_available_date'] ?? null
            );

            $orga_settings->setResultAvailableType(
                ResultAvailableType::tryFrom(
                    (
                        $data['review']['result_available_type'][0]
                    ) ?? ResultAvailableType::REVIEW
                )
            );
            $orga_settings->setResultAvailableDate(
                $data['review']['result_available_type'][1]['result_available_date'] ?? null
            );

            $orga_settings->setStatisticsAvailable(!empty($data['review']['statistics_available']));

            $orga_settings->setReviewEnabled(!empty($data['review']['review']));
            if ($orga_settings->getReviewEnabled()) {
                $orga_settings->setReviewStart($data['review']['review']['review_start'] ?? null);
                $orga_settings->setReviewEnd($data['review']['review']['review_end'] ?? null);
            }
        }

        $result = $this->orga_settings_service->validate($orga_settings);

        if ($result->isOk()) {
            $this->properties_service->save($properties);
            $this->entity_service->secure($orga_settings, OrgaSettings::class);
            $this->orga_settings_service->save($orga_settings);
            $this->writing_settings_service->save($writing_settings);
            if (!$this->fixation_gui->isDisabled('info')) {
                $this->location_service->saveTitles((array) ($data['info']['location'] ?? []));
            }

            $this->success($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }
        $this->failure($this->plugin->txt('failure_form_validation'), false, $result->failures());
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $this->initTools(false, true, 'tab_orga_settings');

        $cmd = $this->ctrl->getCmd('editSettings');
        switch ($cmd) {
            case "editSettings":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }
}
