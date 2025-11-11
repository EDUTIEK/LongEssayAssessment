<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use DateTimeZone;
use Edutiek\AssessmentService\Assessment\CorrectionSettings\FullService as AssessmentCorrectionSettingsService;
use Edutiek\AssessmentService\Assessment\Data\AssignMode;
use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings as AssessmentCorrectionSettings;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaSettingsService;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskManager as TaskManager;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskType;
use Edutiek\AssessmentService\EssayTask\Data\TaskSettings as EssayTaskSettings;
use Edutiek\AssessmentService\System\Entity\FullService as EntityService;
use Edutiek\AssessmentService\Task\CorrectionSettings\FullService as EssayTaskCorrectionSettingsService;
use Edutiek\AssessmentService\Task\Data\CorrectionSettings as EssayCorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;

/**
 * Settings for the correction
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\CorrectionSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectionSettingsGUI extends BaseGUI
{
    private OrgaSettingsService $orga_settings_service;
    private AssessmentCorrectionSettingsService $assessment_correction_settings_service;
    private EssayTaskCorrectionSettingsService $task_correction_settings_service;
    private EntityService $entity_service;
    private DateTimeZone $user_timezone;
    private TaskManager $manager_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->orga_settings_service = $this->assessment_api->orgaSettings();
        $this->assessment_correction_settings_service = $this->assessment_api->correctionSettings();
        $this->task_correction_settings_service = $this->task_api->correctionSettings();
        $this->entity_service = $this->system_api->entity();
        $this->user_timezone = new DateTimeZone($this->user->getTimeZone());
        $this->manager_service = $this->task_api->manager();
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
        $assessment_settings = $this->assessment_correction_settings_service->get();
        $task_settings = $this->task_correction_settings_service->get();

        $factory = $this->ui_factory->input()->field();

        $sections = [];

        // Object

        $fields = [];

        if (!$orga_settings->getMultiTasks()) {
            $fields['required_correctors'] = $factory->select($this->plugin->txt('required_correctors'), [
                "1" => "1",
                "2" => "2"
            ])->withRequired(true)
                ->withValue((string) empty($assessment_settings->getRequiredCorrectors()) ? 1 : $assessment_settings->getRequiredCorrectors());

            $fields['mutual_visibility'] = $factory->checkbox(
                $this->plugin->txt('mutual_visibility'),
                $this->plugin->txt('mutual_visibility_info')
            )
                ->withValue($assessment_settings->getMutualVisibility());
        }

        $fields['assign_mode'] = $factory->radio($this->plugin->txt('assign_mode'))
            ->withRequired(true)
            ->withOption(
                AssignMode::RANDOM_EQUAL->value,
                $this->plugin->txt('assign_mode_random_equal'),
                $this->plugin->txt('assign_mode_random_equal_info')
            )
            ->withValue($assessment_settings->getAssignMode()->value);

        $fields['anonymize_correctors'] = $factory->checkbox(
            $this->plugin->txt('anonymize_correctors'),
            $this->plugin->txt('anonymize_correctors_info')
        )
            ->withValue($assessment_settings->getAnonymizeCorrectors());

        $fields['reports_enabled'] = $factory->optionalGroup(
            [
                'reports_available_start' => $factory->dateTime(
                    $this->plugin->txt("reports_available_start"),
                    $this->plugin->txt("reports_available_start_info")
                )->withUseTime(true)
                    ->withValue($assessment_settings->getReportsAvailableStart()?->setTimezone($this->user_timezone))
            ],
            $this->plugin->txt('reports_enabled'),
            $this->plugin->txt('reports_enabled_info')
        );
        if (!$assessment_settings->getReportsEnabled()) {
            $fields['reports_enabled'] = $fields['reports_enabled']->withValue(null);
        }

        $sections['correction'] = $factory->section($fields, $this->plugin->txt('correction_settings'));

        // Max Points

        $fields = [];

        foreach ($this->manager_service->all() as $task_info) {
            if ($task_info->getTaskType() === TaskType::ESSAY) {
                $settings = $this->essay_task_api->taskSettings($task_info->getId())->get();
                $fields[$task_info->getId()] = $factory->numeric($task_info->getTitle())
                    ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0))
                    ->withAdditionalTransformation($this->refinery->to()->int())
                    ->withRequired(true)
                    ->withValue($settings->getMaxPoints());
            }
        }
        if (!empty($fields)) {
            $sections['max_points'] = $factory->section($fields, $this->plugin->txt('max_points'));
        }

        // Rating

        $fields = [];

        $fields["enable_comments"] = $factory->checkbox(
            $this->plugin->txt('enable_comments'),
            $this->plugin->txt('enable_comments_info')
        )->withValue($task_settings->getEnableComments());

        $fields["enable_partial_points"] = $factory->checkbox(
            $this->plugin->txt('enable_partial_points'),
            $this->plugin->txt('enable_partial_points_info')
        )
        ->withValue($task_settings->getEnablePartialPoints());

        $fields['enable_comment_ratings'] = $factory->optionalGroup(
            [
                "positive_rating" => $factory->text(
                    $this->plugin->txt('comment_rating_positive'),
                    $this->plugin->txt('comment_rating_positive_info')
                )
                    ->withRequired(true)
                    ->withAdditionalTransformation($this->refinery->string()->hasMinLength(3))
                    ->withAdditionalTransformation($this->refinery->string()->hasMaxLength(50))
                    ->withValue($task_settings->getPositiveRating()),
                "negative_rating" => $factory->text(
                    $this->plugin->txt('comment_rating_negative'),
                    $this->plugin->txt('comment_rating_negative_info')
                )
                    ->withRequired(true)
                    ->withAdditionalTransformation($this->refinery->string()->hasMinLength(3))
                    ->withAdditionalTransformation($this->refinery->string()->hasMaxLength(50))
                    ->withValue($task_settings->getNegativeRating())
            ],
            $this->plugin->txt('enable_comment_ratings'),
            $this->plugin->txt('enable_comment_ratings_info')
        );
        if (!$task_settings->getEnableCommentRatings()) {
            $fields['enable_comment_ratings'] = $fields['enable_comment_ratings']->withValue(null);
        }

        $fields['enable_summary_pdf'] = $factory->optionalGroup(
            [
                "summary_pdf_advice" => $factory->textarea(
                    $this->plugin->txt('summary_pdf_advice'),
                    $this->plugin->txt('summary_pdf_advice_info')
                )
                    ->withAdditionalTransformation($this->refinery->kindlyTo()->string())
                    ->withValue((string) $task_settings->getSummaryPdfAdvice())
            ],
            $this->plugin->txt('enable_summary_pdf'),
            $this->plugin->txt('enable_summary_pdf_info')
        );
        if (!$task_settings->getEnableSummaryPdf()) {
            $fields['enable_summary_pdf'] = $fields['enable_summary_pdf']->withValue(null);
        }

        $sections['correction_functions'] = $factory->section($fields, $this->plugin->txt('correction_functions'));

        // Stitch decision

        if (!$orga_settings->getMultiTasks()) {
            $fields = [];
            $fields['stitch_when_distance'] = $factory->optionalGroup(
                [
                    "max_auto_distance" => $factory->text(
                        $this->plugin->txt('max_auto_distance'),
                        $this->plugin->txt('max_auto_distance_info')
                    )
                        ->withAdditionalTransformation($this->refinery->kindlyTo()->float())
                        ->withRequired(true)
                        ->withValue((string) (empty($assessment_settings->getMaxAutoDistance()) ? '0.0' : $assessment_settings->getMaxAutoDistance()))
                ],
                $this->plugin->txt('stitch_when_distance')
            );
            if (!$assessment_settings->getStitchWhenDistance()) {
                $fields['stitch_when_distance'] = $fields['stitch_when_distance']->withValue(null);
            }

            $fields['stitch_when_decimals'] = $factory->checkbox($this->plugin->txt('stitch_when_decimals'))
                ->withValue($assessment_settings->getStitchWhenDecimals());

            $sections['stitch'] = $factory->section($fields, $this->plugin->txt('settings_stitch_required'));
        }

        $form = $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this),
            $this->disabled_group->disableBySetting('tab_correction_settings', $sections)
        );

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {
            if (!$orga_settings->getMultiTasks()) {
                $assessment_settings->setRequiredCorrectors((int) $data['correction']['required_correctors']);
                $assessment_settings->setMutualVisibility((int) $data['correction']['mutual_visibility']);
            }

            $assessment_settings->setAssignMode(AssignMode::tryFrom($data['correction']['assign_mode']) ?? AssignMode::RANDOM_EQUAL);

            $assessment_settings->setAnonymizeCorrectors((int) $data['correction']['anonymize_correctors']);
            if (isset($data['correction']['reports_enabled']) && is_array($data['correction']['reports_enabled'])) {
                $assessment_settings->setReportsEnabled(true);
                $assessment_settings->setReportsAvailableStart($data['correction']['reports_enabled']['reports_available_start']);
            } else {
                $assessment_settings->setReportsEnabled(false);
            }

            $essay_tasks_settings = [];
            foreach ($this->manager_service->all() as $task_info) {
                if ($task_info->getTaskType() === TaskType::ESSAY) {
                    $essay_tasks_settings[] = $this->essay_task_api->taskSettings($task_info->getId())
                        ->get()->setMaxPoints((int) $data['max_points'][$task_info->getId()]);
                }
            }

            $task_settings->setEnableComments(((bool) $data['correction_functions']['enable_comments']));
            $task_settings->setEnablePartialPoints(((bool) $data['correction_functions']['enable_partial_points']));
            if (isset($data['correction_functions']['enable_comment_ratings']) && is_array($data['correction_functions']['enable_comment_ratings'])) {
                $task_settings->setEnableCommentRatings(true);
                $task_settings->setPositiveRating((string) $data['correction_functions']['enable_comment_ratings']['positive_rating']);
                $task_settings->setNegativeRating((string) $data['correction_functions']['enable_comment_ratings']['negative_rating']);
            } else {
                $task_settings->setEnableCommentRatings(false);
            }
            if (isset($data['correction_functions']['enable_summary_pdf']) && is_array($data['correction_functions']['enable_summary_pdf'])) {
                $task_settings->setEnableSummaryPdf(true);
                $task_settings->setSummaryPdfAdvice((string) $data['correction_functions']['enable_summary_pdf']['summary_pdf_advice']);
            } else {
                $task_settings->setEnableSummaryPdf(false);
            }

            if (!$orga_settings->getMultiTasks()) {
                if (isset($data['stitch']['stitch_when_distance']) && is_array($data['stitch']['stitch_when_distance'])) {
                    $assessment_settings->setStitchWhenDistance(true);
                    $assessment_settings->setMaxAutoDistance((float) $data['stitch']['stitch_when_distance']['max_auto_distance']);
                } else {
                    $assessment_settings->setStitchWhenDistance(false);
                }
                $assessment_settings->setStitchWhenDecimals(!empty($data['stitch']['stitch_when_decimals']));
            }

            $this->entity_service->secure($assessment_settings, AssessmentCorrectionSettings::class);
            $this->assessment_correction_settings_service->save($assessment_settings);

            $this->entity_service->secure($task_settings, EssayCorrectionSettings::class);
            $this->task_correction_settings_service->save($task_settings);

            foreach ($essay_tasks_settings as $settings) {
                $this->entity_service->secure($settings, EssayTaskSettings::class);
                $this->essay_task_api->taskSettings($settings->getTaskId())->save($settings);
            }

            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->add($form)->show();
    }
}
