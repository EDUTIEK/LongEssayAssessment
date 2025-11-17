<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\Assessment\Data\PdfSettings;
use Edutiek\AssessmentService\Assessment\PdfSettings\FullService as PdfSettingsService;
use Edutiek\AssessmentService\Task\AssessmentStatus\FullService as StatusService;
use Edutiek\AssessmentService\System\Entity\FullService as EntityService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\PdfCreation\PdfPurpose;
use ILIAS\UI\Component\Table\OrderingBinding;
use ILIAS\UI\Component\Table\OrderingRowBuilder;
use Generator;
use Edutiek\AssessmentService\Assessment\PdfCreation\PdfConfigPart;
use ILIAS\UI\URLBuilder;
use Edutiek\AssessmentService\Assessment\Data\PdfFormat;
use Edutiek\AssessmentService\Assessment\Data\PdfFeedbackMode;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Table\Ordering;
use ILIAS\UI\Component\Input\Container\Form\Form;

/**
 * Technical settings
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\DocumentationSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class DocumentationSettingsGUI extends BaseGUI
{
    private PdfSettingsService $pdf_settings_service;
    private EntityService $entity_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->pdf_settings_service = $this->assessment_api->pdfSettings();
        $this->entity_service = $this->system_api->entity();
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $this->initForNonTask();

        $cmd = $this->ctrl->getCmd('edit');
        switch ($cmd) {
            case "edit":
            case "updateCorrectionOrder":
            case "updateWritingOrder":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    protected function updateCorrectionOrder()
    {
        $this->updateOrder(PdfPurpose::CORRECTION);
    }

    protected function updateWritingOrder()
    {
        $this->updateOrder(PdfPurpose::WRITING);
    }

    protected function updateOrder(PdfPurpose $purpose)
    {
        if ($this->request->getMethod() == "POST") {
            $parts = $this->assessment_api->pdfCreation()->getSortedParts($purpose);
            $post = $this->request->getParsedBody() ?? [];
            $active = !empty($post) && isset($post['active'])
                ? $post['active']
                : array_map(
                    fn(PdfConfigPart $part) => $part->getKey(),
                    $parts
                ); // Set all active if no active parts are in post
            $i = 0; // Index if the order is missing

            foreach ($parts as $part) {
                $part->setIsActive(in_array($part->getKey(), $active));
                $part->setPosition($this->post->integer($part->getKey(), (++$i) * 10));
            }

            $this->assessment_api->pdfCreation()->saveSortedParts($purpose, $parts);
            $this->success($this->lng->txt("settings_saved"), true);
        }
        $this->ctrl->redirect($this, "edit");
    }

    protected function buildOrder(string $title, PdfPurpose $purpose, string $cmd): Ordering
    {
        $parts = $this->assessment_api->pdfCreation()->getSortedParts($purpose);

        $df = new \ILIAS\Data\Factory();
        $url_builder = new URLBuilder($df->uri(ILIAS_HTTP_PATH . '/' . $this->ctrl->getFormAction($this, $cmd)));

        $ordering = $this->ui_factory->table()->ordering(
            $title,
            [
                "active" => $this->plugin_ui_factory->table()->column()->checkbox($this->lng->txt('active'), "active"),
                "title" => $this->ui_factory->table()->column()->text($this->lng->txt('title')),
            ],
            $this->orderingBinding($parts),
            $url_builder->buildURI(),
        )->withRequest($this->request);

        return $ordering;
    }

    private function orderingBinding(array $parts)
    {
        return new class ($parts) implements OrderingBinding {
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
    protected function edit(): void
    {
        $form = $this->buildForm();
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
            $result = $form->getInputGroup()->getContent();

            if ($result->isOK()) {
                $this->updateSettings($data);
            }
        }

        $written_order = $this->buildOrder($this->plugin->txt('written_pdf'), PdfPurpose::WRITING, "updateWritingOrder");
        $correction_order = $this->buildOrder($this->plugin->txt('corrected_pdf'), PdfPurpose::CORRECTION, "updateCorrectionOrder");

        $this->tpl->setContent($this->renderer->render([$form, $correction_order]));
    }

    private function buildForm(): Form
    {
        $pdf_settings = $this->pdf_settings_service->get();
        $factory = $this->ui_factory->input()->field();

        $sections = [];
        $fields = [];

        $fields['format'] = $factory->radio(
            $this->plugin->txt('pdf_format'),
            $this->plugin->txt('pdf_format_info'),
        )->withOption(PdfFormat::EDUTIEK->value, $this->plugin->txt('pdf_format_edutiek'))
            ->withOption(PdfFormat::BY->value, $this->plugin->txt('pdf_format_by'))
            ->withOption(PdfFormat::NRW->value, $this->plugin->txt('pdf_format_nrw'))
            ->withValue($pdf_settings->getFormat()->value)
            ->withRequired(true);

        $fields['feedback_mode'] = $factory->radio(
            $this->plugin->txt('pdf_feedback_mode'),
            $this->plugin->txt('pdf_feedback_mode_info'),
        )->withOption(PdfFeedbackMode::SIDE_BY_SIDE->value, $this->plugin->txt('pdf_feedback_mode_sidebyside'))
            ->withOption(PdfFeedbackMode::SEQUENCE->value, $this->plugin->txt('pdf_feedback_mode_sequence'))
            ->withValue($pdf_settings->getFeedbackMode()->value)
            ->withRequired(true);

        $sections['pdf'] = $factory->section(
            $fields,
            $this->plugin->txt('pdf_settings'),
            $this->plugin->txt('pdf_settings_info')
        );

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }

    private function updateSettings(array $data): void
    {
        $pdf_settings = $this->pdf_settings_service->get();

        $pdf_settings->setFormat(PdfFormat::tryFrom($data['pdf']['format']) ?? PdfFormat::EDUTIEK);
        $pdf_settings->setFeedbackMode(
            PdfFeedbackMode::tryFrom($data['pdf']['feedback_mode']) ?? PdfFeedbackMode::SIDE_BY_SIDE
        );

        $this->entity_service->secure($pdf_settings, PdfSettings::class);
        $this->pdf_settings_service->save($pdf_settings);

        $this->success($this->lng->txt("settings_saved"), true);
        $this->ctrl->redirect($this, "edit");
    }
}
