<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\Data\Task\Location;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\UI\Implementation\Component\Modal\Modal;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\WorkingTime;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup;

class WriterAdminListGUI extends WriterListGUI
{
    private ?RoundTrip $multi_command_modal = null;


    public function getContent() :string
    {
        $this->loadUserData();
        $this->sortWriter();

        $items = [];
        $modals = [];

        $count_total = count($this->getWriters());
        $count_filtered = 0;

        $filter_gui = $this->filterForm("xlas_writer_list_filter");
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? [];

        foreach($this->getWriters() as $writer) {
            if(!$this->filter($filter_data, $writer)) {
                continue;
            }
            $count_filtered++;

            $actions = [];
            if($this->canGetSight($writer)) {
                $sight_modal = $this->uiFactory->modal()->roundtrip("", [])
                    ->withAsyncRenderUrl($this->getSightAction($writer));
                $modals[] = $sight_modal;
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_processing'), '')->withOnClick($sight_modal->getShowSignal());
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('export_steps'), $this->getExportStepsTarget($writer));
            }

            $modals[] = $log_modal = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl(
                $this->getAddLogEntryAction($writer)
            );
            $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('add_log_entry_for writer'),'')
                ->withOnClick($log_modal->getShowSignal());

            $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('mail_to_writer'),
                $this->getWriteMailAction($writer));

            if($this->canGetAuthorized($writer)) {
                $authorize_modal = $this->uiFactory->modal()->interruptive(
                    $this->plugin->txt("authorize_writing"),
                    $this->plugin->txt("authorize_writing_confirmation"),
                    $this->getAuthorizeAction($writer)
                )->withAffectedItems([
                    $this->uiFactory->modal()->interruptiveItem()->standard($writer->getUserId(),
                        $this->renderer->render($this->getUserIcon($writer->getUserId())) . $this->getUsernameText($writer->getUserId()))
                ])->withActionButtonLabel($this->plugin->txt('authorize_writing'));

                $modals[] = $authorize_modal;
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('authorize_writing'), "", )
                    ->withOnClick($authorize_modal->getShowSignal());
            }

            if($this->canGetUnauthorized($writer)) {
                $authorize_modal = $this->uiFactory->modal()->interruptive(
                    $this->plugin->txt("unauthorize_writing"),
                    $this->plugin->txt("unauthorize_writing_confirmation"),
                    $this->getUnauthorizeAction($writer)
                )->withAffectedItems([
                    $this->uiFactory->modal()->interruptiveItem()->standard($writer->getUserId(),
                        $this->renderer->render($this->getUserIcon($writer->getUserId())) . $this->getUsernameText($writer->getUserId()))
                ])->withActionButtonLabel($this->plugin->txt('unauthorize_writing'));

                $modals[] = $authorize_modal;
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('unauthorize_writing'), "", )
                                             ->withOnClick($authorize_modal->getShowSignal());
            }

            if($this->canChangeWorkingTime($writer)) {
                $modals[] = $working_modal = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl(
                    $this->getWorkingTimeAction($writer)
                );
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt("change_working_time"), '')
                    ->withOnClick($working_modal->getShowSignal());
            }

            if($this->canChangeLocation()) {
                $modals[] = $location_modal = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl(
                    $this->getChangeLocationAction($writer)
                );
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt("change_location"), '')
                    ->withOnClick($location_modal->getShowSignal());
            }

            $actions[] = $this->uiFactory->button()->shy($this->getPDFVersionLinkText($writer), $this->getPDFVersionLink($writer));

            if($this->canDownloadPDFVersion($writer)) {
                $link = $this->getPDFDownloadLink($writer);
                $actions[] = $this->uiFactory->button()->shy(
                    $this->plugin->txt("pdf_version_download"),
                    $link
                );
            }

            if($this->canGetRepealed($writer)) {
                $repeal_modal = $this->uiFactory->modal()->interruptive(
                    $this->plugin->txt("repeal_exclude_participant"),
                    $this->plugin->txt("repeal_exclude_participant_confirmation"),
                    $this->getRepealExclusionAction($writer)
                )->withAffectedItems([
                    $this->uiFactory->modal()->interruptiveItem()->standard($writer->getUserId(),
                        $this->renderer->render($this->getUserIcon($writer->getUserId())) . $this->getUsernameText($writer->getUserId()))
                ])->withActionButtonLabel($this->plugin->txt("repeal_exclude_participant"));


                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt("repeal_exclude_participant"), '')
                    ->withOnClick($repeal_modal->getShowSignal());

                $modals[] = $repeal_modal;
            } else {
                $exclusion_modal = $this->uiFactory->modal()->interruptive(
                    $this->plugin->txt("exclude_participant"),
                    $this->plugin->txt("exclude_participant_confirmation"),
                    $this->getExclusionAction($writer)
                )->withAffectedItems([
                    $this->uiFactory->modal()->interruptiveItem()->standard($writer->getUserId(),
                        $this->renderer->render($this->getUserIcon($writer->getUserId())) . $this->getUsernameText($writer->getUserId()))
                ])->withActionButtonLabel($this->plugin->txt("exclude_participant"));

                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt("exclude_participant"), '')
                    ->withOnClick($exclusion_modal->getShowSignal());

                $modals[] = $exclusion_modal;
            }

            $remove_modal = $this->uiFactory->modal()->interruptive(
                $this->plugin->txt("remove_writer"),
                $this->plugin->txt("remove_writer_confirmation"),
                $this->getRemoveAction($writer)
            )->withAffectedItems([
                $this->uiFactory->modal()->interruptiveItem()->standard($writer->getUserId(),
                    $this->renderer->render($this->getUserIcon($writer->getUserId())) . $this->getUsernameText($writer->getUserId()))
            ])->withActionButtonLabel($this->plugin->txt("remove_writer"));

            $actions[] = $this->uiFactory->button()->shy($this->plugin->txt("remove_writer"), '')
                ->withOnClick($remove_modal->getShowSignal());
            $modals[] = $remove_modal;

            $actions_dropdown = $this->uiFactory->dropdown()->standard($actions)
                ->withLabel($this->plugin->txt("actions"));


            $working_time = new WorkingTime($this->task, $writer);

            $properties = [
                $this->plugin->txt("pseudonym") => $writer->getPseudonym(),
                $this->plugin->txt("essay_status") => $this->essayStatus($writer),
                $this->plugin->txt("working_time_change") => $working_time->isIndividual() ?
                    $this->common_services->formatter()->formatWorkingTime($working_time) :
                    $this->plugin->txt("no_working_time_change")
            ];
            if (!empty($this->lastSave($writer))) {
                $properties[ $this->plugin->txt("writing_last_save")] = $this->lastSave($writer);
            }
            $properties[$this->plugin->txt("word_count")]  = $this->word_count($writer);

            if($this->canChangeLocation()) {
                $properties[$this->plugin->txt("location")] = $this->location($writer);
            }

            if($this->canDownloadPDFVersion($writer)) {
                $link = $this->getPDFDownloadLink($writer);
                $properties[$this->plugin->txt("pdf_version")] = $this->uiFactory->button()->shy(
                    $this->plugin->txt("download"),
                    $link
                );
            }

            $items[] = $this->localDI->getUIFactory()->item()->formItem($this->getWriterNameLink($writer))
                ->withName($writer->getId())
                ->withLeadIcon($this->getWriterIcon($writer))
                ->withProperties($properties)
                ->withActions($actions_dropdown);
        }

        $resources = $this->localDI->getUIFactory()->item()->formGroup(
            $this->plugin->txt("participants")
            . $this->localDI->getDataService(0)->formatCounterSuffix($count_filtered, $count_total),
            $items,
            ""
        );

        $this->ctrl->setParameter($this->parent, "writer_id", "");
        $form_actions = [];

        $mail_callback_signal = $resources->generateDSCallbackSignal();
        $form_actions[] = $resources->addDSTriggerToButton(
            $this->uiFactory->button()->shy($this->plugin->txt("mail_to_writers"), "#"),
            $this->ctrl->getFormAction($this->parent, 'mailToWriters'),
            'writer_ids',
            $mail_callback_signal
        );

        if($this->canChangeLocation()) {
            $this->addMultiAction($resources, $modals, $form_actions, 'editLocationMulti',
                $this->plugin->txt('assign_location'));
        }

        $this->addMultiAction($resources, $modals, $form_actions, 'editWorkingTimeMulti',
            $this->plugin->txt('change_working_time'));

        $this->addMultiAction($resources, $modals, $form_actions, 'authorizeWritingMultiConfirmation',
            $this->plugin->txt('authorize_writings'));

        $this->addMultiAction($resources, $modals, $form_actions, 'unauthorizeWritingMultiConfirmation',
            $this->plugin->txt('unauthorize_writings'));

        $this->addMultiAction($resources, $modals, $form_actions, 'removeWriterMultiConfirmation',
            $this->plugin->txt('remove_writer'));

        $this->addMultiAction($resources, $modals, $form_actions, 'changeTextToPdfMultiConfirmation',
            $this->plugin->txt('change_text_to_pdf'));

        $resources = $resources->withActions($this->uiFactory->dropdown()->standard($form_actions));

        return $this->renderer->render([$filter_gui, $resources, $modals]);
    }

    private function addMultiAction(FormGroup $group, array &$modals, array &$actions, string $command, string $label)
    {
        $signal = $group->generateDSCallbackSignal();

        $modals[] = $group->addDSModalTriggerToModal(
            $this->getMultiCommandModal(),
            $this->ctrl->getFormAction($this->parent, $command, "", true),
            "writer_ids",
            $signal
        );

        $actions[] = $group->addDSModalTriggerToButton(
            $this->uiFactory->button()->shy($label, "#"),
            $signal
        );
    }

    private function getSightAction(Writer $writer)
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "showEssay", "", true);
    }

    private function getWriteMailAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "mailToWriters");
    }

    private function getAddLogEntryAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "addLogEntry", "", true);
    }

    private function canChangeLocation(): bool
    {
        return $this->getLocations() !== null;
    }

    private function getChangeLocationAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "editLocation", "", true);
    }


    private function canGetAuthorized(Writer $writer): bool
    {
        if(isset($this->essays[$writer->getId()])) {
            $essay = $this->essays[$writer->getId()];

            return $writer->getWorkingStart() !== null
                /*&& $essay->getEditEnded() !== null*/
                && $essay->getWritingAuthorized() === null;
        }
        return false;
    }

    private function getAuthorizeAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "authorizeWriting");
    }


    private function canGetUnauthorized(Writer $writer): bool
    {
        if(isset($this->essays[$writer->getId()])) {
            $essay = $this->essays[$writer->getId()];

            return $writer->getWorkingStart() !== null
                /*&& $essay->getEditEnded() !== null*/
                && $essay->getWritingAuthorized() !== null;
        }
        return false;
    }

    private function getUnauthorizeAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "unauthorizeWriting");
    }
    

    private function canChangeWorkingTime(Writer $writer): bool
    {
        if(isset($this->essays[$writer->getId()])) {
            $essay = $this->essays[$writer->getId()];

            return $essay->getWritingAuthorized() === null && $essay->getCorrectionFinalized() === null;
        }
        return true;
    }

    private function getWorkingTimeAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "editWorkingTime");
    }

    private function canGetRepealed(Writer $writer): bool
    {
        if(isset($this->essays[$writer->getId()])) {
            $essay = $this->essays[$writer->getId()];

            return $essay->getWritingExcluded() !== null;
        }
        return false;
    }

    private function getRepealExclusionAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "repealExclusion");
    }

    private function getExclusionAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "excludeWriter");
    }

    private function getRemoveAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "removeWriter");
    }

    private function canDownloadPDFVersion(Writer $writer): bool
    {
        return isset($this->essays[$writer->getId()]) && $this->essays[$writer->getId()]->getPdfVersion() !== null;
    }

    private function getPDFDownloadLink(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "downloadPDFVersion");
    }

    private function getPDFVersionLink(Writer $writer):string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "uploadPDFVersion");
    }

    private function getPDFVersionLinkText(Writer $writer): string
    {
        $essay = $this->essays[$writer->getId()] ?? null;

        return $essay !== null && $essay->getPdfVersion() !== null ? $this->plugin->txt("pdf_version_edit") : $this->plugin->txt("pdf_version_upload");
    }

    private function essayStatus(Writer $writer): string
    {
        if(isset($this->essays[$writer->getId()])) {
            $essay = $this->essays[$writer->getId()];

            if($essay->getWritingExcluded() !== null) {
                return $this->plugin->txt("writing_excluded_from") . " " .
                    $this->getUsernameText($essay->getWritingExcludedBy());
            }

            if($essay->getWritingAuthorized() !== null) {
                $name = $this->plugin->txt("participant");
                if($essay->getWritingAuthorizedBy() != $writer->getUserId()) {
                    $name = $this->getUsernameText($essay->getWritingAuthorizedBy());
                }

                return $this->plugin->txt("writing_authorized_from") . " " . $name . ", "
                    . $this->localDI->getDataService($this->task->getTaskId())->formatDateTime($essay->getWritingAuthorized());
            }
        }

        if ($writer->getWorkingStart() !== null) {
            return $this->plugin->txt("working_started");
        }

        return $this->plugin->txt("working_not_started");
    }

    private function lastSave(Writer $writer): string
    {

        if(isset($this->essays[$writer->getId()])) {
            $essay = $this->essays[$writer->getId()];
            if (!empty($essay->getEditEnded())) {
                return \ilDatePresentation::formatDate(
                    new \ilDateTime($essay->getEditEnded(), IL_CAL_DATETIME)
                );
            }
        }
        return '';
    }

    public function getMultiCommandModal():Modal
    {
        if($this->multi_command_modal === null) {
            return $this->uiFactory->modal()->roundtrip("", []);
        }
        return $this->multi_command_modal;
    }

    protected function filterInputs(): array
    {
        return ["attended" => $this->uiFactory->input()->field()->select(
            $this->plugin->txt("filter_attended"),
            [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
        ),
                "assigned" => $this->uiFactory->input()->field()->select(
                    $this->plugin->txt("filter_working_time_change"),
                    [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
                ),
                "pdf_version" => $this->uiFactory->input()->field()->select(
                    $this->plugin->txt("filter_pdf_version"),
                    [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
                ),
                "exclusion" => $this->uiFactory->input()->field()->select(
                    $this->plugin->txt("filter_exclusion"),
                    [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
                )];
    }

    protected function filterInputActivation(): array
    {
        return [true, true, true, true];
    }
    protected function filterItems(array $filter, Writer $writer): bool
    {
        $essay = $this->essays[$writer->getId()] ?? null;

        if(!empty($filter["attended"]) && $filter["attended"] == self::FILTER_YES) {
            if($writer->getWorkingStart() === null) {
                return false;
            }
        }
        if(!empty($filter["attended"]) && $filter["attended"] == self::FILTER_NO) {
            if($writer->getWorkingStart() !== null) {
                return false;
            }
        }

        $working_time = new WorkingTime($this->task, $writer);

        if(!empty($filter["assigned"]) && $filter["assigned"] == self::FILTER_YES) {
            return $working_time->isIndividual();
        }
        if(!empty($filter["assigned"]) && $filter["assigned"] == self::FILTER_NO) {
            return !$working_time->isIndividual();
        }

        if(!empty($filter["pdf_version"]) && $filter["pdf_version"] == self::FILTER_YES) {
            if($essay === null || $essay->getPdfVersion() === null) {
                return false;
            }
        }
        if(!empty($filter["pdf_version"]) && $filter["pdf_version"] == self::FILTER_NO) {
            if($essay !== null && $essay->getPdfVersion() !== null) {
                return false;
            }
        }

        if(!empty($filter["exclusion"]) && $filter["exclusion"] == self::FILTER_YES) {
            if($essay === null || $essay->getWritingExcluded() === null) {
                return false;
            }
        }
        if(!empty($filter["exclusion"]) && $filter["exclusion"] == self::FILTER_NO) {
            if($essay !== null && $essay->getWritingExcluded() !== null) {
                return false;
            }
        }


        return true;
    }
}
