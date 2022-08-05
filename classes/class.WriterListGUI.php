<?php

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use Exception;
use ILIAS\Plugin\LongEssayTask\Data\Corrector;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\TimeExtension;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\Data\WriterHistory;

abstract class WriterListGUI
{
	/**
	 * @var Writer[]
	 */
	protected $writers = [];

	protected $user_ids = [];
	/**
	 * @var array
	 */
	protected $user_data = [];

	/**
	 * @var bool
	 */
	protected $user_data_loaded = false;

	protected \ILIAS\UI\Factory $uiFactory;
	protected \ilCtrl $ctrl;
	protected \ilLongEssayTaskPlugin $plugin;
	protected \ILIAS\UI\Renderer $renderer;
	protected object $parent;
	protected string $parent_cmd;

	public function __construct(object $parent, string $parent_cmd, \ilLongEssayTaskPlugin $plugin)
	{
		global $DIC;
		$this->parent = $parent;
		$this->parent_cmd = $parent_cmd;
		$this->uiFactory = $DIC->ui()->factory();
		$this->ctrl = $DIC->ctrl();
		$this->plugin = $plugin;
		$this->renderer = $DIC->ui()->renderer();
	}

	abstract public function getContent():string;

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
	 * Get Username
	 *
	 * @param $user_id
	 * @return mixed|string
	 */
	protected function getUsername($user_id, $strip_img = false){
		if(!$this->user_data_loaded){
			throw new Exception("getUsername was called without loading usernames.");
		}

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
	 * Get Writer name
	 *
	 * @param Writer $writer
	 * @return string
	 */
	protected function getWriterName(Writer $writer, $strip_img = false): string
	{
		$pseudonym = "";

		if($writer->getPseudonym() != "")
		{
			$pseudonym = " (" . $writer->getPseudonym() . ")";
		}

		return $this->getUsername($writer->getUserId(), $strip_img) . $pseudonym;
	}

	/**
	 * Load needed Usernames From DB
	 * @return void
	 */
	protected function loadUserData()
	{
		$back = $this->ctrl->getLinkTarget($this->parent);
		$this->user_data = \ilUserUtil::getNamePresentation(array_unique($this->user_ids), true, true, $back, true);
		$this->user_data_loaded = true;
	}

	/**
	 * @param Writer $writer
	 * @return string
	 */
	protected function getWriterAnchor(Writer $writer): string
	{
		$user_id = $writer->getUserId();
		$writer_id = $writer->getId();
		return "<blankanchor id='writer_$writer_id'><blankanchor id='user_$user_id'>";
	}

	/**
	 * @param callable|null $custom_sort Custom sortation callable. Equal writer will be sorted by name.
	 * @return void
	 */
	protected function sortWriter(callable $custom_sort = null){
		$this->sortWriterOrCorrector($this->writers, $custom_sort);
	}


	/**
	 * @param callable|null $custom_sort Custom sortation callable. Equal writer will be sorted by name.
	 * @return void
	 */
	protected function sortWriterOrCorrector(array &$target_array, callable $custom_sort = null){
		if(!$this->user_data_loaded){
			throw new Exception("sortWriterOrCorrector was called without loading usernames.");
		}

		$names = [];
		foreach ($this->user_data as $usr_id => $name){
			$names[$usr_id] = strip_tags($name);
		}

		$by_name = function($a, $b) use($names) {
			$name_a = array_key_exists($a->getUserId(), $names) ? $names[$a->getUserId()] : "ÿ";
			$name_b = array_key_exists($b->getUserId(), $names) ? $names[$b->getUserId()] : "ÿ";

			return strcasecmp($name_a, $name_b);
		};

		if($custom_sort !== null){
			$by_custom = function ($a, $b) use ($custom_sort, $by_name){
				$rating = $custom_sort($a, $b);
				return $rating !== 0 ? $rating :  $by_name($a, $b);
			};

			usort($target_array, $by_custom);
		}else{
			usort($target_array, $by_name);
		}
	}
}