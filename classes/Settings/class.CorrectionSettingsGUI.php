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
use Edutiek\AssessmentService\Task\Data\SummaryInclusion;

/**
 * Settings for the correction
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\CorrectionSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectionSettingsGUI extends BaseGUI
{
    private OrgaSettingsService $orga_settings_service;
    private AssessmentCorrectionSettingsService $assessment_correction_settings_service;
    private EssayTaskCorrectionSettingsService $essay_task_correction_settings_service;
    private EntityService $entity_service;
    private DateTimeZone $user_timezone;
    private TaskManager $manager_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->orga_settings_service = $this->assessment_api->orgaSettings();
        $this->assessment_correction_settings_service = $this->assessment_api->correctionSettings();
        $this->essay_task_correction_settings_service = $this->task_api->correctionSettings();
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
        $essay_settings = $this->essay_task_correction_settings_service->get();

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
        // strange but effective
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



        $options = [
            SummaryInclusion::INCLUDE_NOT->value => $this->plugin->txt('include_not'),
            SummaryInclusion::INCLUDE_RELEVANT->value => $this->plugin->txt('include_relevant'),
        ];
        $fields = array_merge($fields,
            [
                "include_comments" => $factory->select($this->plugin->txt('include_comments'),
                    $options),//->withValue($essay_settings->getIncludeComments()->value),
                "include_comment_ratings" => $factory->select(sprintf($this->plugin->txt('include_comment_ratings'), $essay_settings->getPositiveRating(), $essay_settings->getNegativeRating()),
                    $options),//-->withValue($essay_settings->getIncludeCommentRatings()->value),
                "include_points" => $factory->select($this->plugin->txt('include_points'),
                    $options),//-->withValue($essay_settings->getIncludeCommentPoints()->value),
            ],
        );

        $fields['positive_rating'] = $factory->text(
            $this->plugin->txt('comment_rating_positive'),
            $this->plugin->txt('comment_rating_positive_info')
        )
            ->withRequired(true)
            ->withAdditionalTransformation($this->refinery->string()->hasMinLength(3))
            ->withAdditionalTransformation($this->refinery->string()->hasMaxLength(50))
            ->withValue($essay_settings->getPositiveRating());

        $fields['negative_rating'] = $factory->text(
            $this->plugin->txt('comment_rating_negative'),
            $this->plugin->txt('comment_rating_negative_info')
        )
            ->withRequired(true)
            ->withAdditionalTransformation($this->refinery->string()->hasMinLength(3))
            ->withAdditionalTransformation($this->refinery->string()->hasMaxLength(50))
            ->withValue($essay_settings->getNegativeRating());

        $sections['rating'] = $factory->section($fields, $this->plugin->txt('rating_settings'));

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
            // strange but effective
            if (!$assessment_settings->getStitchWhenDistance()) {
                $fields['stitch_when_distance'] = $fields['stitch_when_distance']->withValue(null);
            }

            $fields['stitch_when_decimals'] = $factory->checkbox($this->plugin->txt('stitch_when_decimals'))
                ->withValue($assessment_settings->getStitchWhenDecimals());

            $sections['stitch'] = $factory->section($fields, $this->plugin->txt('settings_stitch_required'));
        }

        $form = $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $this->disabled_group->disableBySetting($sections));

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

            $essay_settings->setPositiveRating((string) $data['rating']['positive_rating']);
            $essay_settings->setNegativeRating((string) $data['rating']['negative_rating']);

            $tasks_settings = [];
            foreach ($this->manager_service->all() as $task_info) {
                if ($task_info->getTaskType() === TaskType::ESSAY) {
                    $tasks_settings[] = $this->essay_task_api->taskSettings($task_info->getId())
                        ->get()->setMaxPoints((int) $data['max_points'][$task_info->getId()]);
                }
            }

            if (isset($data['rating']['fixed_inclusions']) && is_array($data['rating']['fixed_inclusions'])) {
                $essay_settings->setFixedInclusions(true);
                $essay_settings->setIncludeComments(
                    SummaryInclusion::tryFrom((int) $data['rating']['fixed_inclusions']['include_comments']) ?? SummaryInclusion::INCLUDE_NOT);
                $essay_settings->setIncludeCommentRatings(
                    SummaryInclusion::tryFrom((int) $data['rating']['fixed_inclusions']['include_comment_ratings']) ?? SummaryInclusion::INCLUDE_NOT);
                $essay_settings->setIncludeCommentPoints(
                    SummaryInclusion::tryFrom((int) $data['rating']['fixed_inclusions']['include_comment_points']) ?? SummaryInclusion::INCLUDE_NOT);
                $essay_settings->setIncludeCriteriaPoints(
                    SummaryInclusion::tryFrom((int) $data['rating']['fixed_inclusions']['include_criteria_points'])?? SummaryInclusion::INCLUDE_NOT);
            } else {
                $essay_settings->setFixedInclusions(false);
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

            $this->entity_service->secure($essay_settings, EssayCorrectionSettings::class);
            $this->essay_task_correction_settings_service->save($essay_settings);

            foreach ($tasks_settings as $settings) {
                $this->entity_service->secure($settings, EssayTaskSettings::class);
                $this->essay_task_api->taskSettings($settings->getTaskId())->save($settings);
            }

            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->add($form)->show();
    }
}
