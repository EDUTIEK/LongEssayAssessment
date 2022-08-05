<?php

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use Exception;
use ILIAS\Plugin\LongEssayTask\Data\Corrector;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorAssignment;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\TimeExtension;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\Data\WriterHistory;

class CorrectorListGUI extends WriterListGUI
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

	/**
	 * @var Corrector[]
	 */
	private $correctors = [];

	/**
	 * @var CorrectorAssignment[][]
	 */
	private $assignments = [];

	public function getContent():string
	{
		$this->loadUserData();
		$this->sortCorrector();
		$this->sortAssignments();

		$modals = [];
		$items = [];

		foreach ($this->correctors as $corrector) {
			$actions = [];

			if($this->canGetRemoved($corrector)){
				$remove_modal = $this->uiFactory->modal()->interruptive(
					$this->plugin->txt("remove_corrector"),
					$this->plugin->txt("remove_corrector_confirmation"),
					$this->getRemoveCorrectorAction($corrector)
				)->withAffectedItems([
					$this->uiFactory->modal()->interruptiveItem($corrector->getUserId(), $this->getUsername($corrector->getUserId()))
				])->withActionButtonLabel("remove");

				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt("remove_corrector"), '')
					->withOnClick($remove_modal->getShowSignal());

				$modals[] = $remove_modal;
			}

			$writers = [];
			foreach ($this->getAssignmentsByCorrector($corrector) as $assignment){
				switch($assignment->getPosition()){
					case 0: $pos = $this->plugin->txt("first_corrector");break;
					case 1: $pos = $this->plugin->txt("second_corrector");break;
					default: $pos = $this->plugin->txt("assignment_pos_other");break;
				}
				$writers["&nbsp;&nbsp;" . $this->getWriterName($this->writers[$assignment->getWriterId()], true)] = $pos;
			}

			$item = $this->uiFactory->item()->standard($this->getUsername($corrector->getUserId(), true))
				->withLeadIcon($this->getUserIcon($corrector->getUserId()));

			if(count($writers) > 0) {
				$item = $item->withDescription($this->renderer->render($this->uiFactory->listing()->characteristicValue()->text($writers)));
			}
			if(count($actions) > 0){
				$item =  $item->withActions($this->uiFactory->dropdown()->standard($actions));
			}

			$items[] = $item;
		}
		$resources = array_merge([$this->uiFactory->item()->group($this->plugin->txt("correctors"), $items)], $modals);
		return $this->renderer->render($resources);
	}

	private function canGetRemoved(Corrector $corrector):bool
	{
		return ! array_key_exists($corrector->getId(), $this->assignments);
	}

	private function getRemoveCorrectorAction(Corrector $corrector): string
	{
		$this->ctrl->setParameter($this->parent, "corrector_id", $corrector->getId());
		return $this->ctrl->getLinkTarget($this->parent, "removeCorrector");
	}

	/**
	 * @return Corrector[]
	 */
	public function getCorrectors(): array
	{
		return $this->correctors;
	}

	/**
	 * @param Corrector[] $correctors
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
	 * @param CorrectorAssignment[] $assignments
	 */
	public function setAssignments(array $assignments): void
	{
		foreach ($assignments as $assignment) {
			if (! isset($this->assignments[$assignment->getCorrectorId()])){
				$this->assignments[$assignment->getCorrectorId()] = [];
			}
			$this->assignments[$assignment->getCorrectorId()][] = $assignment;
		}
	}

	/**
	 * @param Corrector $corrector
	 * @return array|CorrectorAssignment[]
	 */
	private function getAssignmentsByCorrector(Corrector $corrector): array
	{
		if (array_key_exists($corrector->getId(), $this->assignments)){
			return $this->assignments[$corrector->getId()];
		}
		return [];
	}

	/**
	 * @param callable|null $custom_sort Custom sortation callable. Equal writer will be sorted by name.
	 * @return void
	 */
	protected function sortCorrector(callable $custom_sort = null){
		$this->sortWriterOrCorrector($this->correctors, $custom_sort);
	}

	/**
	 * Sort Assignments primarily by position (lowest position comes first), secondary by name
	 * @return void
	 * @throws Exception
	 */
	protected function sortAssignments(){
		if(!$this->user_data_loaded){
			throw new Exception("sortAssignments was called without loading usernames.");
		}

		$names = [];
		foreach ($this->user_data as $usr_id => $name){
			$names[$usr_id] = strip_tags($name);
		}

		$by_name = function(CorrectorAssignment $a, CorrectorAssignment$b) use($names) {
			$rating = $a->getPosition() - $b->getPosition();

			if($rating !== 0){
				return $rating;
			}

			if(!array_key_exists($a->getWriterId(), $this->writers)) {
				return -1;
			}
			if (!array_key_exists($b->getWriterId(), $this->writers)){
				return 1;
			}

			$writer_a = $this->writers[$a->getWriterId()];
			$writer_b = $this->writers[$b->getWriterId()];

			$name_a = array_key_exists($writer_a->getUserId(), $names) ? $names[$writer_a->getUserId()] : "ÿ";
			$name_b = array_key_exists($writer_b->getUserId(), $names) ? $names[$writer_b->getUserId()] : "ÿ";

			return strcasecmp($name_a, $name_b);
		};

		foreach ($this->assignments as $corrector_id => $assignment)
		{
			usort($this->assignments[$corrector_id], $by_name);
		}
	}
}