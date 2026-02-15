<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use Edutiek\AssessmentService\Assessment\Data\Properties;
use Edutiek\AssessmentService\System\Data\UserData;

class GradingObject extends \Edutiek\AssessmentService\Views\Data\GradingObject
{
    public function __construct(
        private Properties $assessment,
        private UserData $user,
        private int $writer_id,
        private bool $attended,
        private bool $finalized,
        private ?float $points,
        private ?string $grade,
        private bool $passed,
    ) {}

    public function getWriterId(): int
    {
        return $this->writer_id;
    }

    public function isAttended(): bool
    {
        return $this->attended;
    }

    public function isFinalized(): bool
    {
        return $this->finalized;
    }

    public function getPoints(): ?float
    {
        return $this->points;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function getAssessment(): Properties
    {
        return $this->assessment;
    }

    public function getUserData(): UserData
    {
        return $this->user;
    }
}