<?php

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

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
				$writers["&nbsp;&nbsp;" . $this->getUsername($this->writers[$assignment->getWriterId()]->getUserId(), true)] = $pos;
			}

			$item = $this->uiFactory->item()->standard($this->getUsername($corrector->getUserId()))
				->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'));

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
}