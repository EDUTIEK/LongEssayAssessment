<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\EditorSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;

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
        $essay_repo = $this->localDI->getEssayRepo();

        $task_settings = $task_repo->getTaskSettingsById($this->object->getId());
        $editor_settings = $task_repo->getEditorSettingsById($this->object->getId());
        $pdf_settings = $task_repo->getPdfSettingsById($this->object->getId());
        $has_comments = $essay_repo->hasCorrectorCommentsByTaskId($this->object->getId());

        $factory = $this->uiFactory->input()->field();

        $sections = [];

        // Editor
        if ($task_settings->getTaskType() == TaskSettings::TYPE_ESSAY_EDITOR) {
            $fields = [];
            $fields['headline_scheme'] = $factory->select(
                $this->plugin->txt('headline_scheme'),
                [
                    EditorSettings::HEADLINE_SCHEME_SINGLE => $this->plugin->txt('headline_scheme_single'),
                    EditorSettings::HEADLINE_SCHEME_THREE => $this->plugin->txt('headline_scheme_three'),
                    EditorSettings::HEADLINE_SCHEME_NUMERIC => $this->plugin->txt('headline_scheme_numeric'),
                    EditorSettings::HEADLINE_SCHEME_EDUTIEK => $this->plugin->txt('headline_scheme_edutiek'),
                ],
                $this->plugin->txt('headline_scheme_description')
            )
                                                 ->withRequired(true)
                                                 ->withValue($editor_settings->getHeadlineScheme());

            $fields['formatting_options'] = $factory->radio($this->plugin->txt('formatting_options'))
                                                    ->withRequired(true)
                                                    ->withOption(
                                                        EditorSettings::FORMATTING_OPTIONS_NONE,
                                                        $this->plugin->txt('formatting_options_none'),
                                                        $this->plugin->txt('formatting_options_none_info')
                                                    )
                                                    ->withOption(
                                                        EditorSettings::FORMATTING_OPTIONS_MINIMAL,
                                                        $this->plugin->txt('formatting_options_minimal'),
                                                        $this->plugin->txt('formatting_options_minimal_info')
                                                    )
                                                    ->withOption(
                                                        EditorSettings::FORMATTING_OPTIONS_MEDIUM,
                                                        $this->plugin->txt('formatting_options_medium'),
                                                        $this->plugin->txt('formatting_options_medium_info')
                                                    )
                                                    ->withOption(
                                                        EditorSettings::FORMATTING_OPTIONS_FULL,
                                                        $this->plugin->txt('formatting_options_full'),
                                                        $this->plugin->txt('formatting_options_full_info')
                                                    )
                                                    ->withValue($editor_settings->getFormattingOptions());

            $fields['notice_boards'] = $factory->select(
                $this->plugin->txt('notice_boards'),
                [
                    '0' => '0',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5'
                ]
            )
                                               ->withRequired(true)
                                               ->withValue((string) $editor_settings->getNoticeBoards());

            $fields['copy_allowed'] = $factory->checkbox($this->plugin->txt('copy_allowed'), $this->plugin->txt('copy_allowed_info'))
                                              ->withValue($editor_settings->isCopyAllowed());

            $fields['allow_spellcheck'] = $factory->checkbox($this->plugin->txt('allow_spellcheck'), $this->plugin->txt('allow_spellcheck_info'))
                                                  ->withValue($editor_settings->getAllowSpellcheck());

            $sections['editor'] = $factory->section($fields, $this->plugin->txt('editor_settings'));

            // Processing

            $fields = [];

            $fields['add_paragraph_numbers'] = $factory->checkbox(
                $this->plugin->txt('add_paragraph_numbers'),
                $this->plugin->txt('add_paragraph_numbers_info')
            )
                                                       ->withDisabled($has_comments)
                                                       ->withValue($editor_settings->getAddParagraphNumbers());

            $fields['add_correction_margin'] = $factory->optionalGroup(
                [
                    'left_correction_margin' => $factory->numeric($this->plugin->txt('left_correction_margin'))
                                                        ->withAdditionalTransformation($this->refinery->kindlyTo()->int())
                                                        ->withRequired(true)
                                                        ->withDisabled($has_comments)
                                                        ->withValue($editor_settings->getLeftCorrectionMargin()),
                    'right_correction_margin' => $factory->numeric($this->plugin->txt('right_correction_margin'))
                                                         ->withAdditionalTransformation($this->refinery->kindlyTo()->int())
                                                         ->withRequired(true)
                                                         ->withDisabled($has_comments)
                                                         ->withValue($editor_settings->getRightCorrectionMargin()),
                ],
                $this->plugin->txt('add_correction_margin'),
                $this->plugin->txt('add_correction_margin_info'),
            )->withDisabled($has_comments);
            // strange but effective
            if (!$editor_settings->getAddCorrectionMargin()) {
                $fields['add_correction_margin'] = $fields['add_correction_margin']->withValue(null);
            }

            $sections['processing'] = $factory->section(
                $fields,
                $this->plugin->txt('processing_settings'),
                $this->plugin->txt('processing_settings_info')
            );
        }

        // PDF generation

        $fields = [];

        $fields['add_header'] = $factory->checkbox($this->plugin->txt('pdf_add_header'), $this->plugin->txt('pdf_add_header_info'))
            ->withValue($pdf_settings->getAddHeader());

        $fields['add_footer'] = $factory->checkbox($this->plugin->txt('pdf_add_footer'), $this->plugin->txt('pdf_add_footer_info'))
            ->withValue($pdf_settings->getAddFooter());

        $fields['top_margin'] = $factory->numeric($this->plugin->txt('pdf_top_margin'), $this->plugin->txt('pdf_top_margin_info'))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->localDI->constraints()->minimumInteger(5))
            ->withRequired(true)
            ->withValue($pdf_settings->getTopMargin());

        $fields['bottom_margin'] = $factory->numeric($this->plugin->txt('pdf_bottom_margin'), $this->plugin->txt('pdf_bottom_margin_info'))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->localDI->constraints()->minimumInteger(5))
            ->withRequired(true)
            ->withValue($pdf_settings->getBottomMargin());

        $fields['left_margin'] = $factory->numeric($this->plugin->txt('pdf_left_margin'), $this->plugin->txt('pdf_left_margin_info'))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->localDI->constraints()->minimumInteger(5))
            ->withRequired(true)
            ->withValue($pdf_settings->getLeftMargin());

        $fields['right_margin'] = $factory->numeric($this->plugin->txt('pdf_right_margin'), $this->plugin->txt('pdf_right_margin_info'))
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->localDI->constraints()->minimumInteger(5))
            ->withRequired(true)
            ->withValue($pdf_settings->getRightMargin());

        $sections['pdf'] = $factory->section(
            $fields,
            $this->plugin->txt('pdf_settings'),
            $this->plugin->txt('pdf_settings_info')
        );

        $form = $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {

            if ($task_settings->getTaskType() == TaskSettings::TYPE_ESSAY_EDITOR) {
                $editor_settings->setHeadlineScheme($data['editor']['headline_scheme']);
                $editor_settings->setFormattingOptions($data['editor']['formatting_options']);
                $editor_settings->setNoticeBoards((int) $data['editor']['notice_boards']);
                $editor_settings->setCopyAllowed((bool) $data['editor']['copy_allowed']);
                $editor_settings->setAllowSpellcheck((bool) $data['editor']['allow_spellcheck']);

                if (!$has_comments) {
                    $editor_settings->setAddParagraphNumbers((bool) $data['processing']['add_paragraph_numbers']);
                    if (isset($data['processing']['add_correction_margin']) && is_array($data['processing']['add_correction_margin'])) {
                        $editor_settings->setAddCorrectionMargin(true);
                        $editor_settings->setLeftCorrectionMargin((int) $data['processing']['add_correction_margin']['left_correction_margin']);
                        $editor_settings->setRightCorrectionMargin((int) $data['processing']['add_correction_margin']['right_correction_margin']);
                    } else {
                        $editor_settings->setAddCorrectionMargin(false);
                    }
                }
                $task_repo->save($editor_settings);
            }

            $pdf_settings->setAddHeader((bool) $data['pdf']['add_header']);
            $pdf_settings->setAddFooter((bool) $data['pdf']['add_footer']);
            $pdf_settings->setTopMargin((int) $data['pdf']['top_margin']);
            $pdf_settings->setBottomMargin((int) $data['pdf']['bottom_margin']);
            $pdf_settings->setLeftMargin((int) $data['pdf']['left_margin']);
            $pdf_settings->setRightMargin((int) $data['pdf']['right_margin']);
            $task_repo->save($pdf_settings);

            $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}
