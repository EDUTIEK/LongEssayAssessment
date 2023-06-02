<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\Data\Task\Location;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\TimeExtension;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Implementation\Component\Modal\Modal;

class WriterAdminListGUI extends WriterListGUI
{
	/**
	 * @var TimeExtension[]
	 */
	private array $extensions = [];

	/**
	 * @var Location[]
	 */
	private array $locations = [];

	private ?RoundTrip $multi_command_modal = null;


	public function getContent() :string
	{
		$this->loadUserData();
		$this->sortWriter();

		$items = [];
		$modals = [];

        $count_total = count($this->getWriters());
        $count_filtered = 0;
        foreach($this->getWriters() as $writer)
		{
			if(!$this->isFiltered($writer)){
				continue;
			}
            $count_filtered++;

			$actions = [];
			if($this->canGetSight($writer)) {
				$sight_modal = $this->uiFactory->modal()->lightbox($this->uiFactory->modal()->lightboxTextPage(
                    $this->localDI->getDataService($writer->getTaskId())->cleanupRichText($this->essays[$writer->getId()]->getWrittenText()),
					$this->plugin->txt("submission"),
				));
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
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('authorize_writing'), "",)->withOnClick($authorize_modal->getShowSignal());
			}

			if($this->canGetExtension($writer)) {
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('extent_time'), $this->getExtensionAction($writer));
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

			if($this->canChangeLocation()){
				$modals[] = $location_modal = $this->uiFactory->modal()->roundtrip("", [])->withAsyncRenderUrl(
					$this->getChangeLocationAction($writer)
				);
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt("change_location"), '')
					->withOnClick($location_modal->getShowSignal());
			}

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
			if($this->canChangeLocation()){
				$properties[$this->plugin->txt("location")] = $this->location($writer);
			}

			$items[] = $this->localDI->getUIFactory()->item()->formItem($this->getWriterName($writer, true) . $this->getWriterAnchor($writer))
				->withName($writer->getId())
				->withLeadIcon($this->getWriterIcon($writer))
				->withProperties($properties)
                ->withActions($actions_dropdown);
		}
		$modals[] = $multi_command_modal = $this->getMultiCommandModal();

		$resources = $this->localDI->getUIFactory()->item()->formGroup(
			$this->plugin->txt("participants")
			. $this->localDI->getDataService(0)->formatCounterSuffix($count_filtered, $count_total)
			, $items, "");

		$this->ctrl->setParameter($this->parent, "writer_id", "");
		$form_actions = [];

		if($this->canChangeLocation()){
			$form_actions[] = $this->openModalButton($this->plugin->txt("assign_location"),
					$this->ctrl->getFormAction($this->parent, "editLocationMulti", "", true),
					$multi_command_modal, $resources->getListDataSourceSignal());
		}


		$resources = $resources->withActions($this->uiFactory->dropdown()->standard($form_actions));


		$resources = array_merge([$resources], $modals);

		return $this->renderer->render($this->filterControl()) . '<br><br>' .
			$this->renderer->render($resources);
	}

	private function canGetSight(Writer $writer){
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];
			return /*$essay->getEditEnded() === null &&*/ $essay->getEditStarted() !== null;
		}
		return false;
	}

	private function openModalButton(string $txt, string $link, RoundTrip $modal, Signal $data_source){
		$replace_signal_id = $modal->getReplaceSignal()->getId();
		$data_source_id = $data_source->getId();
		return $this->uiFactory->button()->shy($txt, "#")->withOnClick($modal->getShowSignal())->withOnLoadCode(
			function ($id) use ($link, $replace_signal_id, $data_source_id) {
				return "
					$( '#{$id}' ).on( 'load_list_data_source_callback', function( event, signalData ) {
						writer_ids = Object.keys(signalData['data_list']);
						n_url = '{$link}' + '&writer_ids=' + writer_ids.join('/');
						
						$(this).trigger('{$replace_signal_id}',
							{
								'id' : '{$replace_signal_id}', 'event' : 'click',
								'triggerer' : $(this),
								'options' : JSON.parse('{\"url\":\"' + n_url + '\"}')
							}
						);
   					 });
					
					
					$('#{$id}').click(function() { 
					
						$(document).trigger('{$data_source_id}',
							{
								'id' : '{$data_source_id}', 'event' : 'load_list_data_source',
								'triggerer' : $('#{$id}'),
								'options' : JSON.parse('[]')
							}
						);
						return false;
					}
				);";
			}
		);
	}

	private function canChangeLocation(): bool
	{
		return $this->locations !== null;
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

	private function location(Writer $writer){
		if(isset($this->essays[$writer->getId()]) &&
			($location = $this->essays[$writer->getId()]->getLocation()) !== null &&
			isset($this->locations[$location])){
			return $this->locations[$location]->getTitle();
		}
		return " - ";
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

	public function isFiltered(Writer $writer):bool
	{
		$filter = $this->getFilter();
		$essay = null;
		$extension = null;

		if(array_key_exists($writer->getId(), $this->essays)){
			$essay = $this->essays[$writer->getId()];
		}

		if(array_key_exists($writer->getId(), $this->extensions)){
			$extension = $this->extensions[$writer->getId()];
		}

		switch($filter){
			case "attended":
				return $essay !== null && $essay->getEditStarted() !== null;
			case "not_attended":
				return $essay === null || $essay->getEditStarted() === null;
			case "with_extension":
				return $extension !== null;
			case "all":
			default:
				return true;
		}
	}

	public function getFilter():string
	{
		global $DIC;
		$query = $DIC->http()->request()->getQueryParams();
		if(array_key_exists("filter", $query) && in_array($query["filter"], ["attended", "not_attended", "with_extension", "all"])){
			return $query["filter"];
		}
		return "all";
	}

	public function filterControl()
	{
		$link = $this->ctrl->getLinkTarget($this->parent, $this->parent_cmd);
		$filter = [
			"all",
			"attended",
			"not_attended",
			"with_extension"
		];

		$actions  = [];
		foreach ($filter as $key){
			$actions[$this->plugin->txt("filter_writer_admin_list_" . $key)] = $link . "&filter=" . $key;
		}

		$aria_label = "change_the_currently_displayed_mode";
		return $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive($this->plugin->txt("filter_writer_admin_list_" . $this->getFilter()));
	}

	public function setMultiCommandModal(Roundtrip $modal){
		$this->multi_command_modal = $modal->withOnLoadCode(function ($id){
			return "$(document).ready(function(){ $( '#{$id}' ).modal('show'); return false;});";
		});
	}

	public function getMultiCommandModal():Modal{
		if($this->multi_command_modal === null)
			return $this->uiFactory->modal()->roundtrip("",[]);
		return $this->multi_command_modal;
	}

	/**
	 * @param Location[] $locations
	 * @return void
	 */
	public function setLocations(array $locations){
		foreach($locations as $location){
			$this->locations[$location->getId()] = $location;
		}
	}
}
