<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use DateTimeImmutable;

readonly class CorrectorAssessmentView extends \Edutiek\AssessmentService\Views\Data\CorrectorAssessmentView
{
    public function __construct(
        private int $ass_id,
        private int $context_id,
        private string $parent_title,
        private string $title,
        private ?DateTimeImmutable $correction_start,
        private ?DateTimeImmutable $correction_end,
        private int $assigned_gradings,
        private int $finished_gradings
    ) {
    }

    public function getAssId(): int
    {
        return $this->ass_id;
    }

    public function getContextId(): int
    {
        return $this->context_id;
    }

    public function getParentTitle(): string
    {
        return $this->parent_title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCorrectionStart(): ?DateTimeImmutable
    {
        return $this->correction_start;
    }

    public function getCorrectionEnd(): ?DateTimeImmutable
    {
        return $this->correction_end;
    }

    public function getAssignedGradings(): int
    {
        return $this->assigned_gradings;
    }

    public function getFinishedGradings(): int
    {
        return $this->finished_gradings;
    }

}
