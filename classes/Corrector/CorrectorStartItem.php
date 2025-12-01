<?php

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use Edutiek\AssessmentService\Task\Data\CorrectorSummary;
use Edutiek\AssessmentService\System\Data\UserData;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Task\Data\CorrectorAssignment;
use Edutiek\AssessmentService\Task\AssessmentStatus\CombinedStatus;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\GradingStatus;

class CorrectorStartItem extends Item
{
    public function __construct(
        int $id,
        private Writer $writer,
        private CombinedStatus $correction_status,
        private CorrectorAssignment $assignment,
        private ?CorrectorSummary $summary,
        private ?string $task_title,
        private ?CorrectorSummary $other_summary,
        private ?UserData $other_corrector,
        private ?CorrectorAssignment $other_assignment
    ) {
        parent::__construct($id);
    }

    public function getWriter(): Writer
    {
        return $this->writer;
    }

    public function getCombinedStatus(): CombinedStatus
    {
        return $this->correction_status;
    }

    public function getAssignment(): CorrectorAssignment
    {
        return $this->assignment;
    }

    public function getSummary(): ?CorrectorSummary
    {
        return $this->summary;
    }

    public function getOtherSummary(): ?CorrectorSummary
    {
        return $this->other_summary;
    }

    public function getOtherCorrector(): ?UserData
    {
        return $this->other_corrector;
    }

    public function getTaskTitle(): ?string
    {
        return $this->task_title;
    }

    public function getOtherAssignment(): ?CorrectorAssignment
    {
        return $this->other_assignment;
    }
}
