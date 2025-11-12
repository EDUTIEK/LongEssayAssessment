<?php

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;

class GradeItem extends Item
{
    public function __construct(
        int $id,
        protected string $grade,
        protected float $min_points,
        protected bool $passed,
        protected ?string $code,
        protected ?string $statement
    ) {
        parent::__construct($id);
    }

    public function getGrade(): string
    {
        return $this->grade;
    }

    public function getMinPoints(): float
    {
        return $this->min_points;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function getCode(): string
    {
        return $this->code ?? "";
    }

    public function getStatement(): ?string
    {
        return $this->statement ?? "";
    }
}
