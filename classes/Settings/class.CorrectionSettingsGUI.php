<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use DateTimeZone;
use Edutiek\AssessmentService\Assessment\CorrectionSettings\FullService as AssessmentCorrectionSettingsService;
use Edutiek\AssessmentService\Assessment\Data\AssignMode;
use Edutiek\AssessmentService\Assessment\Data\CorrectionApproximation;
use Edutiek\AssessmentService\Assessment\Data\CorrectionProcedure;
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
use ILIAS\UI\Component\Input\Container\Form\Standard;

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

        $form = $this->buildForm();
        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {

            // Multi Correctors

            if (!$orga_settings->getMultiTasks()) {
                $assessment_settings->setRequiredCorrectors((int) $data['correctors_per_writer']['required_correctors'][0]);
                if ($assessment_settings->getRequiredCorrectors() === 2) {
                    $assessment_settings->setMutualVisibility((int) $data['correctors_per_writer']['required_correctors'][1]['mutual_visibility']);
                    $assessment_settings->setWaitForFirst((int) $data['correctors_per_writer']['required_correctors'][1]['wait_for_first']);
                }
            }

            // Correction settings

            $assessment_settings->setAssignMode(AssignMode::tryFrom($data['correction']['assign_mode']) ?? AssignMode::RANDOM_EQUAL);
            $assessment_settings->setUndoAuthorization((bool) $data['correction']['undo_authorization']);
            $assessment_settings->setInstantStatus((bool) $data['correction']['instant_status']);
            $assessment_settings->setAnonymizeCorrectors((bool) $data['correction']['anonymize_correctors']);
            if (isset($data['correction']['reports_enabled']) && is_array($data['correction']['reports_enabled'])) {
                $assessment_settings->setReportsEnabled(true);
                $assessment_settings->setReportsAvailableStart($data['correction']['reports_enabled']['reports_available_start']);
            } else {
                $assessment_settings->setReportsEnabled(false);
            }

            // Rating settings

            $essay_tasks_settings = [];
            foreach ($this->manager_service->all() as $task_info) {
                if ($task_info->getTaskType() === TaskType::ESSAY) {
                    $essay_tasks_settings[] = $this->essay_task_api->taskSettings($task_info->getId())
                        ->get()->setMaxPoints((int) $data['rating_settings'][$task_info->getId()]);
                }
            }
            $assessment_settings->setNoManualDecimals(((bool) $data['rating_settings']['no_manual_decimals']));
            if (isset($data['rating_settings']['enable_summary_pdf']) && is_array($data['rating_settings']['enable_summary_pdf'])) {
                $task_settings->setEnableSummaryPdf(true);
                $task_settings->setSummaryPdfAdvice((string) $data['rating_settings']['enable_summary_pdf']['summary_pdf_advice']);
            } else {
                $task_settings->setEnableSummaryPdf(false);
            }

            // Correction functions

            $task_settings->setEnableComments(((bool) $data['correction_functions']['enable_comments']));
            $task_settings->setEnablePartialPoints(((bool) $data['correction_functions']['enable_partial_points']));
            if (isset($data['correction_functions']['enable_comment_ratings']) && is_array($data['correction_functions']['enable_comment_ratings'])) {
                $task_settings->setEnableCommentRatings(true);
                $task_settings->setPositiveRating((string) $data['correction_functions']['enable_comment_ratings']['positive_rating']);
                $task_settings->setNegativeRating((string) $data['correction_functions']['enable_comment_ratings']['negative_rating']);
            } else {
                $task_settings->setEnableCommentRatings(false);
            }

            // Procedure

            if (!$orga_settings->getMultiTasks()) {
                if (isset($data['procedure']['procedure_when_distance']) && is_array($data['procedure']['procedure_when_distance'])) {
                    $assessment_settings->setProcedureWhenDistance(true);
                    $assessment_settings->setMaxAutoDistance((float) $data['procedure']['procedure_when_distance']['max_auto_distance']);
                } else {
                    $assessment_settings->setProcedureWhenDistance(false);
                }
                $assessment_settings->setProcedure(CorrectionProcedure::tryFrom(
                    $data['procedure']['procedure'][0] ?? CorrectionProcedure::NONE
                ));
                if ($assessment_settings->getProcedure() === CorrectionProcedure::APPROXIMATION) {
                    $assessment_settings->setApproximation(CorrectionApproximation::tryFrom(
                        $data['procedure']['procedure'][1]['approximation']
                    ) ?? CorrectionProcedure::NONE);
                }
                $assessment_settings->setProcedureWhenDecimals(!empty($data['procedure']['procedure_when_decimals']));
                $assessment_settings->setRevisionBetween(!empty($data['procedure']['revision_between']));
                $assessment_settings->setStitchAfterProcedure(!empty($data['procedure']['stitch_after_procedure']));
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

    private function buildForm(): Standard
    {
        $orga_settings = $this->orga_settings_service->get();
        $assessment_settings = $this->assessment_correction_settings_service->get();
        $task_settings = $this->task_correction_settings_service->get();

        $factory = $this->ui_factory->input()->field();

        $sections = [];

        // Object

        $fields = [];

        if (!$orga_settings->getMultiTasks()) {
            $fields = [];

            $single = $factory->group(
                [],
                $this->plugin->txt('single_corrector'),
            );
            $double = $factory->group([
                'mutual_visibility' => $factory->checkbox(
                    $this->plugin->txt('mutual_visibility'),
                    $this->plugin->txt('mutual_visibility_info')
                )->withValue($assessment_settings->getMutualVisibility()),
                'wait_for_first' => $factory->checkbox(
                    $this->plugin->txt('wait_for_first'),
                    $this->plugin->txt('wait_for_first_info')
                )->withValue($assessment_settings->getWaitForFirst())

            ], $this->plugin->txt('first_and_second_corrector'));

            $fields['required_correctors'] = $factory->switchableGroup([
                '1' => $single,
                '2' => $double,
            ], $this->plugin->txt('required_correctors'))
            ->withValue((string) empty($assessment_settings->getRequiredCorrectors()) ? 1 : $assessment_settings->getRequiredCorrectors());

            $sections['correctors_per_writer'] = $factory->section($fields, $this->plugin->txt('correctors_per_writer'));
        }

        $fields = [];
        $fields['assign_mode'] = $factory->radio($this->plugin->txt('assign_mode'))
            ->withRequired(true)
            ->withOption(
                AssignMode::RANDOM_EQUAL->value,
                $this->plugin->txt('assign_mode_random_equal'),
                $this->plugin->txt('assign_mode_random_equal_info')
            )
            ->withValue($assessment_settings->getAssignMode()->value);

        $fields['undo_authorization'] = $factory->checkbox(
            $this->plugin->txt('undo_authorization'),
            $this->plugin->txt('undo_authorization_info')
        )->withValue($assessment_settings->getUndoAuthorization());

        $fields['instant_status'] = $factory->radio(
            $this->plugin->txt('instant_status'),
        )->withOption(1, $this->plugin->txt('instant_status_on'), $this->plugin->txt('instant_status_on_info'))
         ->withOption(0, $this->plugin->txt('instant_status_off'), $this->plugin->txt('instant_status_off_info'))
        ->withValue((int) $assessment_settings->getInstantStatus());

        $fields['anonymize_correctors'] = $factory->checkbox(
            $this->plugin->txt('anonymize_correctors'),
            $this->plugin->txt('anonymize_correctors_info')
        )->withValue($assessment_settings->getAnonymizeCorrectors());

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

        // Rating

        $fields = [];

        foreach ($this->manager_service->all() as $task_info) {
            if ($task_info->getTaskType() === TaskType::ESSAY) {
                $settings = $this->essay_task_api->taskSettings($task_info->getId())->get();
                $fields[$task_info->getId()] = $factory->numeric($this->plugin->txt('max_points') .
                    ($orga_settings->getMultiTasks() ? ' ' . $task_info->getTitle() : ''))
                    ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0))
                    ->withAdditionalTransformation($this->refinery->to()->int())
                    ->withRequired(true)
                    ->withValue($settings->getMaxPoints());
            }
        }

        $fields["no_manual_decimals"] = $factory->checkbox(
            $this->plugin->txt('no_manual_decimals'),
            $this->plugin->txt('no_manual_decimals_info')
        )->withValue($assessment_settings->getNoManualDecimals());

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

        if (!empty($fields)) {
            $sections['rating_settings'] = $factory->section($fields, $this->plugin->txt('rating_settings'));
        }

        // Functions

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

        $sections['correction_functions'] = $factory->section($fields, $this->plugin->txt('correction_functions'));

        // procedure decision

        if (!$orga_settings->getMultiTasks()) {
            $fields = [];
            $fields['procedure_when_distance'] = $factory->optionalGroup(
                [
                    "max_auto_distance" => $factory->text(
                        $this->plugin->txt('max_auto_distance'),
                        $this->plugin->txt('max_auto_distance_info')
                    )
                        ->withAdditionalTransformation($this->refinery->kindlyTo()->float())
                        ->withRequired(true)
                        ->withValue((string) (empty($assessment_settings->getMaxAutoDistance()) ? '0.0' : $assessment_settings->getMaxAutoDistance()))
                ],
                $this->plugin->txt('procedure_when_distance')
            );
            if (!$assessment_settings->getProcedureWhenDistance()) {
                $fields['procedure_when_distance'] = $fields['procedure_when_distance']->withValue(null);
            }

            $fields['procedure_when_decimals'] = $factory->checkbox($this->plugin->txt('procedure_when_decimals'))
                ->withValue($assessment_settings->getProcedureWhenDecimals());

            $proc_none = $factory->group(
                [],
                $this->plugin->txt('procedure_none')
            );
            $proc_consult = $factory->group(
                [],
                $this->plugin->txt('procedure_consulting'),
                $this->plugin->txt('procedure_approximation_info')
            );
            $proc_approx = $factory->group([
                'approximation' => $factory->radio($this->plugin->txt('approximation'))
                    ->withOption(CorrectionApproximation::ONE->value, $this->plugin->txt('approximation_one'))
                    ->withOption(CorrectionApproximation::BOTH->value, $this->plugin->txt('approximation_both'))
                    ->withOption(CorrectionApproximation::DECIDE->value, $this->plugin->txt('approximation_decide'))
                    ->withValue($assessment_settings->getApproximation()->value)
            ], $this->plugin->txt('procedure_approximation'), $this->plugin->txt('procedure_approximation_info'));

            $fields['procedure'] = $factory->switchableGroup([
                CorrectionProcedure::NONE->value => $proc_none,
                CorrectionProcedure::APPROXIMATION->value => $proc_approx,
                CorrectionProcedure::CONSULTING->value => $proc_consult,
            ], $this->plugin->txt('correction_procedure'))
                ->withValue($assessment_settings->getProcedure()->value);

            $fields['revision_between'] = $factory->checkbox(
                $this->plugin->txt('revision_between'),
                $this->plugin->txt('revision_between_info')
            )
                ->withValue($assessment_settings->getRevisionBetween());

            $fields['stitch_after_procedure'] = $factory->checkbox(
                $this->plugin->txt('stitch_after_procedure'),
                $this->plugin->txt('stitch_after_procedure_info')
            )
                ->withValue($assessment_settings->getStitchAfterProcedure());

            $sections['procedure'] = $factory->section($fields, $this->plugin->txt('correction_procedure_settings'));
        }

        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this),
            $this->disabled_group->disableBySetting('tab_correction_settings', $sections)
        );
    }
}
