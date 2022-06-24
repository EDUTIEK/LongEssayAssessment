<?php

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\TimeExtension;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\Data\WriterHistory;

class WriterAdminListGUI extends WriterListGUI
{
	/**
	 * @var TimeExtension[]
	 */
	private $extensions = [];

	/**
	 * @var WriterHistory[]
	 */
	private $history = [];

	/**
	 * @var Essay[]
	 */
	private $essays = [];

	public function getContent() :string
	{
		$this->loadUserData();

		$actions = array(
			"Alle" => "all",
			"Teilgenommen" => "",
			"Nicht Teilgenommen" => "",
			"Mit Zeitverlängerung" => "",
		);

		$aria_label = "change_the_currently_displayed_mode";
		$view_control = $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive("Alle");

		$items = [];
		$modals = [];

		foreach($this->getWriters() as $writer)
		{
			$actions = [];
			if($this->canGetSight($writer)) {
				$sight_modal = $this->uiFactory->modal()->lightbox($this->uiFactory->modal()->lightboxTextPage(
					(string)$this->essays[$writer->getId()]->getProcessedText(),
					$this->plugin->txt("submission"),
				));
				$modals[] = $sight_modal;
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_processing'), '')->withOnClick($sight_modal->getShowSignal());
			}

			if($this->canGetAuthorized($writer)) {
				$authorize_modal = $this->uiFactory->modal()->interruptive(
					$this->plugin->txt("authorize_writing"),
					$this->plugin->txt("authorize_writing_confirmation"),
					$this->getAuthorizeAction($writer)
				)->withAffectedItems([
					$this->uiFactory->modal()->interruptiveItem($writer->getUserId(), $this->getUsername($writer->getUserId()))
				])->withActionButtonLabel("confirm");

				$modals[] = $authorize_modal;
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('authorize_writing'), "",)->withOnClick($authorize_modal->getShowSignal());
			}

			if($this->canGetExtension($writer)) {
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('extent_time'), $this->getExtensionAction($writer));
			}

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

			$items[] = $this->uiFactory->item()->standard($this->getUsername($writer->getUserId()))
				->withLeadIcon($this->uiFactory->symbol()->icon()->standard('usr', 'user', 'medium'))
				->withProperties(array(
					$this->plugin->txt("essay_status") => $this->essayStatus($writer),
					$this->plugin->txt("writing_time_extension") => $this->extensionString($writer),
					$this->plugin->txt("writing_last_save") => $this->lastSave($writer),

				))
				->withActions(
					$this->uiFactory->dropdown()->standard($actions));
		}

		$resources = array_merge([$this->uiFactory->item()->group($this->plugin->txt("participants"), $items)], $modals);

		return $this->renderer->render($view_control) . '<br><br>' .
			$this->renderer->render($resources);
	}

	private function canGetSight(Writer $writer){
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];
			return /*$essay->getEditEnded() === null &&*/ $essay->getEditStarted() !== null;
		}
		return false;
	}

	private function canGetAuthorized(Writer $writer){
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];

			return $essay->getEditStarted() !== null
				/*&& $essay->getEditEnded() !== null*/
				&& $essay->getWritingAuthorized() === null;
		}
		return false;
	}

	private function getAuthorizeAction(Writer $writer) {
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "authorizeWriting");
	}

	private function canGetExtension(Writer $writer) {
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];

			return $essay->getWritingAuthorized() === null && $essay->getCorrectionFinalized() === null;
		}
		return true;
	}

	private function getExtensionAction(Writer $writer){
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "editExtension");
	}

	private function getExclusionAction(Writer $writer){
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "deleteWriter");
	}

	/**
	 * @param Writer $writer
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

			if($essay->getCorrectionFinalized() !== null)
			{
				return $this->plugin->txt("writing_finalized_from") . " " .
					$this->getUsername($essay->getWritingAuthorizedBy(), true);
			}

			if($essay->getWritingAuthorized() !== null){
				$name = $this->plugin->txt("participant");
				if($essay->getWritingAuthorizedBy() != $writer->getUserId()){
					$name = $this->getUsername($essay->getWritingAuthorizedBy(), true);
				}

				return $this->plugin->txt("writing_authorized_from") . " " . $name;
			}

			if($essay->getEditEnded() !== null){
				return $this->plugin->txt("writing_edit_ended");
			}

			if($essay->getEditStarted() !== null){
				return $this->plugin->txt("writing_edit_started");
			}
		}

		return $this->plugin->txt("writing_not_started");
	}

	private function lastSave(Writer $writer){

		if(isset($this->essays[$writer->getId()])){
			$essay = $this->essays[$writer->getId()];

			if(isset($this->history[$essay->getId()])){
				$history = $this->history[$essay->getId()];
				return \ilDatePresentation::formatDate(
					new \ilDateTime($history->getTimestamp(), IL_CAL_DATETIME));
			}
		}

		return $this->plugin->txt("writing_no_last_save");
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

	/**
	 * @return WriterHistory[]
	 */
	public function getHistory(): array
	{
		return $this->history;
	}

	/**
	 * @param WriterHistory[] $history
	 */
	public function setHistory(array $history): void
	{
		foreach($history as $history_item) {
			$this->history[$history_item->getEssayId()] = $history_item;
		}
	}

	/**
	 * @return Essay[]
	 */
	public function getEssays(): array
	{
		return $this->essays;
	}

	/**
	 * @param Essay[] $essays
	 */
	public function setEssays(array $essays): void
	{
		foreach ($essays as $essay){
			$this->essays[$essay->getWriterId()] = $essay;
			$this->user_ids[] = $essay->getCorrectionFinalizedBy();
			$this->user_ids[] = $essay->getWritingAuthorizedBy();
		}
	}

//	private function dummy_writers(){
//		$item1 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Theo Teststudent (theo.teststudent)",''))
//			->withLeadIcon($this->uiFactory->symbol()->icon()->standard('usr', 'user', 'medium'))
//			->withProperties(array(
//				"Abgabe-Status" => "noch nicht abgegeben",
//				"Zeitverlängerung" => "10 min",
//				"Letzte Speicherung" => "Heute, 13:50",
//
//			))
//			->withActions(
//				$this->uiFactory->dropdown()->standard([
//					$this->uiFactory->button()->shy('Bearbeitung einsehen', '#'),
//					$this->uiFactory->button()->shy('Abgabe autorisieren', '#'),
//					$this->uiFactory->button()->shy('Zeit verlängern', '#'),
//					$this->uiFactory->button()->shy('Von Bearbeitung ausschließen', '#'),
//
//				]));
//
//		$item2 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Thekla Teststudentin (thekla.teststudentin)", ''))
//			->withLeadIcon($this->uiFactory->symbol()->icon()->standard('usr', 'editor', 'medium'))
//			->withProperties(array(
//				"Abgabe-Status" => "abgegeben",
//				"Zeitverlängerung" => "keine",
//				"Letzte Speicherung" => "Heute, 12:45",
//
//			))
//			->withActions(
//				$this->uiFactory->dropdown()->standard([
//					$this->uiFactory->button()->shy('Bearbeitung einsehen', '#'),
//				]));
//		return [$item1, $item2];
//	}
}