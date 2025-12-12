<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

class EssayTaskSummary extends \Edutiek\AssessmentService\Views\Data\EssayTaskSummary
{
    public function __construct(
        private readonly ?\DateTimeImmutable $last_save,
        private readonly bool $has_pdf_uploads,
        private readonly ?int $words
    ) {
    }

    public function getLastSave(): ?\DateTimeImmutable
    {
        return $this->last_save;
    }

    public function hasPdfUploads(): bool
    {
        return $this->has_pdf_uploads;
    }

    public function getWords(): ?int
    {
        return $this->words;
    }
}
