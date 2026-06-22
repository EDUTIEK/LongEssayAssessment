<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Task\Data;

use Edutiek\AssessmentService\Task\Data\CriteriaMode;
use Edutiek\AssessmentService\Task\Data\PdfMarking;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_ta_corr_settings')]
class CorrectionSettings extends \Edutiek\AssessmentService\Task\Data\CorrectionSettings
{
    #[Key]
    private int $ass_id = 0;
    private string $criteria_mode = 'none';
    private string $positive_rating = '';
    private string $negative_rating = '';
    private bool $enable_comments = true;
    private bool $enable_comment_ratings = true;
    private bool $enable_partial_points = true;
    private bool $enable_summary_pdf = true;
    private ?string $summary_pdf_advice = null;
    private string $pdf_marking = PdfMarking::IMAGES->value;

    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }
    public function getCriteriaMode(): CriteriaMode
    {
        return CriteriaMode::tryFrom($this->criteria_mode) ?? CriteriaMode::NONE;
    }
    public function setCriteriaMode(CriteriaMode $criteria_mode): self
    {
        $this->criteria_mode = $criteria_mode->value;
        return $this;
    }
    public function getPositiveRating(): string
    {
        return $this->positive_rating;
    }
    public function setPositiveRating(string $positive_rating): self
    {
        $this->positive_rating = $positive_rating;
        return $this;
    }
    public function getNegativeRating(): string
    {
        return $this->negative_rating;
    }
    public function setNegativeRating(string $negative_rating): self
    {
        $this->negative_rating = $negative_rating;
        return $this;
    }

    public function getEnableComments(): bool
    {
        return $this->enable_comments;
    }

    public function setEnableComments(bool $enable_comments): self
    {
        $this->enable_comments = $enable_comments;
        return $this;
    }

    public function getEnableCommentRatings(): bool
    {
        return $this->enable_comment_ratings;
    }

    public function setEnableCommentRatings(bool $enable_comment_ratings): self
    {
        $this->enable_comment_ratings = $enable_comment_ratings;
        return $this;
    }

    public function getEnablePartialPoints(): bool
    {
        return $this->enable_partial_points;
    }

    public function setEnablePartialPoints(bool $enable_partial_points): self
    {
        $this->enable_partial_points = $enable_partial_points;
        return $this;
    }

    public function getEnableSummaryPdf(): bool
    {
        return $this->enable_summary_pdf;
    }

    public function setEnableSummaryPdf(bool $enable_summary_pdf): self
    {
        $this->enable_summary_pdf = $enable_summary_pdf;
        return $this;
    }

    public function getSummaryPdfAdvice(): ?string
    {
        return $this->summary_pdf_advice;
    }

    public function setSummaryPdfAdvice(?string $summary_pdf_advice): self
    {
        $this->summary_pdf_advice = $summary_pdf_advice;
        return $this;
    }

    public function getPdfMarking(): PdfMarking
    {
        return PdfMarking::tryFrom($this->pdf_marking) ?? PdfMarking::IMAGES;
    }

    public function setPdfMarking(PdfMarking $pdf_marking): self
    {
        $this->pdf_marking = $pdf_marking->value;
        return $this;
    }
}
