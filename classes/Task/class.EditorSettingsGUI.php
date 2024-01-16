<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\EditorSettings;
use \ilUtil;

/**
 * Features of the editor for the writers
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\EditorSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class EditorSettingsGUI extends BaseGUI
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

		$editorSettings = $task_repo->getEditorSettingsById($this->object->getId());

        $factory = $this->uiFactory->input()->field();

        $sections = [];

        // Object
        $fields = [];
        $fields['headline_scheme'] = $factory->select($this->plugin->txt('headline_scheme'),
            [
                EditorSettings::HEADLINE_SCHEME_NONE => $this->plugin->txt('headline_scheme_none'),
                EditorSettings::HEADLINE_SCHEME_NUMERIC => $this->plugin->txt('headline_scheme_numeric'),
                EditorSettings::HEADLINE_SCHEME_EDUTIEK => $this->plugin->txt('headline_scheme_edutiek'),
            ])
            ->withRequired(true)
            ->withValue($editorSettings->getHeadlineScheme());

        $fields['formatting_options'] = $factory->radio($this->plugin->txt('formatting_options'))
            ->withRequired(true)
            ->withOption( EditorSettings::FORMATTING_OPTIONS_NONE, $this->plugin->txt('formatting_options_none'),
                $this->plugin->txt('formatting_options_none_info'))
            ->withOption( EditorSettings::FORMATTING_OPTIONS_MINIMAL, $this->plugin->txt('formatting_options_minimal'),
                $this->plugin->txt('formatting_options_minimal_info'))
            ->withOption( EditorSettings::FORMATTING_OPTIONS_MEDIUM, $this->plugin->txt('formatting_options_medium'),
                $this->plugin->txt('formatting_options_medium_info'))
            ->withOption( EditorSettings::FORMATTING_OPTIONS_FULL, $this->plugin->txt('formatting_options_full'),
                $this->plugin->txt('formatting_options_full_info'))
            ->withValue($editorSettings->getFormattingOptions());

        $fields['notice_boards'] = $factory->select($this->plugin->txt('notice_boards'),
            [
                '0' => '0',
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5'
            ])
            ->withRequired(true)
            ->withValue((string) $editorSettings->getNoticeBoards());

        $fields['copy_allowed'] = $factory->checkbox($this->plugin->txt('copy_allowed'), $this->plugin->txt('copy_allowed_info'))
            ->withValue($editorSettings->isCopyAllowed());

        $sections['editor'] = $factory->section($fields, $this->plugin->txt('editor_settings'));

        $form = $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {
            $editorSettings->setHeadlineScheme($data['editor']['headline_scheme']);
            $editorSettings->setFormattingOptions($data['editor']['formatting_options']);
            $editorSettings->setNoticeBoards((int) $data['editor']['notice_boards']);
            $editorSettings->setCopyAllowed($data['editor']['copy_allowed']);
			$task_repo->save($editorSettings);

            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}