<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

class GradingObject extends \Edutiek\AssessmentService\Views\Data\GradingObject
{
    /**
     * @param int      $reference
     * @param int      $writer_id
     * @param bool     $attended
     * @param bool     $finalized
     * @param int|null $points
     * @param string|null $grade
     * @param bool     $passed
     */
    public function __construct(
        private int $reference,
        private int $writer_id,
        private bool $attended,
        private bool $finalized,
        private ?int $points,
        private ?string $grade,
        private bool $passed,
    ) {}

    public function getReference(): int
    {
        return $this->reference;
    }

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

    public function getPoints(): ?int
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
}