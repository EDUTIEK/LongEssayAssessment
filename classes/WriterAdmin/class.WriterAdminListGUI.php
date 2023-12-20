<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\Data\Task\Location;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\TimeExtension;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\UI\Implementation\Component\Modal\Modal;

class WriterAdminListGUI extends WriterListGUI
{
	/**
	 * @var TimeExtension[]
	 */
	private array $extensions = [];

	private ?RoundTrip $multi_command_modal = null;


	public function getContent() :string
	{
		$this->loadUserData();
		$this->sortWriter();

		$items = [];
		$modals = [];

        $count_total = count($this->getWriters());
        $count_filtered = 0;

        $filter_gui = $this->filterForm();
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? [];

        foreach($this->getWriters() as $writer)
		{
            if(!$this->filter($filter_data, $writer)){
                continue;
            }
            $count_filtered++;

			$actions = [];
			if($this->canGetSight($writer)) {
				$sight_modal = $this->uiFactory->modal()->roundtrip("",[])
					->withAsyncRenderUrl($this->getSightAction($writer));
				$modals[] = $sight_modal;
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_processing'), '')->withOnClick($sight_modal->getShowSignal());
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('export_steps'), $this->getExportStepsTarget($writer));
            }

			if($this->canGetAuthorized($writer)) {
				$authorize_modal = $this->uiFactory->modal()->interruptive(
					$this->plugin->txt("authorize_writing"),
					$this->plugin->txt("authorize_writing_confirmation"),
					$this->getAuthorizeAction($writer)
				)->withAffectedItems([
					$this->uiFactory->modal()->interruptiveItem($writer->getUserId(), $this->getWriterName($writer))
				])->withActionButtonLabel("confirm");

				$modals[] = $authorize_modal;
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('authorize_writing'), "",)
					->withOnClick($authorize_modal->getShowSignal());
			}

