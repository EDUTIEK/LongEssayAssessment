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

namespace ILIAS\Plugin\LongEssayAssessment\EssayTask\Data;

use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_corr_summary')]
class CorrectorSummary extends \Edutiek\AssessmentService\EssayTask\Data\CorrectorSummary
{
    #[Key]
    private int $id;
    private int $essay_id;
    private int $corrector_id;
    private ?string $summary_text;
    private ?float $points;
    private ?DateTimeImmutable $last_change;
    private ?int $include_comments;
    private ?int $include_comment_ratings;
    private ?int $include_comment_points;
    private ?int $include_criteria_points;
    private ?DateTimeImmutable $corection_authorized;
    private ?int $correction_authorized_by;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getEssayId(): int
    {
        return $this->essay_id;
    }
    public function setEssayId(int $essay_id): void
    {
        $this->essay_id = $essay_id;
    }
    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }
    public function setCorrectorId(int $corrector_id): void
    {
        $this->corrector_id = $corrector_id;
    }
    public function getSummaryText(): ?string
    {
        return $this->summary_text;
    }
    public function setSummaryText(?string $summary_text): void
    {
        $this->summary_text = $summary_text;
    }
    public function getPoints(): ?float
    {
        return $this->points;
    }
    public function setPoints(?float $points): void
    {
        $this->points = $points;
    }
    public function getLastChange(): ?DateTimeImmutable
    {
        return $this->last_change;
    }
    public function setLastChange(?DateTimeImmutable $last_change): void
    {
        $this->last_change = $last_change;
    }
    public function getIncludeComments(): ?int
    {
        return $this->include_comments;
    }
    public function setIncludeComments(?int $include_comments): void
    {
        $this->include_comments = $include_comments;
    }
    public function getIncludeCommentRatings(): ?int
    {
        return $this->include_comment_ratings;
    }
    public function setIncludeCommentRatings(?int $include_comment_ratings): void
    {
        $this->include_comment_ratings = $include_comment_ratings;
    }
    public function getIncludeCommentPoints(): ?int
    {
        return $this->include_comment_points;
    }
    public function setIncludeCommentPoints(?int $include_comment_points): void
    {
        $this->include_comment_points = $include_comment_points;
    }
    public function getIncludeCriteriaPoints(): ?int
    {
        return $this->include_criteria_points;
    }
    public function setIncludeCriteriaPoints(?int $include_criteria_points): void
    {
        $this->include_criteria_points = $include_criteria_points;
    }
    public function getCorectionAuthorized(): ?DateTimeImmutable
    {
        return $this->corection_authorized;
    }
    public function setCorectionAuthorized(?DateTimeImmutable $corection_authorized): void
    {
        $this->corection_authorized = $corection_authorized;
    }
    public function getCorrectionAuthorizedBy(): ?int
    {
        return $this->correction_authorized_by;
    }
    public function setCorrectionAuthorizedBy(?int $correction_authorized_by): void
    {
        $this->correction_authorized_by = $correction_authorized_by;
    }
}
