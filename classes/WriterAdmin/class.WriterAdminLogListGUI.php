<?php

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use ILIAS\Plugin\LongEssayTask\Data\LogEntry;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\Data\WriterNotice;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;

class WriterAdminLogListGUI
{
	/**
	 * @var mixed[]
	 */
	protected $entries = [];

	protected $user_ids = [];

	/**
	 * @var Writer[]
	 */
	protected $writer = [];


	protected \ILIAS\UI\Factory $uiFactory;
	protected \ilCtrl $ctrl;
	protected \ilLongEssayTaskPlugin $plugin;
	protected \ILIAS\UI\Renderer $renderer;
	protected object $parent;
	protected string $parent_cmd;
	protected int $task_id;

	public function __construct(object $parent, string $parent_cmd, \ilLongEssayTaskPlugin $plugin, $task_id)
	{
		global $DIC;
		$this->parent = $parent;
		$this->parent_cmd = $parent_cmd;
		$this->uiFactory = $DIC->ui()->factory();
		$this->ctrl = $DIC->ctrl();
		$this->plugin = $plugin;
		$this->renderer = $DIC->ui()->renderer();
		$this->task_id = $task_id;
	}


	private function buildNotice(WriterNotice $notice)
	{
		$recipient = "";

		if($notice->getWriterId() !== null){
			$id = -1;
			if(array_key_exists($notice->getWriterId(), $this->writer)){
				$id = $this->writer[$notice->getWriterId()]->getUserId();
			}
			$recipient = $this->getUsername($id);
		}else{
			$recipient = $this->plugin->txt("notice_recipient_all");
		}

		return $this->uiFactory->item()->standard(nl2br($notice->getNoticeText()))
			->withLeadIcon($this->uiFactory->symbol()->icon()->standard('coms', 'coms', 'medium')->withIsOutlined(true))
			->withProperties(array(
				$this->plugin->txt("log_type") => $this->plugin->txt("log_type_notice"),
				$this->plugin->txt("notice_send") => $this->getFormattedTime($notice->getCreated()),
				$this->plugin->txt("notice_recipient") => $recipient

			));
	}

	private function buildLogEntry(LogEntry $log_entry) {

		switch($log_entry->getCategory()){
			case LogEntry::CATEGORY_EXCLUSION:
			case LogEntry::CATEGORY_AUTHORIZE:
			case LogEntry::CATEGORY_EXTENSION:
				$icon = $this->uiFactory->symbol()->icon()->standard('pecd', 'notes', 'medium')->withIsOutlined(true);
				break;
			default:
				$icon = $this->uiFactory->symbol()->icon()->standard('nots', 'notes', 'medium')->withIsOutlined(true);
				break;
		}

		return $this->uiFactory->item()->standard(nl2br($this->replaceUserIDs($log_entry->getEntry())))
			->withLeadIcon($icon)
			->withProperties(array(
				$this->plugin->txt("log_type") => $this->plugin->txt("log_type_" . $log_entry->getCategory()),
				$this->plugin->txt("log_entry_entered") => $this->getFormattedTime($log_entry->getTimestamp()),
			));
	}

	public function getContent() :string
	{
		$this->loadUserData();
		try {
			$this->sortEntries();
		} catch (\ilDateTimeException $e) {
		}

		$items = [];

		foreach($this->entries as $key => $entry){
			if($entry instanceof WriterNotice){
				$items[] = $this->buildNotice($entry);
			}elseif ($entry instanceof LogEntry){
				$items[] = $this->buildLogEntry($entry);
			}
		}

		$resources = $this->uiFactory->item()->group($this->plugin->txt("log_entries"), $items);

		return $this->renderer->render([$resources]);
	}

	/**
	 * @param LogEntry[] $log_entries
	 * @return void
	 */
	public function addLogEntries(array $log_entries) {
		foreach ($log_entries as $log_entry){
			$this->entries[$log_entry->getTimestamp()] = $log_entry;
			$this->user_ids= array_merge($this->user_ids, $this->parseUserIDs($log_entry->getEntry()));
		}
	}

	/**
	 * @param WriterNotice[] $writer_notices
	 * @return void
	 */
	public function addWriterNotices(array $writer_notices) {
		$writer_ids = [];

		foreach ($writer_notices as $writer_notice) {
			$this->entries[$writer_notice->getCreated()] = $writer_notice;
			if($writer_notice->getWriterId() !== null)
				$writer_ids[] = $writer_notice->getWriterId();
		}

		$writer_repo = LongEssayTaskDI::getInstance()->getWriterRepo();
		$user_ids = [];

		foreach ($writer_repo->getWritersByTaskId($this->task_id, $writer_ids) as $writer){
			$this->writer[$writer->getId()] = $writer;
			$user_ids[] = $writer->getUserId();
		}

		$this->user_ids = array_merge($this->user_ids, $user_ids);
	}

	/**
	 * @return void
	 */
	private function sortEntries()
	{
		krsort($this->entries);
	}

	/**
	 * @param ?string $text
	 * @return int[]
	 */
	private function parseUserIDs(?string $text): array
	{
		if($text === "" || $text === null)
		{
			return [];
		}

		$output_array = [];

		preg_match_all('/\[user=(\d+)\]/', $text, $output_array);

		return array_map('intval',$output_array[1]);
	}

	/**
	 * @param ?string $text
	 * @return array|string|string[]|null
	 */
	private function replaceUserIDs(?string $text){
		if($text === "" || $text === null)
		{
			return "";
		}

		return preg_replace_callback(
			'/\[user=(\d+)\]/',
			function ($matches) {
				return $this->getUsername($matches[1], true);
			},
			$text
		);
	}

	/**
	 * Get Username
	 *
	 * @param $user_id
	 * @return mixed|string
	 */
	protected function getUsername($user_id, $strip_img = false){
		if(isset($this->user_data[$user_id])){
			if($strip_img){
				return strip_tags($this->user_data[$user_id], ["a"]);
			}else{
				return $this->user_data[$user_id];
			}
		}
		return ' - ';
	}

	/**
	 * Load needed Usernames From DB
	 * @return void
	 */
	protected function loadUserData()
	{
		$back = $this->ctrl->getLinkTarget($this->parent);
		$this->user_data = \ilUserUtil::getNamePresentation(array_unique($this->user_ids), true, true, $back, true);
	}

	/**
	 * @param string $timestamp
	 * @return string
	 */
	protected function getFormattedTime($timestamp): string
	{
		try {
			return \ilDatePresentation::formatDate(
				new \ilDateTime($timestamp, IL_CAL_DATETIME));
		} catch (\ilDateTimeException $e) {
			return " - ";
		}
	}
}