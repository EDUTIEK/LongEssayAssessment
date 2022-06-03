<?php

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\TimeExtension;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\Data\WriterHistory;

class WriterAdminListGUI
{
	/**
	 * @var Writer[]
	 */
	private $writers = [];

	/**
	 * @var TimeExtension[]
	 */
	private $extensions = [];

	/**
	 * @var WriterHistory[]
	 */
	private $history = [];

	private $user_ids = [];

	/**
	 * @var array
	 */
	private $user_data = [];

	/**
	 * @var Essay[]
	 */
	private $essays = [];

	private \ILIAS\UI\Factory $uiFactory;
	private \ilCtrl $ctrl;
	private \ilLongEssayTaskPlugin $plugin;
	private \ILIAS\UI\Renderer $renderer;
	private object $parent;

	public function __construct(object $parent, \ilLongEssayTaskPlugin $plugin)
	{
		global $DIC;
		$this->parent = $parent;
		$this->uiFactory = $DIC->ui()->factory();
		$this->ctrl = $DIC->ctrl();
		$this->plugin = $plugin;
		$this->renderer = $DIC->ui()->renderer();
	}

	public function getContent()
	{
		$actions = array(
			"Alle" => "all",
			"Teilgenommen" => "",
			"Nicht Teilgenommen" => "",
			"Mit Zeitverlängerung" => "",
		);

		$aria_label = "change_the_currently_displayed_mode";
		$view_control = $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive("Alle");

		$items = [];
		foreach($this->getWriters() as $writer)
		{
			$actions = [];
			if($this->canGetSight($writer)) {
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_processing'), $this->getSightAction($writer));
			}
			if($this->canGetAuthorized($writer)) {
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('authorize_writing'), $this->getAuthorizeAction($writer));
			}
			if($this->canGetExtension($writer)) {
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('extent_time'), $this->getExtensionAction($writer));
			}
			$actions[] = $this->uiFactory->button()->shy($this->plugin->txt("exclude_participant"), $this->getExclusionAction($writer));

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

		$resources = $this->uiFactory->item()->group($this->plugin->txt("participants"), $items);

		return $this->renderer->render($view_control) . '<br><br>' .
			$this->renderer->render($resources);
	}
	private function canGetSight(Writer $writer){
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];
			return $essay->getEditEnded() === null && $essay->getEditStarted() !== null;
		}
		return false;
	}

	private function getSightAction(Writer $writer) {
		return "#";
	}

	private function canGetAuthorized(Writer $writer){
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];

			return $essay->getEditStarted() !== null && $essay->getWritingAuthorized() === null;
		}
		return false;
	}

	private function getAuthorizeAction(Writer $writer) {
		return "#";
	}

	private function canGetExtension(Writer $writer) {
		if(isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];

			return $essay->getWritingAuthorized() === null && $essay->getCorrectionFinalized() === null;
		}
		return true;
	}

	private function getExtensionAction(Writer $writer){
		return "#";
	}

	private function getExclusionAction(Writer $writer){
		$this->ctrl->setParameter($this->parent,"writer_id", $writer->getId());
		return $this->ctrl->getFormAction($this->parent, "deleteWriter");
	}

	private function getUsername($user_id){
		if(isset($this->user_data[$user_id])){
			return $this->user_data[$user_id];
		}
		return ' - ';
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

	private function essayStatus(Writer $writer){
		if(isset($this->essays[$writer->getId()])){
			$essay = $this->essays[$writer->getId()];

			if($essay->getCorrectionFinalized()!== null)
			{
				return $this->plugin->txt("writing_finalized_from") . $this->getUsername($essay->getWritingAuthorizedBy());
			}

			if($essay->getWritingAuthorized() !== null){
				$name = $this->plugin->txt("user");
				if($essay->getWritingAuthorizedBy() != $writer->getUserId()){
					$name = $this->getUsername($essay->getWritingAuthorizedBy());
				}

				return $this->plugin->txt("writing_authorized_from") . $name;
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
					new \ilDateTime($history->getTimestamp(),IL_CAL_UNIX));;
			}
		}

		return $this->plugin->txt("writing_no_last_save");
	}

	/**
	 * @return Writer[]
	 */
	public function getWriters(): array
	{
		return $this->writers;
	}

	/**
	 * @param Writer[] $writers
	 */
	public function setWriters(array $writers): void
	{
		$this->writers = $writers;

		foreach($writers as $writer){
			$this->user_ids[] = $writer->getUserId();
		}
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

	public function loadUserData()
	{
		$back = $this->ctrl->getLinkTarget($this->parent);
		$this->user_data = \ilUserUtil::getNamePresentation(array_unique($this->user_ids), true, true, $back, true);
	}

	private function dummy_writers(){
		$item1 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Theo Teststudent (theo.teststudent)",''))
			->withLeadIcon($this->uiFactory->symbol()->icon()->standard('usr', 'user', 'medium'))
			->withProperties(array(
				"Abgabe-Status" => "noch nicht abgegeben",
				"Zeitverlängerung" => "10 min",
				"Letzte Speicherung" => "Heute, 13:50",

			))
			->withActions(
				$this->uiFactory->dropdown()->standard([
					$this->uiFactory->button()->shy('Bearbeitung einsehen', '#'),
					$this->uiFactory->button()->shy('Abgabe autorisieren', '#'),
					$this->uiFactory->button()->shy('Zeit verlängern', '#'),
					$this->uiFactory->button()->shy('Von Bearbeitung ausschließen', '#'),

				]));

		$item2 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Thekla Teststudentin (thekla.teststudentin)", ''))
			->withLeadIcon($this->uiFactory->symbol()->icon()->standard('usr', 'editor', 'medium'))
			->withProperties(array(
				"Abgabe-Status" => "abgegeben",
				"Zeitverlängerung" => "keine",
				"Letzte Speicherung" => "Heute, 12:45",

			))
			->withActions(
				$this->uiFactory->dropdown()->standard([
					$this->uiFactory->button()->shy('Bearbeitung einsehen', '#'),
				]));
		return [$item1, $item2];
	}
}