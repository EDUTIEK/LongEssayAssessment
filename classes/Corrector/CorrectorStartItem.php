<?php

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use Edutiek\AssessmentService\Task\Data\CorrectorSummary;
use Edutiek\AssessmentService\System\Data\UserData;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Task\Data\CorrectorAssignment;
use Edutiek\AssessmentService\Assessment\Data\CombinedStatus;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\GradingStatus;
use Edutiek\AssessmentService\Task\Data\CorrectionItem;

class CorrectorStartItem extends Item
{
    public static function fromCorrectionItem(CorrectionItem $item)
    {
        return new self(
            $item->getAssignmentId(),
            $item
        );
    }

    public function __construct(
        protected int $id,
        private CorrectionItem $item
    ) {
        parent::__construct($id);
    }

    public function getCorrectionItem(): CorrectionItem
    {
        return $this->item;
    }

    public function getWriter(): Writer
    {
        return $this->item->getWriter();
    }

    public function getAssignment(): CorrectorAssignment
    {
        return $this->item->getAssignment();
    }

    public function getSummary(): ?CorrectorSummary
    {
        return $this->item->getSummary();
    }

    public function getOtherSummary(): ?CorrectorSummary
    {
        return $this->item->getOtherSummary();
    }

    public function getOtherCorrector(): ?UserData
    {
        return $this->item->getOtherCorrector();
    }

    public function getTaskTitle(): ?string
    {
        return $this->item->getTaskTitle();
    }

    public function getOtherAssignment(): ?CorrectorAssignment
    {
        return $this->item->getOtherAssignment();
    }
}
