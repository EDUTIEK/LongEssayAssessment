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

	public function getContent():string
	{
		$this->loadUserData();

		$actions = array(
			"Alle" => "all",
			"Korrigiert" => "",
			"Noch nicht korrigiert" => "",
			"Stichentscheid gefordert" => "",
		);

		$aria_label = "change_the_currently_displayed_mode";
		$view_control = $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive("Alle");

		foreach ($this->writers as $writer) {
			$actions = [];
			$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_correction'), $this->getViewCorrectionAction($writer));
			$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('change_corrector'), $this->getChangeCorrectorAction($writer));
			$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('change_corrector'), $this->getReviewAction($writer));

			$items[] = $this->uiFactory->item()->standard($this->getUsername($writer->getUserId()))
				->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
				->withProperties(array(
					$this->plugin->txt("first_corrector") => $this->getAssignedCorrectorName($writer, 0),
					$this->plugin->txt("second_corrector") => $this->getAssignedCorrectorName($writer, 1),
					$this->plugin->txt("status") => $this->essayStatus($writer),
				))
				->withActions(
					$this->uiFactory->dropdown()->standard($actions));
		}

		$resources = $this->uiFactory->item()->group($this->plugin->txt("correctable_exams"), $items);

		return $this->renderer->render($view_control) . '<br><br>' .
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
		$this->ctrl->setParameter($this->parent, "some_id", "id");
		return $this->ctrl->getLinkTarget($this->parent, "viewCorrection");
	}

	private function getChangeCorrectorAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
		return $this->ctrl->getLinkTarget($this->parent, "changeCorrector");
	}

	private function getReviewAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent, "some_id", "id");
		return $this->ctrl->getLinkTarget($this->parent, "review");
	}

	private function essayStatus(Writer $writer)
	{
		if (isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];

			if ($essay->getCorrectionFinalized() !== null) {
				return $this->plugin->txt("writing_finalized_from") . $this->getUsername($essay->getWritingAuthorizedBy());
			}

			// TODO: Calc Sichtentscheid gefordert

			if ($essay->getWritingAuthorized() !== null) {
				$name = $this->plugin->txt("user");
				if ($essay->getWritingAuthorizedBy() != $writer->getUserId()) {
					$name = $this->getUsername($essay->getWritingAuthorizedBy());
				}

				return $this->plugin->txt("writing_authorized_from") . $name;
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
	 * @param CorrectorAssignment[] $assignments
	 */
	public function setAssignments(array $assignments): void
	{
		foreach ($assignments as $assignment) {
			$assignments[$assignment->getWriterId()][$assignment->getPosition()] = $assignment;
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
}