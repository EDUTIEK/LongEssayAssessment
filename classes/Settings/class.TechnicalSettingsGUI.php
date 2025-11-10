<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\Assessment\Data\PdfSettings;
use Edutiek\AssessmentService\Assessment\PdfSettings\FullService as PdfSettingsService;
use Edutiek\AssessmentService\Task\AssessmentStatus\FullService as StatusService;
use Edutiek\AssessmentService\System\Data\FormattingOptions;
use Edutiek\AssessmentService\System\Data\HeadlineScheme;
use Edutiek\AssessmentService\EssayTask\Data\WritingSettings;
use Edutiek\AssessmentService\EssayTask\Data\WritingType;
use Edutiek\AssessmentService\EssayTask\WritingSettings\FullService as WritingSettingsService;
use Edutiek\AssessmentService\System\Entity\FullService as EntityService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\PdfCreation\PdfPurpose;
use ILIAS\Data\URI;
use ILIAS\UI\Component\Table\OrderingBinding;
use ILIAS\UI\Component\Table\OrderingRowBuilder;
use Generator;
use Edutiek\AssessmentService\Assessment\PdfCreation\PdfConfigPart;
use ILIAS\Data\Factory;
use ILIAS\UI\URLBuilder;
use Edutiek\AssessmentService\Assessment\Data\PdfFormat;
use Edutiek\AssessmentService\Assessment\Data\PdfFeedbackMode;

