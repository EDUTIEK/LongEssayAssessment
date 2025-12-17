<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use Edutiek\AssessmentService\System\Data\UserData;
use Edutiek\AssessmentService\Assessment\Data\Corrector;
use Edutiek\AssessmentService\Task\Data\CorrectorSummary;
use Edutiek\AssessmentService\Task\Data\CorrectorAssignment;

class Correction extends \Edutiek\AssessmentService\Views\Data\Correction
{
    public function __construct(
        private readonly CorrectorAssignment $corrector_assignment,
        private readonly ?CorrectorSummary $corrector_summary,
        private readonly Corrector $corrector,
        private readonly UserData $corrector_data
    ){
    }

    public function getCorrectorAssignment(): CorrectorAssignment
    {
        return $this->corrector_assignment;
    }

    public function getCorrectorSummary(): ?CorrectorSummary
    {
        return $this->corrector_summary;
    }

    public function getCorrector(): Corrector
    {
        return $this->corrector;
    }

    public function getCorrectorData(): UserData
    {
        return $this->corrector_data;
    }
}