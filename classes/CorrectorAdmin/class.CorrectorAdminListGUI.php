<?php

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use ILIAS\Plugin\LongEssayTask\Data\Corrector;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorAssignment;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\TimeExtension;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\Data\WriterHistory;

class CorrectorAdminListGUI extends WriterListGUI
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

	/**
	 * @var int[]
	 */
	private array $correction_status_stitches = [];

	public function getContent():string
	{
		$this->loadUserData();

		$items = [];

		foreach ($this->writers as $writer) {
			if(!$this->isFiltered($writer)){
				continue;
			}

			$actions = [];
			$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_correction'), $this->getViewCorrectionAction($writer));
			$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('change_corrector'), $this->getChangeCorrectorAction($writer));

			if($this->hasCorrectionStatusStitch($writer)){
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('correction_status_stitch'), $this->getCorrectionStatusStitchAction($writer));
			}

			$properties = [];

			foreach($this->getAssignmentsByWriter($writer) as $assignment){
				switch($assignment->getPosition()){
					case 0: $pos = $this->plugin->txt("first_corrector");break;
					case 1: $pos = $this->plugin->txt("second_corrector");break;
					default: $pos = $this->plugin->txt("assignment_pos_other");break;
				}
				$properties[$pos] = $this->getAssignedCorrectorName($writer, $assignment->getPosition());
			}
			$properties[$this->plugin->txt("status")] = $this->essayStatus($writer);

			$items[] = $this->uiFactory->item()->standard($this->getWriterName($writer))
				->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
				->withProperties($properties)
				->withActions($this->uiFactory->dropdown()->standard($actions));
		}

		$resources = $this->uiFactory->item()->group($this->plugin->txt("correctable_exams"), $items);

		return $this->renderer->render($this->filterControl()) . '<br><br>' .
			$this->renderer->render($resources);
	}

	private function getAssignedCorrectorName(Writer $writer, int $pos): string
	{
		if (isset($this->assignments[$writer->getId()][$pos])) {
			$assignment = $this->assignments[$writer->getId()][$pos];
			$corrector = $this->correctors[$assignment->getCorrectorId()];
			return $this->getUsername($corrector->getUserId());
		}
		return " - ";
	}

	private function getViewCorrectionAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
		return $this->ctrl->getLinkTargetByClass(["ILIAS\Plugin\LongEssayTask\Corrector\CorrectorStartGUI"], "startCorrector");
	}

	private function getChangeCorrectorAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
		return $this->ctrl->getLinkTarget($this->parent, "changeCorrector");
	}

	private function hasCorrectionStatusStitch($writer): bool
	{
		if (!isset($this->essays[$writer->getId()])) {
			return false;
		}
		$essay = $this->essays[$writer->getId()];
		return in_array($essay->getId(), $this->getCorrectionStatusStitches());
	}

	private function getCorrectionStatusStitchAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
		return $this->ctrl->getLinkTarget($this->parent, "correctionStatusStitchView");
	}

	private function essayStatus(Writer $writer)
	{
		if (isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];

			if ($essay->getCorrectionFinalized() !== null) {
				return $this->plugin->txt("writing_finalized_from") . " " .
					$this->getUsername($essay->getWritingAuthorizedBy(), true);
			}

			if(in_array($essay->getId(), $this->getCorrectionStatusStitches())){
				return $this->plugin->txt("correction_status_stitch_needed");
			}

			if ($essay->getWritingAuthorized() !== null) {
				$name = $this->plugin->txt("participant");
				if ($essay->getWritingAuthorizedBy() != $writer->getUserId()) {
					$name = $this->getUsername($essay->getWritingAuthorizedBy(), true);
				}

				return $this->plugin->txt("writing_authorized_from") . " " .$name;
			}

			if ($essay->getEditEnded() !== null) {
				return $this->plugin->txt("writing_edit_ended");
			}

			if ($essay->getEditStarted() !== null) {
				return $this->plugin->txt("writing_edit_started");
			}
		}

		return $this->plugin->txt("writing_not_started");
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
	 * @param Corrector $corrector
	 * @return array|CorrectorAssignment[]
	 */
	private function getAssignmentsByWriter(Writer $writer): array
	{
		if (array_key_exists($writer->getId(), $this->assignments)){
			return $this->assignments[$writer->getId()];
		}
		return [];
	}

	/**
	 * @param CorrectorAssignment[] $assignments
	 */
	public function setAssignments(array $assignments): void
	{
		foreach ($assignments as $assignment) {
			if (! isset($this->assignments[$assignment->getWriterId()])){
				$this->assignments[$assignment->getWriterId()] = [];
			}
			$this->assignments[$assignment->getWriterId()][$assignment->getPosition()] = $assignment;
		}
		foreach($this->assignments as $key => $val){
			usort($this->assignments[$key], function($a, $b){return ($a->getPosition() < $b->getPosition())?-1:1;});
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
		foreach ($essays as $essay) {
			$this->essays[$essay->getWriterId()] = $essay;
			$this->user_ids[] = $essay->getCorrectionFinalizedBy();
			$this->user_ids[] = $essay->getWritingAuthorizedBy();
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

	public function isFiltered(Writer $writer):bool
	{
		$filter = $this->getFilter();
		$essay = null;
		$stitch = null;

		if(array_key_exists($writer->getId(), $this->essays)){
			$essay = $this->essays[$writer->getId()];
		}

		if($essay !== null && array_key_exists($essay->getId(), $this->correction_status_stitches)){
			$stitch = $this->extensions[$essay->getId()];
		}

		switch($filter){
			case "corrected":
				return $essay !== null && $essay->getCorrectionFinalized() !== null;
			case "not_corrected":
				return $essay === null || $essay->getCorrectionFinalized() === null;
			case "with_stitch":
				return $stitch !== null;
			case "all":
			default:
				return true;
		}
	}

	public function getFilter(){
		global $DIC;
		$query = $DIC->http()->request()->getQueryParams();
		if(array_key_exists("filter", $query) && in_array($query["filter"], ["all", "corrected", "not_corrected", "with_stitch"])){
			return $query["filter"];
		}
		return "all";
	}

	public function filterControl()
	{

		$link = $this->ctrl->getLinkTarget($this->parent, $this->parent_cmd);
		$filter = [
			"all",
			"corrected",
			"not_corrected",
			"with_stitch"
		];

		$actions  = [];
		foreach ($filter as $key){
			$actions[$this->plugin->txt("filter_corrector_admin_list_" . $key)] = $link . "&filter=" . $key;
		}

		$aria_label = "change_the_currently_displayed_mode";
		return $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive($this->plugin->txt("filter_corrector_admin_list_" . $this->getFilter()));
	}
}