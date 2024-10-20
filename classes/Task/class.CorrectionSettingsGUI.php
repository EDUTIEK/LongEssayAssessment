<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;

/**
 * Settings for the correction
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\CorrectionSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectionSettingsGUI extends BaseGUI
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
        $task_repo = $this->localDI->getTaskRepo();
        $correctionSettings = $task_repo->getCorrectionSettingsById($this->object->getId());

        $factory = $this->uiFactory->input()->field();

        $sections = [];

        // Object
        $fields = [];

        $fields['required_correctors'] = $factory->select($this->plugin->txt('required_correctors'), [
            "1" => "1",
            "2" => "2"
        ])->withRequired(true)
          ->withValue($correctionSettings->getRequiredCorrectors());

        $fields['assign_mode'] = $factory->radio($this->plugin->txt('assign_mode'))
            ->withRequired(true)
            ->withOption(
                CorrectionSettings::ASSIGN_MODE_RANDOM_EQUAL,
                $this->plugin->txt('assign_mode_random_equal'),
                $this->plugin->txt('assign_mode_random_equal_info')
            )
            ->withValue($correctionSettings->getAssignMode());

        $fields['mutual_visibility'] = $factory->checkbox($this->plugin->txt('mutual_visibility'), $this->plugin->txt('mutual_visibility_info'))
            ->withValue((bool) $correctionSettings->getMutualVisibility());

        $fields['anonymize_correctors'] = $factory->checkbox($this->plugin->txt('anonymize_correctors'), $this->plugin->txt('anonymize_correctors_info'))
            ->withValue($correctionSettings->getAnonymizeCorrectors());

        $fields['reports_enabled'] = $factory->optionalGroup(
            [
                'reports_available_start' => $factory->dateTime(
                    $this->plugin->txt("reports_available_start"),
                    $this->plugin->txt("reports_available_start_info")
                )->withUseTime(true)
                 ->withValue((string) $correctionSettings->getReportsAvailableStart())
            ],
            $this->plugin->txt('reports_enabled'),
            $this->plugin->txt('reports_enabled_info')
        );
        // strange but effective
        if (!$correctionSettings->getReportsEnabled()) {
            $fields['reports_enabled'] = $fields['reports_enabled']->withValue(null);
        }

        $sections['correction'] = $factory->section($fields, $this->plugin->txt('correction_settings'));

        // Rating

        $fields = [];

        $fields['max_points'] = $factory->numeric($this->plugin->txt('max_points'))
            ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(0))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withRequired(true)
            ->withValue($correctionSettings->getMaxPoints());

        $fields['positive_rating'] = $factory->text($this->plugin->txt('comment_rating_positive'), $this->plugin->txt('comment_rating_positive_info'))
                                             ->withRequired(true)
                                             ->withAdditionalTransformation($this->refinery->string()->hasMinLength(3))
                                             ->withAdditionalTransformation($this->refinery->string()->hasMaxLength(50))
                                             ->withValue($correctionSettings->getPositiveRating());

        $fields['negative_rating'] = $factory->text($this->plugin->txt('comment_rating_negative'), $this->plugin->txt('comment_rating_negative_info'))
                                             ->withRequired(true)
                                             ->withAdditionalTransformation($this->refinery->string()->hasMinLength(3))
                                             ->withAdditionalTransformation($this->refinery->string()->hasMaxLength(50))
                                             ->withValue($correctionSettings->getNegativeRating());

        $fields['criteria_mode'] = $factory->radio($this->plugin->txt('criteria_mode'))
                                           ->withRequired(true)
                                           ->withOption(
                                               CorrectionSettings::CRITERIA_MODE_NONE,
                                               $this->plugin->txt('criteria_mode_none'),
                                               $this->plugin->txt('criteria_mode_none_info')
                                           )
                                           ->withOption(
                                               CorrectionSettings::CRITERIA_MODE_FIXED,
                                               $this->plugin->txt('criteria_mode_fixed'),
                                               $this->plugin->txt('criteria_mode_fixed_info')
                                           )
                                            ->withOption(
                                                CorrectionSettings::CRITERIA_MODE_CORRECTOR,
                                                $this->plugin->txt('criteria_mode_corrector'),
                                                $this->plugin->txt('criteria_mode_corrector_info')
                                            )
                                           ->withValue($correctionSettings->getCriteriaMode());

        $options = [
          CorrectorSummary::INCLUDE_NOT => $this->plugin->txt('include_not'),
          CorrectorSummary::INCLUDE_INFO => $this->plugin->txt('include_info'),
          CorrectorSummary::INCLUDE_RELEVANT => $this->plugin->txt('include_relevant'),
        ];
        $fields['fixed_inclusions'] = $factory->optionalGroup(
            [
                "include_comments" => $factory->select($this->plugin->txt('include_comments'), $options)->withValue($correctionSettings->getIncludeComments()),
                "include_comment_ratings" => $factory->select(
                    sprintf($this->plugin->txt('include_comment_ratings'), $correctionSettings->getPositiveRating(), $correctionSettings->getNegativeRating()), $options)->withValue($correctionSettings->getIncludeCommentRatings()),
                "include_comment_points" => $factory->select($this->plugin->txt('include_comment_points'), $options)->withValue($correctionSettings->getIncludeCommentPoints()),
                "include_criteria_points" => $factory->select($this->plugin->txt('include_criteria_points'), $options)->withValue($correctionSettings->getIncludeCriteriaPoints()),
            ],
            $this->plugin->txt('fixed_inclusions'),
            $this->plugin->txt('fixed_inclusions_info')
        );
        // strange but effective
        if (!$correctionSettings->getFixedInclusions()) {
            $fields['fixed_inclusions'] = $fields['fixed_inclusions']->withValue(null);
        }

        $sections['rating'] = $factory->section($fields, $this->plugin->txt('rating_settings'));

        // Stitch decision

        $fields = [];
        $fields['stitch_when_distance'] = $factory->optionalGroup(
            [
                "max_auto_distance" => $factory->text($this->plugin->txt('max_auto_distance'), $this->plugin->txt('max_auto_distance_info'))
                    ->withAdditionalTransformation($this->refinery->kindlyTo()->float())
                    ->withRequired(true)
                    ->withValue((string) (empty($correctionSettings->getMaxAutoDistance()) ? '0.0' : $correctionSettings->getMaxAutoDistance()))],
            $this->plugin->txt('stitch_when_distance')
        );
        // strange but effective
        if (!$correctionSettings->getStitchWhenDistance()) {
            $fields['stitch_when_distance'] = $fields['stitch_when_distance']->withValue(null);
        }

        $fields['stitch_when_decimals'] = $factory->checkbox($this->plugin->txt('stitch_when_decimals'))
            ->withValue((bool) $correctionSettings->getStitchWhenDecimals());

        $sections['stitch'] = $factory->section($fields, $this->plugin->txt('settings_stitch_required'));

        $form = $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {
            $correctionSettings->setRequiredCorrectors((int) $data['correction']['required_correctors']);
            $correctionSettings->setAssignMode((string) $data['correction']['assign_mode']);
            $correctionSettings->setMutualVisibility((int) $data['correction']['mutual_visibility']);
            $correctionSettings->setAnonymizeCorrectors((int) $data['correction']['anonymize_correctors']);
            if (isset($data['correction']['reports_enabled']) && is_array($data['correction']['reports_enabled'])) {
                $correctionSettings->setReportsEnabled(true);
                $date = $data['correction']['reports_enabled']['reports_available_start'];
                $correctionSettings->setReportsAvailableStart($date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : null);
            } else {
                $correctionSettings->setReportsEnabled(false);
            }

            $correctionSettings->setPositiveRating((string) $data['rating']['positive_rating']);
            $correctionSettings->setNegativeRating((string) $data['rating']['negative_rating']);
            $correctionSettings->setMaxPoints((int) $data['rating']['max_points']);
            $correctionSettings->setCriteriaMode((string) $data['rating']['criteria_mode']);
            if (isset($data['rating']['fixed_inclusions']) && is_array($data['rating']['fixed_inclusions'])) {
                $correctionSettings->setFixedInclusions(true);
                $correctionSettings->setIncludeComments((int) $data['rating']['fixed_inclusions']['include_comments']);
                $correctionSettings->setIncludeCommentRatings((int) $data['rating']['fixed_inclusions']['include_comment_ratings']);
                $correctionSettings->setIncludeCommentPoints((int) $data['rating']['fixed_inclusions']['include_comment_points']);
                $correctionSettings->setIncludeCriteriaPoints((int) $data['rating']['fixed_inclusions']['include_criteria_points']);
            } else {
                $correctionSettings->setFixedInclusions(false);
            }

            if (isset($data['stitch']['stitch_when_distance']) && is_array($data['stitch']['stitch_when_distance'])) {
                $correctionSettings->setStitchWhenDistance(true);
                $correctionSettings->setMaxAutoDistance((float) $data['stitch']['stitch_when_distance']['max_auto_distance']);
            } else {
                $correctionSettings->setStitchWhenDistance(false);
            }
            $correctionSettings->setStitchWhenDecimals(!empty($data['stitch']['stitch_when_decimals']));
            $task_repo->save($correctionSettings);

            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}
