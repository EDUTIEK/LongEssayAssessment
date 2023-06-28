<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;

class CorrectorAdminListGUI extends WriterListGUI
{

	/**
	 * @var \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector[]
	 */
	private $correctors = [];

	/**
	 * @var CorrectorAssignment[][]
	 */
	private $assignments = [];

	/**
	 * @var \ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings
	 */
	private $correction_settings;


	/**
	 * @var int[]
	 */
	private array $correction_status_stitches = [];

	public function __construct(object $parent, string $parent_cmd, \ilLongEssayAssessmentPlugin $plugin, CorrectionSettings $correction_settings)
	{
		parent::__construct($parent, $parent_cmd, $plugin);
		$this->correction_settings = $correction_settings;
	}


	public function getContent():string
	{
		$this->loadUserData();
		$this->sortWriter();

		$items = [];
		$modals = [];

        $count_total = count($this->writers);
        $count_filtered = 0;

		foreach ($this->writers as $writer) {
			if(!$this->isFiltered($writer)){
				continue;
			}
            $count_filtered++;

			$change_corrector_modal = $this->buildFormModalCorrectorAssignment($writer);
			$modals[] = $change_corrector_modal;
			$actions = [];
			$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_correction'), $this->getViewCorrectionAction($writer));
            if ($this->hasCorrectionStatusStitchDecided($writer)) {
                $sight_modal = $this->uiFactory->modal()->lightbox($this->uiFactory->modal()->lightboxTextPage(
                    $this->localDI->getDataService($writer->getTaskId())->cleanupRichText($this->essays[$writer->getId()]->getStitchComment()),
                    $this->getWriterName($writer, true). $this->getWriterAnchor($writer),
                ));
                $modals[] = $sight_modal;
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_stitch_comment'), '')->withOnClick($sight_modal->getShowSignal());
            }

			$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('change_corrector'), "")
				->withOnClick($change_corrector_modal->getShowSignal());

			if($this->hasCorrectionStatusStitch($writer)){
				$actions[] = $this->uiFactory->button()->shy($this->plugin->txt('draw_stitch_decision'), $this->getCorrectionStatusStitchAction($writer));
			}

            $properties = [
				$this->plugin->txt("pseudonym") => $writer->getPseudonym(),
				$this->plugin->txt("status") => $this->essayStatus($writer)
			];

			foreach($this->getAssignmentsByWriter($writer) as $assignment){
				switch($assignment->getPosition()){
					case 0: $pos = $this->plugin->txt("assignment_pos_first");break;
					case 1: $pos = $this->plugin->txt("assignment_pos_second");break;
					default: $pos = $this->plugin->txt("assignment_pos_other");break;
				}
				$properties[$pos] = $this->getAssignedCorrectorName($writer, $assignment->getPosition());
			}

            $essay = $this->essays[$writer->getId()] ?? null;
            if (!empty($essay)) {
                if (!empty($essay->getCorrectionFinalized())
                    || !empty($this->localDI->getCorrectorAdminService($essay->getTaskId())->getAuthorizedSummaries($essay))
                ) {
                    $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('remove_authorizations'), $this->getRemoveAuthorisationsAction($writer));
                }

                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt('export_steps'), $this->getExportStepsTarget($writer));
                $properties[$this->plugin->txt("final_grade")] = $this->localDI->getDataService($writer->getTaskId())->formatFinalResult($essay);
            }

			$actions_dropdown = $this->uiFactory->dropdown()->standard($actions)
				->withLabel($this->plugin->txt("actions"));

			$items[] = $this->localDI->getUIFactory()->item()->formItem($this->getWriterName($writer, true). $this->getWriterAnchor($writer))
				->withName($writer->getId())
				->withLeadIcon($this->getWriterIcon($writer))
				->withProperties($properties)
				->withActions($actions_dropdown);
		}

		$resources = $this->localDI->getUIFactory()->item()->formGroup($this->plugin->txt("correctable_exams")
            . $this->localDI->getDataService(0)->formatCounterSuffix($count_filtered, $count_total), $items, "");

		$assign_callback_signal = $resources->generateDSCallbackSignal();
		$this->ctrl->clearParameters($this->parent);
		$modals[] = $resources->addDSModalTriggerToModal(
			$this->uiFactory->modal()->roundtrip("",[]),
			$this->ctrl->getFormAction($this->parent, "editAssignmentsAsync", "", true),
			"writer_ids",
			$assign_callback_signal
		);

		$form_actions[] = $resources->addDSModalTriggerToButton(
			$this->uiFactory->button()->shy($this->plugin->txt("change_corrector"), "#"),
			$assign_callback_signal
		);


		return $this->renderer->render($this->filterControl()) . '<br><br>' .
			$this->renderer->render(array_merge([$resources->withActions($this->uiFactory->dropdown()->standard($form_actions))], $modals));
	}

	private function getAssignedCorrectorName(Writer $writer, int $pos): string
	{
		if(($assignment = $this->getAssignmentByWriterPosition($writer, $pos)) !== null){
			$corrector = $this->correctors[$assignment->getCorrectorId()];

            if (!empty($essay = $this->essays[$writer->getId()])) {
                $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId($essay->getId(), $assignment->getCorrectorId());
            }

            return $this->getUsername($corrector->getUserId())
                . ' - ' . $this->localDI->getDataService($corrector->getTaskId())->formatCorrectionResult($summary);
        }

		return " - ";
	}

	private function getViewCorrectionAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
		return $this->ctrl->getLinkTargetByClass(["ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI"], "viewCorrections");
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

    private function hasCorrectionStatusStitchDecided($writer): bool
    {
        return isset($this->essays[$writer->getId()])
            && !empty($this->essays[$writer->getId()]->getStitchComment());
    }

	private function getCorrectionStatusStitchAction(Writer $writer): string
	{
		$this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
		return $this->ctrl->getLinkTarget($this->parent, "stitchDecision");
	}

    private function getRemoveAuthorisationsAction(Writer $writer): string
    {
        $this->ctrl->setParameter($this->parent, "writer_id", $writer->getId());
        return $this->ctrl->getLinkTarget($this->parent, "confirmRemoveAuthorizations");
    }

    private function essayStatus(Writer $writer)
	{
		if (isset($this->essays[$writer->getId()])) {
			$essay = $this->essays[$writer->getId()];

			if($essay->getWritingExcluded() !== null)
			{
				return '<strong>' . $this->plugin->txt("writing_excluded_from") . " " .
					$this->getUsername($essay->getWritingExcludedBy(), true) . '</strong>';
			}

			if ($essay->getCorrectionFinalized() !== null) {
				return $this->plugin->txt("writing_finalized_from") . " " .
					$this->getUsername($essay->getCorrectionFinalizedBy(), true);
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

			if ($essay->getEditStarted() !== null) {
				return $this->plugin->txt("writing_edit_started");
			}
		}

		return $this->plugin->txt("writing_not_started");
	}


	private function buildFormModalCorrectorAssignment(Writer $writer): \ILIAS\UI\Component\Modal\RoundTrip
	{
		$form = new \ilPropertyFormGUI();
		$form->setId(uniqid('form'));

		$options = [-1 => ""];

		foreach($this->correctors as $corrector){
			$options[$corrector->getId()] = $this->getUsername($corrector->getUserId(), true);
		}

		if($this->correction_settings->getRequiredCorrectors() > 0){
			$cc = $this->correction_settings->getRequiredCorrectors();
		}else{
			$cc = 3;
		}

		for($i = 0; $i <  $cc; $i++){
			switch($i){
				case 0: $pos = $this->plugin->txt("assignment_pos_first");break;
				case 1: $pos = $this->plugin->txt("assignment_pos_second");break;
				default: $pos = $this->plugin->txt("assignment_pos_other");break;
			}
			$val = -1;
			if(($ass = $this->getAssignmentByWriterPosition($writer, $i)) !== null){
				$val = $ass->getCorrectorId();
			}

			$item = new \ilSelectInputGUI($pos, 'corrector[]');
			$item->setOptions($options);
			$item->setValue($val);
			$form->addItem($item);
		}

		$form->setFormAction($this->getChangeCorrectorAction($writer));

		$item = new \ilHiddenInputGUI('cmd');
		$item->setValue('submit');
		$form->addItem($item);

		return $this->buildFormModal($this->plugin->txt("change_corrector"), $form);
	}


	private function buildFormModal(string $title, \ilPropertyFormGUI $form): \ILIAS\UI\Component\Modal\RoundTrip
	{
		global $DIC;
		$factory = $DIC->ui()->factory();
		$renderer = $DIC->ui()->renderer();

		// Build the form
		$item = new \ilHiddenInputGUI('cmd');
		$item->setValue('submit');
		$form->addItem($item);

		// Build a submit button (action button) for the modal footer
		$form_id = 'form_' . $form->getId();
		$submit = $factory->button()->primary('Submit', '#')
			->withOnLoadCode(function ($id) use ($form_id) {
				return "$('#{$id}').click(function() { $('#{$form_id}').submit(); return false; });";
			});

		return $factory->modal()->roundtrip($title, $factory->legacy($form->getHTML()))
			->withActionButtons([$submit]);
	}

	/**
	 * @return \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector[]
	 */
	public function getCorrectors(): array
	{
		return $this->correctors;
	}

	/**
	 * @param \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector[] $correctors
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
	 * @param \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector $corrector
	 * @return array|\ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment[]
	 */
	private function getAssignmentsByWriter(Writer $writer): array
	{
		if (array_key_exists($writer->getId(), $this->assignments)){
			return $this->assignments[$writer->getId()];
		}
		return [];
	}

	/**
	 * @param \ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer $writer
	 * @param int $position
	 * @return \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment|null
	 */
	private function getAssignmentByWriterPosition(Writer $writer, int $position): ?CorrectorAssignment
	{
		if(array_key_exists($writer->getId(), $this->assignments)) {
			foreach($this->assignments[$writer->getId()] as $assignment){
				if($assignment->getPosition() === $position){
					return $assignment;
				}
			}
		}
		return null;
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
			usort($this->assignments[$key], function($a, $b){return $a->getPosition() - $b->getPosition();});
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

		if($essay !== null && in_array($essay->getId(), $this->correction_status_stitches)){
			$stitch = true;
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