/**
 * Technical settings
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\TechnicalSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class TechnicalSettingsGUI extends BaseGUI
{
    private WritingSettingsService $writing_settings_service;
    private PdfSettingsService $pdf_settings_service;
    private EntityService $entity_service;
    private StatusService $status_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->writing_settings_service = $this->essay_task_api->writingSettings();
        $this->pdf_settings_service = $this->assessment_api->pdfSettings();
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
        $this->initForNonTask();

        $cmd = $this->ctrl->getCmd('editSettings');
        switch ($cmd) {
            case "editSettings":
            case "configCorrectionPDF":
            case "configWritingPDF":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    protected function configCorrectionPDF()
    {
        $this->configPDF(
            $this->plugin->txt('corrected_pdf'),
            PdfPurpose::CORRECTION
        );
    }

    protected function configWritingPDF()
    {
        $this->configPDF(
            $this->plugin->txt('written_pdf'),
            PdfPurpose::WRITING
        );
    }

    protected function configPDF(string $title, PdfPurpose $purpose)
    {
        $this->tabs->setBackTarget($this->lng->txt('back'), $this->ctrl->getLinkTarget($this));

        $parts = $this->assessment_api->pdfCreation()->getSortedParts($purpose);

        if ($this->request->getMethod() == "POST") {
            $post = $this->request->getParsedBody() ?? [];
            $active = !empty($post) && isset($post['active'])
                ? $post['active']
                : array_map(fn (PdfConfigPart $part) => $part->getKey(), $parts); // Set all active if no active parts are in post
            $i = 0; // Index if the order is missing

            foreach ($parts as $part) {
                $part->setIsActive(in_array($part->getKey(), $active));
                $part->setPosition($this->post->integer($part->getKey(), (++$i)*10));
            }

            $this->assessment_api->pdfCreation()->saveSortedParts($purpose, $parts);
            $this->success($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, $this->ctrl->getCmd('editSettings'));
            return;
        }

        $df = new \ILIAS\Data\Factory();
        $url_builder = new URLBuilder($df->uri($this->request->getUri()->__toString()));

        $ordering = $this->ui_factory->table()->ordering(
            $title,
            [
                "active" => $this->plugin_ui_factory->table()->column()->checkbox($this->lng->txt('active'), "active"),
                "title" => $this->ui_factory->table()->column()->text($this->lng->txt('title')),
            ],
            $this->orderingBinding($parts),
            $url_builder->buildURI(),
        )->withRequest($this->request);

        $this->tpl->setContent($this->renderer->renderAsync($ordering));
    }

    private function orderingBinding(array $parts)
    {
        return new class($parts) implements OrderingBinding {
            /**
             * @param PdfConfigPart[] $parts
             */
            public function __construct(private array $parts)
            {
            }

            public function getRows(OrderingRowBuilder $row_builder, array $visible_column_ids): Generator
            {
                foreach ($this->parts as $part) {
                    yield $row_builder->buildOrderingRow(
                        $part->getKey(),
                        [
                            "active" => [$part->getKey(), $part->getIsActive()],
                            "title" => $part->getTitle(),
                        ]
                    )->withPosition($part->getPosition());
                }
            }
        };
    }

    /**
     * Edit and save the settings
     */
    protected function editSettings()
    {
        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->plugin->txt('config_corrected_pdf'),
            $this->ctrl->getLinkTarget($this, "configCorrectionPDF")
        ));

        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->plugin->txt('config_written_pdf'),
            $this->ctrl->getLinkTarget($this, "configWritingPDF")
        ));

        $writing_settings = $this->writing_settings_service->get();
        $pdf_settings = $this->pdf_settings_service->get();
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
                        ->withRequired(true)
                        ->withDisabled($has_comments)
                        ->withValue($writing_settings->getLeftCorrectionMargin()),
                    'right_correction_margin' => $factory->numeric($this->plugin->txt('right_correction_margin'))
                        ->withAdditionalTransformation($this->refinery->kindlyTo()->int())
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

        // PDF generation

        $fields = [];

        $fields['format'] = $factory->select(
            $this->plugin->txt('pdf_format'),
            [PdfFormat::EDUTIEK->value => $this->plugin->txt('pdf_format_edutiek'),
             PdfFormat::BY->value => $this->plugin->txt('pdf_format_by'),
             PdfFormat::NRW->value => $this->plugin->txt('pdf_format_nrw')],
            $this->plugin->txt('pdf_format_info')
        )->withValue($pdf_settings->getFormat()->value)
         ->withRequired(true);

        $fields['feedback_mode'] = $factory->select(
            $this->plugin->txt('pdf_feedback_mode'),
            [PdfFeedbackMode::SIDE_BY_SIDE->value => $this->plugin->txt('pdf_feedback_mode_sidebyside'),
             PdfFeedbackMode::SEQUENCE->value => $this->plugin->txt('pdf_feedback_mode_sequence')],
            $this->plugin->txt('pdf_feedback_mode_info')
        )->withValue($pdf_settings->getFeedbackMode()->value)
         ->withRequired(true);

        $fields['add_header'] = $factory->checkbox(
            $this->plugin->txt('pdf_add_header'),
            $this->plugin->txt('pdf_add_header_info')
        )
            ->withValue($pdf_settings->getAddHeader());

        $fields['add_footer'] = $factory->checkbox(
            $this->plugin->txt('pdf_add_footer'),
            $this->plugin->txt('pdf_add_footer_info')
        )
            ->withValue($pdf_settings->getAddFooter());

        $fields['top_margin'] = $factory->numeric(
            $this->plugin->txt('pdf_top_margin'),
            $this->plugin->txt('pdf_top_margin_info')
        )
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->constraints->minimumInteger(5))
            ->withRequired(true)
            ->withValue($pdf_settings->getTopMargin());

        $fields['bottom_margin'] = $factory->numeric(
            $this->plugin->txt('pdf_bottom_margin'),
            $this->plugin->txt('pdf_bottom_margin_info')
        )
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->constraints->minimumInteger(5))
            ->withRequired(true)
            ->withValue($pdf_settings->getBottomMargin());

        $fields['left_margin'] = $factory->numeric(
            $this->plugin->txt('pdf_left_margin'),
            $this->plugin->txt('pdf_left_margin_info')
        )
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->constraints->minimumInteger(5))
            ->withRequired(true)
            ->withValue($pdf_settings->getLeftMargin());

        $fields['right_margin'] = $factory->numeric(
            $this->plugin->txt('pdf_right_margin'),
            $this->plugin->txt('pdf_right_margin_info')
        )
            ->withAdditionalTransformation($this->refinery->to()->int())
            ->withAdditionalTransformation($this->constraints->minimumInteger(5))
            ->withRequired(true)
            ->withValue($pdf_settings->getRightMargin());

        $sections['pdf'] = $factory->section(
            $fields,
            $this->plugin->txt('pdf_settings'),
            $this->plugin->txt('pdf_settings_info')
        );

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

            $pdf_settings->setAddHeader((bool) $data['pdf']['add_header']);
            $pdf_settings->setAddFooter((bool) $data['pdf']['add_footer']);
            $pdf_settings->setTopMargin((int) $data['pdf']['top_margin']);
            $pdf_settings->setBottomMargin((int) $data['pdf']['bottom_margin']);
            $pdf_settings->setLeftMargin((int) $data['pdf']['left_margin']);
            $pdf_settings->setRightMargin((int) $data['pdf']['right_margin']);
            $pdf_settings->setFormat(PdfFormat::tryFrom($data['pdf']['format']) ?? PdfFormat::EDUTIEK);
            $pdf_settings->setFeedbackMode(PdfFeedbackMode::tryFrom($data['pdf']['feedback_mode']) ?? PdfFeedbackMode::SIDE_BY_SIDE);

            $this->entity_service->secure($pdf_settings, PdfSettings::class);
            $this->pdf_settings_service->save($pdf_settings);

            $this->success($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "editSettings");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}