            if($this->canGetUnauthorized($writer)) {
                $authorize_modal = $this->uiFactory->modal()->interruptive(
                    $this->plugin->txt("unauthorize_writing"),
                    $this->plugin->txt("unauthorize_writing_confirmation"),
                    $this->getUnauthorizeAction($writer)
                )->withAffectedItems([
                    $this->uiFactory->modal()->interruptiveItem($writer->getUserId(), $this->getWriterName($writer))
                ])->withActionButtonLabel("confirm");

                $modals[] = $authorize_modal;
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('unauthorize_writing'), "",)
                                             ->withOnClick($authorize_modal->getShowSignal());
            }

            if($this->canGetExtension($writer)) {
				$modals[] = $extension = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl(
					$this->getExtensionAction($writer)
				);
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt("extent_time"), '')
					->withOnClick($extension->getShowSignal());
			}

			if($this->canChangeLocation()){
				$modals[] = $location_modal = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl(
					$this->getChangeLocationAction($writer)
				);
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt("change_location"), '')
					->withOnClick($location_modal->getShowSignal());
			}

			$actions[] = $this->uiFactory->button()->shy($this->getPDFVersionLinkText($writer), $this->getPDFVersionLink($writer));

			if($this->canDownloadPDFVersion($writer)){
				$link = $this->getPDFDownloadLink($writer);
				$actions[] = $this->uiFactory->button()->shy(
					$this->plugin->txt("pdf_version_download"), $link);
			}

			if($this->canGetRepealed($writer)){
				$repeal_modal = $this->uiFactory->modal()->interruptive(
					$this->plugin->txt("repeal_exclude_participant"),
					$this->plugin->txt("repeal_exclude_participant_confirmation"),
					$this->getRepealExclusionAction($writer)
				)->withAffectedItems([
					$this->uiFactory->modal()->interruptiveItem($writer->getUserId(), $this->getUsername($writer->getUserId()))
				]);

				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt("repeal_exclude_participant"), '')
					->withOnClick($repeal_modal->getShowSignal());

				$modals[] = $repeal_modal;
			}else{
				$exclusion_modal = $this->uiFactory->modal()->interruptive(
					$this->plugin->txt("exclude_participant"),
					$this->plugin->txt("exclude_participant_confirmation"),
					$this->getExclusionAction($writer)
				)->withAffectedItems([
					$this->uiFactory->modal()->interruptiveItem($writer->getUserId(), $this->getUsername($writer->getUserId()))
				])->withActionButtonLabel("remove");

				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt("exclude_participant"), '')
					->withOnClick($exclusion_modal->getShowSignal());

				$modals[] = $exclusion_modal;
			}

			$remove_modal = $this->uiFactory->modal()->interruptive(
				$this->plugin->txt("remove_writer"),
				$this->plugin->txt("remove_writer_confirmation"),
				$this->getRemoveAction($writer)
			)->withAffectedItems([
				$this->uiFactory->modal()->interruptiveItem($writer->getUserId(), $this->getUsername($writer->getUserId()))
			])->withActionButtonLabel("remove");

			$actions[] = $this->uiFactory->button()->shy($this->plugin->txt("remove_writer"), '')
				->withOnClick($remove_modal->getShowSignal());
			$modals[] = $remove_modal;

			$actions_dropdown = $this->uiFactory->dropdown()->standard($actions)
				->withLabel($this->plugin->txt("actions"));

            $properties = [
                $this->plugin->txt("pseudonym") => $writer->getPseudonym(),
                $this->plugin->txt("essay_status") => $this->essayStatus($writer),
                $this->plugin->txt("writing_time_extension") => $this->extensionString($writer),
            ];
            if (!empty($this->lastSave($writer))) {
                $properties[ $this->plugin->txt("writing_last_save")] = $this->lastSave($writer);
            }
            $properties[$this->plugin->txt("word_count")]  = $this->word_count($writer);

            if($this->canChangeLocation()){
				$properties[$this->plugin->txt("location")] = $this->location($writer);
			}

			if($this->canDownloadPDFVersion($writer)){
				$link = $this->getPDFDownloadLink($writer);
				$properties[$this->plugin->txt("pdf_version")] = $this->uiFactory->button()->shy(
					$this->plugin->txt("download"), $link);
			}

			$items[] = $this->localDI->getUIFactory()->item()->formItem($this->getWriterName($writer, true) . $this->getWriterAnchor($writer))
				->withName($writer->getId())
				->withLeadIcon($this->getWriterIcon($writer))
				->withProperties($properties)
                ->withActions($actions_dropdown);
		}

		$resources = $this->localDI->getUIFactory()->item()->formGroup(
			$this->plugin->txt("participants")
			. $this->localDI->getDataService(0)->formatCounterSuffix($count_filtered, $count_total)
			, $items, "");

		$this->ctrl->setParameter($this->parent, "writer_id", "");
		$form_actions = [];

		if($this->canChangeLocation()){
			$location_callback_signal = $resources->generateDSCallbackSignal();

			$modals[] = $resources->addDSModalTriggerToModal(
				$this->getMultiCommandModal(),
				$this->ctrl->getFormAction($this->parent, "editLocationMulti", "", true),
				"writer_ids",
				$location_callback_signal
			);

			$form_actions[] = $resources->addDSModalTriggerToButton(
				$this->uiFactory->button()->shy($this->plugin->txt("assign_location"), "#"),
				$location_callback_signal
			);
		}
		$extension_callback_signal = $resources->generateDSCallbackSignal();

		$modals[] = $resources->addDSModalTriggerToModal(
			$this->uiFactory->modal()->roundtrip("", []),
			$this->ctrl->getFormAction($this->parent, "editExtensionMulti", "", true),
			"writer_ids",
			$extension_callback_signal
		);

		$form_actions[] = $resources->addDSModalTriggerToButton(
			$this->uiFactory->button()->shy($this->plugin->txt("extent_time"), "#"),
			$extension_callback_signal
		);

		$remove_callback_signal = $resources->generateDSCallbackSignal();

		$modals[] = $resources->addDSModalTriggerToModal(
			$this->uiFactory->modal()->interruptive("", "", ""),
			$this->ctrl->getFormAction($this->parent, "removeWriterMultiConfirmation", "", true),
			"writer_ids",
			$remove_callback_signal
		);

		$form_actions[] = $resources->addDSModalTriggerToButton(
			$this->uiFactory->button()->shy($this->plugin->txt("remove_writer"), "#"),
			$remove_callback_signal
		);

        $remove_callback_signal = $resources->generateDSCallbackSignal();

        $modals[] = $resources->addDSModalTriggerToModal(
            $this->uiFactory->modal()->interruptive("", "", ""),
            $this->ctrl->getFormAction($this->parent, "changeTextToPdfMultiConfirmation", "", true),
            "writer_ids",
            $remove_callback_signal
        );

        $form_actions[] = $resources->addDSModalTriggerToButton(
            $this->uiFactory->button()->shy($this->plugin->txt("change_text_to_pdf"), "#"),
            $remove_callback_signal
        );

        $resources = $resources->withActions($this->uiFactory->dropdown()->standard($form_actions));

		$resources = array_merge([$resources], $modals);

		return $this->renderer->render([$filter_gui, $resources]);
	}

	private function canGetSight(Writer $writer){
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];
			return /*$essay->getEditEnded() === null &&*/ $essay->getEditStarted() !== null;
		}
		return false;
	}

	private function getSightAction(Writer $writer){
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "		showEssay", "", true);
	}

	private function canChangeLocation(): bool
	{
		return $this->getLocations() !== null;
	}

	private function getChangeLocationAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "editLocation", "", true);
	}


	private function canGetAuthorized(Writer $writer): bool
	{
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];

			return $essay->getEditStarted() !== null
				/*&& $essay->getEditEnded() !== null*/
				&& $essay->getWritingAuthorized() === null;
		}
		return false;
	}

	private function getAuthorizeAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "authorizeWriting");
	}


    private function canGetUnauthorized(Writer $writer): bool
    {
        if(isset($this->essays[$writer->getId()])) {
            $essay = $this->essays[$writer->getId()];

            return $essay->getEditStarted() !== null
                /*&& $essay->getEditEnded() !== null*/
                && $essay->getWritingAuthorized() !== null;
        }
        return false;
    }

    private function getUnauthorizeAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
        return $this->ctrl->getFormAction($this->parent, "unauthorizeWriting");
    }


    private function canGetExtension(Writer $writer): bool
	{
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];

			return $essay->getWritingAuthorized() === null && $essay->getCorrectionFinalized() === null;
		}
		return true;
	}

	private function getExtensionAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "editExtension");
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
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "repealExclusion");
	}

	private function getExclusionAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "excludeWriter");
	}

	private function getRemoveAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "removeWriter");
	}

	private function canDownloadPDFVersion(Writer $writer): bool
	{
		return isset($this->essays[$writer->getId()]) && $this->essays[$writer->getId()]->getPdfVersion() !== null;
	}

	private function getPDFDownloadLink(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "downloadPDFVersion");
	}

	private function getPDFVersionLink(Writer $writer):string
	{
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "uploadPDFVersion");
	}

	private function getPDFVersionLinkText(Writer $writer): string
	{
		$essay = $this->essays[$writer->getId()] ?? null;

		return $essay !== null && $essay->getPdfVersion() !== null ? $this->plugin->txt("pdf_version_edit") : $this->plugin->txt("pdf_version_upload");
	}

	/**
	 * @param \ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer $writer
	 * @return void
	 */
	private function extensionString(Writer $writer): string
	{
		if(isset($this->extensions[$writer->getId()])){
			return $this->extensions[$writer->getId()]->getMinutes() . " " . $this->plugin->txt("min");
		}
		return $this->plugin->txt("writing_none_extension");
	}

	private function essayStatus(Writer $writer): string
	{
		if(isset($this->essays[$writer->getId()])){
			$essay = $this->essays[$writer->getId()];

			if($essay->getWritingExcluded() !== null)
			{
				return $this->plugin->txt("writing_excluded_from") . " " .
					$this->getUsername($essay->getWritingExcludedBy(), true);
			}

			if($essay->getWritingAuthorized() !== null){
				$name = $this->plugin->txt("participant");
				if($essay->getWritingAuthorizedBy() != $writer->getUserId()){
					$name = $this->getUsername($essay->getWritingAuthorizedBy(), true);
				}

				return $this->plugin->txt("writing_authorized_from") . " " . $name;
			}

			if($essay->getEditStarted() !== null){
				return $this->plugin->txt("writing_edit_started");
			}
		}

		return $this->plugin->txt("writing_not_started");
	}

	private function lastSave(Writer $writer): string
	{

		if(isset($this->essays[$writer->getId()])){
			$essay = $this->essays[$writer->getId()];
            if (!empty($essay->getEditEnded())) {
                return \ilDatePresentation::formatDate(
                    new \ilDateTime($essay->getEditEnded(), IL_CAL_DATETIME));
            }
		}
        return '';
	}

	/**
	 * @return TimeExtension[]
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

	/**
	 * @param TimeExtension[] $extensions
	 */
	public function setExtensions(array $extensions): void
	{
		foreach($extensions as $extension) {
			$this->extensions[$extension->getWriterId()] = $extension;
		}
	}

	public function getMultiCommandModal():Modal{
		if($this->multi_command_modal === null)
			return $this->uiFactory->modal()->roundtrip("",[]);
		return $this->multi_command_modal;
	}

    protected function filterInputs(): array
    {
        return ["attended" => $this->uiFactory->input()->field()->select($this->plugin->txt("filter_attended"),
            [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]),
                "assigned" => $this->uiFactory->input()->field()->select($this->plugin->txt("filter_with_extension"),
                    [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]),
                "pdf_version" => $this->uiFactory->input()->field()->select($this->plugin->txt("filter_pdf_version"),
                    [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]),
                "exclusion" => $this->uiFactory->input()->field()->select($this->plugin->txt("filter_exclusion"),
                    [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")])];
    }

    protected function filterInputActivation(): array
    {
        return [true, true, true, true];
    }
    protected function filterItems(array $filter, Writer $writer): bool
    {
        $essay = $this->essays[$writer->getId()];

        if(!empty($filter["attended"]) && $filter["attended"] == self::FILTER_YES){
            if($essay === null || $essay->getEditStarted() === null){
                return false;
            }
        }
        if(!empty($filter["attended"]) && $filter["attended"] == self::FILTER_NO){
            if($essay !== null && $essay->getEditStarted() !== null){
                return false;
            }
        }

        $extension = null;

        if(array_key_exists($writer->getId(), $this->extensions)){
            $extension = $this->extensions[$writer->getId()];
        }

        if(!empty($filter["assigned"]) && $filter["assigned"] == self::FILTER_YES){
            if($extension !== null){
                return false;
            }
        }
        if(!empty($filter["assigned"]) && $filter["assigned"] == self::FILTER_NO){
            if($extension === null){
                return false;
            }
        }

        if(!empty($filter["pdf_version"]) && $filter["pdf_version"] == self::FILTER_YES){
            if($essay === null || $essay->getPdfVersion() === null){
                return false;
            }
        }
        if(!empty($filter["pdf_version"]) && $filter["pdf_version"] == self::FILTER_NO){
            if($essay !== null && $essay->getPdfVersion() !== null){
                return false;
            }
        }

        if(!empty($filter["exclusion"]) && $filter["exclusion"] == self::FILTER_YES){
            if($essay === null || $essay->getWritingExcluded() === null){
                return false;
            }
        }
        if(!empty($filter["exclusion"]) && $filter["exclusion"] == self::FILTER_NO){
            if($essay !== null && $essay->getWritingExcluded() !== null){
                return false;
            }
        }


        return true;
    }
}
