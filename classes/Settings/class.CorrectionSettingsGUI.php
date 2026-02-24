<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use DateTimeZone;
use Edutiek\AssessmentService\Assessment\CorrectionSettings\FullService as AssessmentCorrectionSettingsService;
use Edutiek\AssessmentService\Assessment\Data\AssignMode;
use Edutiek\AssessmentService\Assessment\Data\CorrectionProcedure;
use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings as AssessmentCorrectionSettings;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaSettingsService;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskManager as TaskManager;
use Edutiek\AssessmentService\System\Entity\FullService as EntityService;
use Edutiek\AssessmentService\Task\CorrectionSettings\FullService as TaskCorrectionSettingsService;
use Edutiek\AssessmentService\Task\Data\CorrectionSettings as EssayCorrectionSettings;
use Edutiek\AssessmentService\Task\Data\Settings as TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use Edutiek\AssessmentService\Assessment\Data\Pseudonymization;

/**
 * Settings for the correction
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\CorrectionSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectionSettingsGUI extends BaseGUI
{
    private OrgaSettingsService $orga_settings_service;
    private AssessmentCorrectionSettingsService $assessment_correction_settings_service;
    private TaskCorrectionSettingsService $task_correction_settings_service;
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
        $this->initTools(false, true);

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
                $assessment_settings->setRequiredCorrectors((int) $data['correctors']['required_correctors'][0]);
                if ($assessment_settings->getRequiredCorrectors() === 2) {
                    $subdata = $data['correctors']['required_correctors'][1];

                    $assessment_settings->setMutualVisibility((int) $subdata['mutual_visibility']);
                    $assessment_settings->setWaitForFirst((int) $subdata['wait_for_first']);

                    if ($subdata['handle_distance'][0] === 'procedure') {
                        $assessment_settings->setProcedureWhenDistance(true);
                        $assessment_settings->setMaxAutoDistance((float) $subdata['handle_distance'][1]['max_auto_distance']);
                        $assessment_settings->setProcedure(CorrectionProcedure::tryFrom(
                            $subdata['handle_distance'][1]['procedure'] ?? CorrectionProcedure::NONE
                        ));
                        $assessment_settings->setRevisionBetween(!empty($subdata['handle_distance'][1]['revision_between']));
                        $assessment_settings->setStitchAfterProcedure(!empty($subdata['handle_distance'][1]['stitch_after_procedure']));
                    } else {
                        $assessment_settings->setProcedureWhenDistance(false);
                    }
                }
            }

            // Correction settings

            $assessment_settings->setPseudonymization(Pseudonymization::tryFrom((string) $data['correction']['pseudonymization'])
                ?? Pseudonymization::WRITER_ID);
            $assessment_settings->setAssignMode(AssignMode::tryFrom($data['correction']['assign_mode']) ?? AssignMode::RANDOM_EQUAL);
            $assessment_settings->setUndoAuthorization((bool) $data['correction']['undo_authorization']);
            $assessment_settings->setInstantStatus((bool) $data['correction']['instant_status']);
            $assessment_settings->setAnonymizeCorrectors((bool) $data['correction']['anonymize_correctors']);
            $assessment_settings->setDownloadWriting((bool) $data['correction']['allow_download_writing']);
            $assessment_settings->setDownloadCorrection((bool) $data['correction']['allow_download_correction']);
            if (isset($data['correction']['reports_enabled']) && is_array($data['correction']['reports_enabled'])) {
                $assessment_settings->setReportsEnabled(true);
                $assessment_settings->setReportsAvailableStart($data['correction']['reports_enabled']['reports_available_start']);
            } else {
                $assessment_settings->setReportsEnabled(false);
            }

            // Rating settings

            $assessment_settings->setMaxPoints((int) $data['rating_settings']['max_points']);

            $single_task_settings = [];
            if ($orga_settings->getMultiTasks()) {
                foreach ($this->manager_service->all() as $task_info) {
                    $settings = $this->task_api->settings($task_info->getId())->get();
                    $settings->setWeight($data['rating_settings']['weight' . $task_info->getId()]);
                    $single_task_settings[] = $settings;
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
                $task_settings->setPositiveRating('');
                $task_settings->setNegativeRating('');
            }

            $this->entity_service->secure($assessment_settings, AssessmentCorrectionSettings::class);
            $this->assessment_correction_settings_service->save($assessment_settings);

            $this->entity_service->secure($task_settings, EssayCorrectionSettings::class);
            $this->task_correction_settings_service->save($task_settings);

            foreach ($single_task_settings as $settings) {
                $this->entity_service->secure($settings, TaskSettings::class);
                $this->task_api->settings($settings->getTaskId())->save($settings);
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

        // Correctors

        if (!$orga_settings->getMultiTasks()) {
            $fields = [];

            // multi correctors

            $fields['max_auto_distance'] = $this->plugin_ui_factory->field()->numeric(
                $this->plugin->txt('max_auto_distance'),
                $this->plugin->txt('max_auto_distance_info')
            )
                ->withStep(0.5)
                ->withAdditionalTransformation($this->refinery->byTrying([
                    $this->refinery->KindlyTo()->float(),
                    $this->refinery->always(0)
                ]))
                ->withAdditionalTransformation($this->constraints->minimum(0))
                ->withAdditionalTransformation($this->constraints->maximum(1000000000))
                ->withValue((empty($assessment_settings->getMaxAutoDistance()) ? 0.0 : $assessment_settings->getMaxAutoDistance()));

            $fields['procedure'] = $factory->radio($this->plugin->txt('correction_procedure'))
                ->withOption(CorrectionProcedure::NONE->value, $this->plugin->txt('procedure_none'))
                ->withOption(
                    CorrectionProcedure::APPROXIMATION->value,
                    $this->plugin->txt('procedure_approximation'),
                    $this->plugin->txt('procedure_approximation_info')
                )
                ->withOption(
                    CorrectionProcedure::CONSULTING->value,
                    $this->plugin->txt('procedure_consulting'),
                    $this->plugin->txt('procedure_consulting_info')
                )
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

            $average = $factory->group(
                [],
                $this->plugin->txt('average_when_distance'),
                $this->plugin->txt('average_when_distance_info')
            );

            $procedure = $factory->group(
                $fields,
                $this->plugin->txt('procedure_when_distance'),
                $this->plugin->txt('procedure_when_distance_info')
            );


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
                )->withValue($assessment_settings->getWaitForFirst()),
                'handle_distance' => $factory->switchableGroup(
                    [
                        'average' => $average,
                        'procedure' => $procedure
                    ],
                    $this->plugin->txt('handle_distance'),
                )->withValue($assessment_settings->getProcedureWhenDistance() ? 'procedure' : 'average')
            ], $this->plugin->txt('first_and_second_corrector'));

            $fields = [];
            $fields['required_correctors'] = $factory->switchableGroup([
                '1' => $single,
                '2' => $double,
            ], $this->plugin->txt('required_correctors'))
                ->withValue((string) empty($assessment_settings->getRequiredCorrectors()) ? 1 : $assessment_settings->getRequiredCorrectors());

            $sections['correctors'] = $factory->section($fields, $this->plugin->txt('correctors_per_writer'));
        }

        // Correction Settings

        $fields = [];

        $fields['pseudonymization'] = $factory->select(
            $this->plugin->txt('pseudonymization'),
            $this->assessment_api->pseudonym()->options(),
            $this->plugin->txt('pseudonymization_info'),
        )->withRequired(true)->withValue($assessment_settings->getPseudonymization()->value);

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

        $fields['allow_download_writing'] = $factory->checkbox(
            $this->plugin->txt('allow_download_writing'),
            $this->plugin->txt('allow_download_writing_info')
        )->withValue($assessment_settings->getDownloadWriting());

        $fields['allow_download_correction'] = $factory->checkbox(
            $this->plugin->txt('allow_download_correction'),
            $this->plugin->txt('allow_download_correction_info')
        )->withValue($assessment_settings->getDownloadCorrection());

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

        $fields['max_points'] = $factory->numeric(
            $this->plugin->txt('max_points'),
            $this->plugin->txt($orga_settings->getMultiTasks() ? 'max_points_info_multi' : 'max_points_info')
        )
            ->withAdditionalTransformation($this->refinery->byTrying([
                $this->refinery->To()->int(),
                $this->refinery->always(0)
            ]))
            ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0))
            ->withAdditionalTransformation($this->refinery->int()->isLessThanOrEqual(1000000000))
            ->withValue($assessment_settings->getMaxPoints());

        if ($orga_settings->getMultiTasks()) {
            foreach ($this->manager_service->all() as $task_info) {
                $settings = $this->task_api->settings($task_info->getId())->get();
                $fields['weight' . $task_info->getId()] = $this->plugin_ui_factory->field()->numeric(
                    $this->plugin->txt('weight') . ' ' . $task_info->getTitle()
                )
                    ->withStep(0.1)
                    ->withAdditionalTransformation($this->refinery->byTrying([
                        $this->refinery->To()->float(),
                        $this->refinery->always(0)
                    ]))
                    ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0))
                    ->withAdditionalTransformation($this->refinery->int()->isLessThanOrEqual(10))
                    ->withRequired(true)
                    ->withValue($settings->getWeight());
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

        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this),
            $this->fixation_gui->disableBySetting('tab_correction_settings', $sections)
        );
    }
}
