<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\Task\AssessmentStatus\FullService as StatusService;
use Edutiek\AssessmentService\System\Data\FormattingOptions;
use Edutiek\AssessmentService\System\Data\HeadlineScheme;
use Edutiek\AssessmentService\EssayTask\Data\WritingSettings;
use Edutiek\AssessmentService\EssayTask\Data\WritingType;
use Edutiek\AssessmentService\EssayTask\WritingSettings\FullService as WritingSettingsService;
use Edutiek\AssessmentService\System\Entity\FullService as EntityService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;

/**
 * Technical settings
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\TechnicalSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class TechnicalSettingsGUI extends BaseGUI
{
    private WritingSettingsService $writing_settings_service;
    private EntityService $entity_service;
    private StatusService $status_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->writing_settings_service = $this->essay_task_api->writingSettings();
        $this->entity_service = $this->system_api->entity();
        $this->status_service = $this->task_api->assessmentStatus();
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
        $writing_settings = $this->writing_settings_service->get();
        $has_comments = $this->status_service->hasComments();

        $factory = $this->ui_factory->input()->field();

        $sections = [];

        // Editor
        if ($writing_settings->getWritingType() == WritingType::ESSAY_EDITOR) {
            $fields = [];
            $fields['headline_scheme'] = $factory->select(
                $this->plugin->txt('headline_scheme'),
                [
                    HeadlineScheme::SINGLE->value => $this->plugin->txt('headline_scheme_single'),
                    HeadlineScheme::THREE->value => $this->plugin->txt('headline_scheme_three'),
                    HeadlineScheme::NUMERIC->value => $this->plugin->txt('headline_scheme_numeric'),
                    HeadlineScheme::EDUTIEK->value => $this->plugin->txt('headline_scheme_edutiek'),
                ],
                $this->plugin->txt('headline_scheme_description')
            )
                ->withRequired(true)
                ->withValue($writing_settings->getHeadlineScheme()->value);

            $fields['formatting_options'] = $factory->radio($this->plugin->txt('formatting_options'))
                ->withRequired(true)
                ->withOption(
                    FormattingOptions::NONE->value,
                    $this->plugin->txt('formatting_options_none'),
                    $this->plugin->txt('formatting_options_none_info')
                )
                ->withOption(
                    FormattingOptions::MINIMAL->value,
                    $this->plugin->txt('formatting_options_minimal'),
                    $this->plugin->txt('formatting_options_minimal_info')
                )
                ->withOption(
                    FormattingOptions::MEDIUM->value,
                    $this->plugin->txt('formatting_options_medium'),
                    $this->plugin->txt('formatting_options_medium_info')
                )
                ->withOption(
                    FormattingOptions::FULL->value,
                    $this->plugin->txt('formatting_options_full'),
                    $this->plugin->txt('formatting_options_full_info')
                )
                ->withValue($writing_settings->getFormattingOptions()->value);

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
                ->withValue((string) $writing_settings->getNoticeBoards());

            $fields['copy_allowed'] = $factory->checkbox(
                $this->plugin->txt('copy_allowed'),
                $this->plugin->txt('copy_allowed_info')
            )
                ->withValue($writing_settings->getCopyAllowed());

            $fields['allow_spellcheck'] = $factory->checkbox(
                $this->plugin->txt('allow_spellcheck'),
                $this->plugin->txt('allow_spellcheck_info')
            )
                ->withValue($writing_settings->getAllowSpellcheck());

            $sections['editor'] = $factory->section($fields, $this->plugin->txt('editor_settings'));

            // Processing

            $fields = [];

            $fields['add_paragraph_numbers'] = $factory->checkbox(
                $this->plugin->txt('add_paragraph_numbers'),
                $this->plugin->txt('add_paragraph_numbers_info')
            )
                ->withDisabled($has_comments)
                ->withValue($writing_settings->getAddParagraphNumbers());

            $fields['add_correction_margin'] = $factory->optionalGroup(
                [
                    'left_correction_margin' => $factory->numeric($this->plugin->txt('left_correction_margin'))
                        ->withAdditionalTransformation($this->refinery->kindlyTo()->int())
                        ->withAdditionalTransformation($this->refinery->int()->isLessThan(200))
                        ->withRequired(true)
                        ->withDisabled($has_comments)
                        ->withValue($writing_settings->getLeftCorrectionMargin()),
                    'right_correction_margin' => $factory->numeric($this->plugin->txt('right_correction_margin'))
                        ->withAdditionalTransformation($this->refinery->kindlyTo()->int())
                        ->withAdditionalTransformation($this->refinery->int()->isLessThan(200))
                        ->withRequired(true)
                        ->withDisabled($has_comments)
                        ->withValue($writing_settings->getRightCorrectionMargin()),
                ],
                $this->plugin->txt('add_correction_margin'),
                $this->plugin->txt('add_correction_margin_info'),
            )->withDisabled($has_comments);
            // strange but effective
            if (!$writing_settings->getAddCorrectionMargin()) {
                $fields['add_correction_margin'] = $fields['add_correction_margin']->withValue(null);
            }

            $sections['processing'] = $factory->section(
                $fields,
                $this->plugin->txt('processing_settings'),
                $this->plugin->txt('processing_settings_info')
            );
        }

        $form = $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {

            if ($writing_settings->getWritingType() == WritingType::ESSAY_EDITOR) {
                $writing_settings->setHeadlineScheme(HeadlineScheme::tryFrom($data['editor']['headline_scheme']) ?? HeadlineScheme::NUMERIC);
                $writing_settings->setFormattingOptions(FormattingOptions::tryFrom($data['editor']['formatting_options']) ?? FormattingOptions::FULL);
                $writing_settings->setNoticeBoards((int) $data['editor']['notice_boards']);
                $writing_settings->setCopyAllowed((bool) $data['editor']['copy_allowed']);
                $writing_settings->setAllowSpellcheck((bool) $data['editor']['allow_spellcheck']);

                if (!$has_comments) {
                    $writing_settings->setAddParagraphNumbers((bool) $data['processing']['add_paragraph_numbers']);
                    if (isset($data['processing']['add_correction_margin']) && is_array($data['processing']['add_correction_margin'])) {
                        $writing_settings->setAddCorrectionMargin(true);
                        $writing_settings->setLeftCorrectionMargin((int) $data['processing']['add_correction_margin']['left_correction_margin']);
                        $writing_settings->setRightCorrectionMargin((int) $data['processing']['add_correction_margin']['right_correction_margin']);
                    } else {
                        $writing_settings->setAddCorrectionMargin(false);
                    }
                }

                $this->entity_service->secure($writing_settings, WritingSettings::class);
                $this->writing_settings_service->save($writing_settings);
            }

            $this->success($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}
