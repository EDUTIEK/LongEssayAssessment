<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use \ilUtil;

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
            ->withOption( CorrectionSettings::ASSIGN_MODE_RANDOM_EQUAL, $this->plugin->txt('assign_mode_random_equal'),
                $this->plugin->txt('assign_mode_random_equal_info'))
            ->withValue($correctionSettings->getAssignMode());

        $fields['mutual_visibility'] = $factory->checkbox($this->plugin->txt('mutual_visibility'), $this->plugin->txt('mutual_visibility_info'))
            ->withValue((bool) $correctionSettings->getMutualVisibility());

        $fields['max_points'] = $factory->numeric($this->plugin->txt('max_points'))
            ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(0))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withRequired(true)
            ->withValue($correctionSettings->getMaxPoints());

        $sections['correction'] = $factory->section($fields, $this->plugin->txt('correction_settings'));

        // Stitch decision

        $fields = [];
        $fields['stitch_when_distance'] = $factory->optionalGroup([
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
            $correctionSettings->setMaxPoints((int) $data['correction']['max_points']);
            if (isset($data['stitch']['stitch_when_distance']) && is_array($data['stitch']['stitch_when_distance'])) {
                $correctionSettings->setStitchWhenDistance(true);
                $correctionSettings->setMaxAutoDistance((float) $data['stitch']['stitch_when_distance']['max_auto_distance']);
            }
            else {
                $correctionSettings->setStitchWhenDistance(false);
            }
            $correctionSettings->setStitchWhenDecimals(!empty($data['stitch']['stitch_when_decimals']));
			$task_repo->save($correctionSettings);

            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}