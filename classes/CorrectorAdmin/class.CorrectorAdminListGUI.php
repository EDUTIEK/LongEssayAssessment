<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ILIAS\UI\Component\Input\Container\Filter\Standard;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;

class CorrectorAdminListGUI extends WriterListGUI
{

    /**
     * @var CorrectorSummary[][]
     */
    private $summaries = [];

    /**
     * @var \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector[]
     */
    private $correctors = [];

    /**
     * @var CorrectorAssignment[][]
     */
    private $assignments = [];

    /**
     * @var \ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings
     */
    private $correction_settings;


    /**
     * @var int[]
     */
    private array $correction_status_stitches = [];

    public function __construct(object $parent, string $parent_cmd, \ilLongEssayAssessmentPlugin $plugin, CorrectionSettings $correction_settings)
    {
        parent::__construct($parent, $parent_cmd, $plugin);
        $this->correction_settings = $correction_settings;
    }


    public function getContent():string
    {
        $this->loadUserData();
        $this->sortWriter();

        $items = [];
        $modals = [];

        $count_total = count($this->writers);
        $count_filtered = 0;
        $filter_gui = $this->filterForm();
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? [];

        foreach ($this->writers as $writer) {
            if(!$this->filter($filter_data, $writer)) {
                continue;
            }
            $count_filtered++;

            $actions = [];
            $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_correction'), $this->getViewCorrectionAction($writer));

            if($this->canDownloadWrittenPdf($writer)) {
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('download_written_pdf'), $this->getDownloadWrittenPdfAction($writer));
            }

            if($this->canDownloadCorrectedPdf($writer)) {
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('download_corrected_pdf'), $this->getDownloadCorrectedPdfAction($writer));
            }
            
            if ($this->hasCorrectionStatusStitchDecided($writer)) {
                $sight_modal = $this->uiFactory->modal()->lightbox($this->uiFactory->modal()->lightboxTextPage(
                    $this->localDI->getDataService($writer->getTaskId())->cleanupRichText($this->essays[$writer->getId()]->getStitchComment()),
                    $this->getWriterName($writer, true). $this->getWriterAnchor($writer),
                ));
                $modals[] = $sight_modal;
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_stitch_comment'), '')->withOnClick($sight_modal->getShowSignal());
            }

            $mail_for_essay_modal = $this->uiFactory->modal()->roundtrip("", [])
                  ->withAsyncRenderUrl($this->getMailForEssayAction($writer));

