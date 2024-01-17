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

        // Editor

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

        // Processing

        $fields = [];

        $fields['add_paragraph_numbers'] = $factory->checkbox($this->plugin->txt('add_paragraph_numbers'))
            ->withValue($editorSettings->getAddParagraphNumbers());

        $fields['top_margin'] = $factory->numeric($this->plugin->txt('top_margin'))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(14))
            ->withRequired(true)
            ->withValue($editorSettings->getTopMargin());

        $fields['bottom_margin'] = $factory->numeric($this->plugin->txt('bottom_margin'))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(9))
            ->withRequired(true)
            ->withValue($editorSettings->getBottomMargin());

        $fields['left_margin'] = $factory->numeric($this->plugin->txt('left_margin'))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withRequired(true)
            ->withValue($editorSettings->getLeftMargin());

        $fields['right_margin'] = $factory->numeric($this->plugin->txt('right_margin'))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withRequired(true)
            ->withValue($editorSettings->getRightMargin());

        $sections['processing'] = $factory->section($fields,
            $this->plugin->txt('processing_settings'),
            $this->plugin->txt('processing_settings_info')
        );

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
            $editorSettings->setCopyAllowed((bool) $data['editor']['copy_allowed']);
            $editorSettings->setAddParagraphNumbers((bool) $data['processing']['add_paragraph_numbers']);
            $editorSettings->setTopMargin((int) $data['processing']['top_margin']);
            $editorSettings->setBottomMargin((int) $data['processing']['bottom_margin']);
            $editorSettings->setLeftMargin((int) $data['processing']['left_margin']);
            $editorSettings->setRightMargin((int) $data['processing']['right_margin']);
			$task_repo->save($editorSettings);

            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}