            $modals[] = $mail_for_essay_modal;
            $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('mail_to_writer_or_corrector'), "")
                 ->withOnClick($mail_for_essay_modal->getShowSignal());

            $change_corrector_modal = $this->uiFactory->modal()->roundtrip("", [])
                ->withAsyncRenderUrl($this->getChangeCorrectorAction($writer));

            $modals[] = $change_corrector_modal;
            $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('change_corrector'), "")
                ->withOnClick($change_corrector_modal->getShowSignal());

            if($this->hasCorrectionStatusStitch($writer)) {
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('draw_stitch_decision'), $this->getCorrectionStatusStitchAction($writer));
            }

            $properties = [
                $this->plugin->txt("pseudonym") => $writer->getPseudonym(),
                $this->plugin->txt("status") => $this->essayStatus($writer)
            ];

            foreach($this->getAssignmentsByWriter($writer) as $assignment) {
                if ($this->correction_settings->getRequiredCorrectors() == 1) {
                    $pos = $this->plugin->txt("assignment_pos_single");
                } else {
                    switch($assignment->getPosition()) {
                        case 0: $pos = $this->plugin->txt("assignment_pos_first");
                            break;
                        case 1: $pos = $this->plugin->txt("assignment_pos_second");
                            break;
                        default: $pos = $this->plugin->txt("assignment_pos_other");
                            break;
                    }
                }
                $properties[$pos] = $this->getAssignedCorrectorName($writer, $assignment->getPosition());
            }

            $essay = $this->essays[$writer->getId()] ?? null;
            if (!empty($essay)) {
                if (!empty($essay->getCorrectionFinalized())
                    || !empty($this->localDI->getCorrectorAdminService($essay->getTaskId())->getAuthorizedSummaries($essay))
                ) {
                    $modals[] = $confirm_remove_auth_modal = $this->uiFactory->modal()->interruptive(
                        $this->correction_settings->getRequiredCorrectors() == 1
                            ? $this->plugin->txt('remove_authorization')
                            : $this->plugin->txt('remove_authorizations'),
                        $this->plugin->txt("remove_authorizations_confirmation"),
                        $this->getRemoveAuthorisationsAction($writer)
                    )->withAffectedItems([ $this->uiFactory->modal()->interruptiveItem(
                        $writer->getId(),
                        $this->getWriterName($writer) . ' [' . $writer->getPseudonym() . ']'
                    )])->withActionButtonLabel("ok");

                    $actions[] = $this->uiFactory->button()->shy(
                        $this->correction_settings->getRequiredCorrectors() == 1
                            ? $this->plugin->txt('remove_authorization')
                            : $this->plugin->txt('remove_authorizations'),
                        ""
                    )
                        ->withOnClick($confirm_remove_auth_modal->getShowSignal());
                }

                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('export_steps'), $this->getExportStepsTarget($writer));
                $properties[$this->plugin->txt("word_count")]  = $this->word_count($writer);
                $properties[$this->plugin->txt("final_grade")] = $this->localDI->getDataService($writer->getTaskId())->formatFinalResult($essay);
            }

            if(!empty($this->getLocations())) {
                $properties[$this->plugin->txt("location")] = $this->location($writer);
            }

            $actions_dropdown = $this->uiFactory->dropdown()->standard($actions)
                ->withLabel($this->plugin->txt("actions"));

            $item = $this->localDI->getUIFactory()->item()->formItem($this->getWriterName($writer, true). $this->getWriterAnchor($writer))
                                  ->withName($writer->getId())
                                  ->withProperties($properties)
                                  ->withActions($actions_dropdown);

            if(($icon = $this->getWriterIcon($writer)) !== null) {
                $item->withLeadIcon($icon);
            }

            $items[] = $item;
        }

        // Multi actions

        $resources = $this->localDI->getUIFactory()->item()->formGroup($this->plugin->txt("correctable_exams")
            . $this->localDI->getDataService(0)->formatCounterSuffix($count_filtered, $count_total), $items, "");

        $write_mail_signal = $resources->generateDSCallbackSignal();
        $this->ctrl->clearParameters($this->parent);
        $modals[] = $resources->addDSModalTriggerToModal(
            $this->uiFactory->modal()->roundtrip("", []),
            $this->ctrl->getFormAction($this->parent, "mailToSelectedAsync", "", true),
            "writer_ids",
            $write_mail_signal
        );
        $form_actions[] = $resources->addDSModalTriggerToButton(
            $this->uiFactory->button()->shy($this->plugin->txt("mail_to_writers_or_correctors"), "#"),
            $write_mail_signal
        );

        $assign_callback_signal = $resources->generateDSCallbackSignal();
        $this->ctrl->clearParameters($this->parent);
        $modals[] = $resources->addDSModalTriggerToModal(
            $this->uiFactory->modal()->roundtrip("", []),
            $this->ctrl->getFormAction($this->parent, "editAssignmentsAsync", "", true),
            "writer_ids",
            $assign_callback_signal
        );
        $form_actions[] = $resources->addDSModalTriggerToButton(
            $this->uiFactory->button()->shy($this->plugin->txt("change_corrector"), "#"),
            $assign_callback_signal
        );

        $remove_auth_callback_signal = $resources->generateDSCallbackSignal();
        $modals[] = $resources->addDSModalTriggerToModal(
            $this->uiFactory->modal()->interruptive("", "", ""),
            $this->ctrl->getFormAction($this->parent, "confirmRemoveAuthorizationsAsync", "", true),
            "writer_ids",
            $remove_auth_callback_signal
        );
        $form_actions[] = $resources->addDSModalTriggerToButton(
            $this->uiFactory->button()->shy($this->plugin->txt("remove_authorizations"), "#"),
            $remove_auth_callback_signal
        );

        return $this->renderer->render(array_merge([$filter_gui], [$resources->withActions($this->uiFactory->dropdown()->standard($form_actions))], $modals));
    }

    private function getAssignedCorrectorName(Writer $writer, int $pos): string
    {
        if(($assignment = $this->getAssignmentByWriterPosition($writer, $pos)) !== null) {
            $corrector = $this->correctors[$assignment->getCorrectorId()];

            if (!empty($essay = ($this->essays[$writer->getId()] ?? null)) && isset($this->summaries[$essay->getId()][$corrector->getId()])) {
                $summary = $this->summaries[$essay->getId()][$corrector->getId()];
            } else {
                $summary = null;
            }

            return $this->getUsername($corrector->getUserId())
                . ' - ' . $this->localDI->getDataService($corrector->getTaskId())->formatCorrectionResult($summary);
        }

        return " - ";
    }

    private function getViewCorrectionAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getLinkTargetByClass(["ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI"], "viewCorrections");
    }

    private function canDownloadWrittenPdf(Writer $writer): bool
    {
        if (!isset($this->essays[$writer->getId()])) {
            return false;
        }
        $essay = $this->essays[$writer->getId()];

        return !empty($essay->getEditStarted());
    }

    private function getDownloadWrittenPdfAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getLinkTargetByClass(["ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI"], "downloadWrittenPdf");
    }

    private function canDownloadCorrectedPdf(Writer $writer): bool
    {
        if (!isset($this->essays[$writer->getId()])) {
            return false;
        }
        $essay = $this->essays[$writer->getId()];

        if(!isset($this->summaries[$essay->getId()])) {
            return false;
        }

        return !empty($this->summaries[$essay->getId()]);
    }

    private function getDownloadCorrectedPdfAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getLinkTargetByClass(["ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI"], "downloadCorrectedPdf");
    }

    private function getMailForEssayAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_ids", $writer->getId());
        return $this->ctrl->getLinkTarget($this->parent, "mailToSelectedAsync");
    }

    private function getChangeCorrectorAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getLinkTarget($this->parent, "editAssignmentsAsync");
    }

    private function hasCorrectionStatusStitch($writer): bool
    {
        if (!isset($this->essays[$writer->getId()])) {
            return false;
        }
        $essay = $this->essays[$writer->getId()];
        return in_array($essay->getId(), $this->getCorrectionStatusStitches());
    }

    private function hasCorrectionStatusStitchDecided($writer): bool
    {
        return isset($this->essays[$writer->getId()])
            && !empty($this->essays[$writer->getId()]->getStitchComment());
    }

    private function getCorrectionStatusStitchAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getLinkTarget($this->parent, "stitchDecision");
    }

    private function getRemoveAuthorisationsAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getLinkTarget($this->parent, "removeAuthorizations");
    }

    private function essayStatus(Writer $writer)
    {
        if (isset($this->essays[$writer->getId()])) {
            $essay = $this->essays[$writer->getId()];

            if($essay->getWritingExcluded() !== null) {
                return '<strong>' . $this->plugin->txt("writing_excluded_from") . " " .
                    $this->getUsername($essay->getWritingExcludedBy(), true) . '</strong>';
            }

            if ($essay->getCorrectionFinalized() !== null) {
                return $this->plugin->txt("writing_finalized_from") . " " .
                    $this->getUsername($essay->getCorrectionFinalizedBy(), true);
            }

            if(in_array($essay->getId(), $this->getCorrectionStatusStitches())) {
                return $this->plugin->txt("correction_status_stitch_needed");
            }

            if ($essay->getWritingAuthorized() !== null) {
                $name = $this->plugin->txt("participant");
                if ($essay->getWritingAuthorizedBy() != $writer->getUserId()) {
                    $name = $this->getUsername($essay->getWritingAuthorizedBy(), true);
                }

                return $this->plugin->txt("writing_authorized_from") . " " .$name;
            }

            if ($essay->getEditStarted() !== null) {
                return $this->plugin->txt("writing_edit_started");
            }
        }

        return $this->plugin->txt("writing_not_started");
    }

    /**
     * @return \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector[]
     */
    public function getCorrectors(): array
    {
        return $this->correctors;
    }

    /**
     * @param \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector[] $correctors
     */
    public function setCorrectors(array $correctors): void
    {
        $this->correctors = $correctors;

        foreach ($correctors as $corrector) {
            $this->user_ids[] = $corrector->getUserId();
        }
    }

    /**
     * @return CorrectorAssignment[]
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    /**
     * @param \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector $corrector
     * @return array|\ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment[]
     */
    private function getAssignmentsByWriter(Writer $writer): array
    {
        if (array_key_exists($writer->getId(), $this->assignments)) {
            return $this->assignments[$writer->getId()];
        }
        return [];
    }

    /**
     * @param \ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer $writer
     * @param int $position
     * @return \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment|null
     */
    private function getAssignmentByWriterPosition(Writer $writer, int $position): ?CorrectorAssignment
    {
        if(array_key_exists($writer->getId(), $this->assignments)) {
            foreach($this->assignments[$writer->getId()] as $assignment) {
                if($assignment->getPosition() === $position) {
                    return $assignment;
                }
            }
        }
        return null;
    }

    /**
     * @param CorrectorAssignment[] $assignments
     */
    public function setAssignments(array $assignments): void
    {
        foreach ($assignments as $assignment) {
            if (! isset($this->assignments[$assignment->getWriterId()])) {
                $this->assignments[$assignment->getWriterId()] = [];
            }
            $this->assignments[$assignment->getWriterId()][$assignment->getPosition()] = $assignment;
        }
        foreach($this->assignments as $key => $val) {
            usort($this->assignments[$key], function ($a, $b) {return $a->getPosition() - $b->getPosition();});
        }

    }

    /**
     * @param CorrectorSummary[] $summaries
     * @return void
     */
    public function setSummaries(array $summaries): void
    {
        foreach($summaries as $summary) {
            if(!isset($this->summaries[$summary->getEssayId()])) {
                $this->summaries[$summary->getEssayId()]= [];
            }
            $this->summaries[$summary->getEssayId()][$summary->getCorrectorId()] = $summary;
        }
    }


    /**
     * @return int[]
     */
    public function getCorrectionStatusStitches(): array
    {
        return $this->correction_status_stitches;
    }

    /**
     * @param int[] $correction_status_stitches
     */
    public function setCorrectionStatusStitches(array $correction_status_stitches): void
    {
        $this->correction_status_stitches = $correction_status_stitches;
    }

    protected function filterInputs(): array
    {
        $correctors = [];

        foreach($this->getCorrectors() as $corrector) {
            $name = strip_tags($this->getUsername($corrector->getUserId(), true));
            $correctors[$corrector->getId()] = $name;

        }

        return [
            "pdf_version" => $this->uiFactory->input()->field()->select(
                $this->plugin->txt("filter_pdf_version"),
                [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
            ),
            "corrector" => $this->uiFactory->input()->field()->multiselect($this->plugin->txt("correctors"), $correctors),
                "corrected" => $this->uiFactory->input()->field()->select(
                    $this->plugin->txt("filter_corrected"),
                    [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
                ),
                "stitch" => $this->uiFactory->input()->field()->select(
                    $this->plugin->txt("filter_stitch"),
                    [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
                ),
                "assigned" => $this->uiFactory->input()->field()->select(
                    $this->plugin->txt("filter_assigned"),
                    [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
                )
        ];
    }

    protected function filterInputActivation(): array
    {
        return [true, true, true, true, true];
    }
    protected function filterItems(array $filter, Writer $writer): bool
    {
 
        if(!empty($filter["corrector"])) {
            $has = [];
            foreach($this->getAssignmentsByWriter($writer) as $ass) {
                $has[] = (string) $ass->getCorrectorId();
            }
            if(empty(array_intersect($has, $filter["corrector"]))) {
                return false;
            }
        }
        $essay = $this->essays[$writer->getId()];

        if(!empty($filter["pdf_version"]) && $filter["pdf_version"] == self::FILTER_YES) {
            if($essay === null || $essay->getPdfVersion() === null) {
                return false;
            }
        }

        if(!empty($filter["corrected"]) && $filter["corrected"] == self::FILTER_YES) {
            if($essay === null || $essay->getCorrectionFinalized() === null) {
                return false;
            }
        }
        if(!empty($filter["corrected"]) && $filter["corrected"] == self::FILTER_NO) {
            if($essay !== null && $essay->getCorrectionFinalized() !== null) {
                return false;
            }
        }

        if(!empty($filter["stitch"]) && $filter["stitch"] == self::FILTER_YES) {
            if($essay === null || !in_array($essay->getId(), $this->correction_status_stitches)) {
                return false;
            }
        }
        if(!empty($filter["stitch"]) && $filter["stitch"] == self::FILTER_NO) {
            if($essay !== null && in_array($essay->getId(), $this->correction_status_stitches)) {
                return false;
            }
        }

        if(!empty($filter["assigned"]) && $filter["assigned"] == self::FILTER_YES) {
            if(count($this->getAssignmentsByWriter($writer)) !== $this->correction_settings->getRequiredCorrectors()) {
                return false;
            }
        }
        if(!empty($filter["assigned"]) && $filter["assigned"] == self::FILTER_NO) {
            if(count($this->getAssignmentsByWriter($writer)) === $this->correction_settings->getRequiredCorrectors()) {
                return false;
            }
        }

        return true;
    }


}
