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

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use DateTimeImmutable;
use Edutiek\AssessmentService\Assessment\Data\CorrectionStatus;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_writer')]
class Writer extends \Edutiek\AssessmentService\Assessment\Data\Writer
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private int $user_id = 0;
    private string $pseudonym = '';
    private int $ass_id = 0;
    private ?DateTimeImmutable $earliest_start = null;
    private ?DateTimeImmutable $latest_end = null;
    private ?int $time_limit_minutes = null;
    private ?DateTimeImmutable $working_start = null;
    private ?float $final_points = null;
    private ?int $final_grade_level_id = null;
    private ?DateTimeImmutable $writing_authorized = null;
    private ?int $writing_authorized_by = null;
    private string $correction_status = CorrectionStatus::OPEN->value;
    private ?DateTimeImmutable $correction_status_changed = null;
    private ?int $correction_status_changed_by = null;
    private ?DateTimeImmutable $writing_excluded = null;
    private ?int $writing_excluded_by = null;
    private ?string $stitch_comment = null;
    private ?int $location = null;
    private int $review_notification = 0;
    private ?string $finalized_from_status = null;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getUserId(): int
    {
        return $this->user_id;
    }
    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }
    public function getPseudonym(): string
    {
        return $this->pseudonym;
    }
    public function setPseudonym(string $pseudonym): self
    {
        $this->pseudonym = $pseudonym;
        return $this;
    }
    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }
    public function getEarliestStart(): ?DateTimeImmutable
    {
        return $this->earliest_start;
    }
    public function setEarliestStart(?DateTimeImmutable $earliest_start): self
    {
        $this->earliest_start = $earliest_start;
        return $this;
    }
    public function getLatestEnd(): ?DateTimeImmutable
    {
        return $this->latest_end;
    }
    public function setLatestEnd(?DateTimeImmutable $latest_end): self
    {
        $this->latest_end = $latest_end;
        return $this;
    }
    public function getTimeLimitMinutes(): ?int
    {
        return $this->time_limit_minutes;
    }
    public function setTimeLimitMinutes(?int $time_limit_minutes): self
    {
        $this->time_limit_minutes = $time_limit_minutes;
        return $this;
    }
    public function getWorkingStart(): ?DateTimeImmutable
    {
        return $this->working_start;
    }
    public function setWorkingStart(?DateTimeImmutable $working_start): self
    {
        $this->working_start = $working_start;
        return $this;
    }
    public function getFinalPoints(): ?float
    {
        return $this->final_points;
    }
    public function setFinalPoints(?float $final_points): self
    {
        $this->final_points = $final_points;
        return $this;
    }
    public function getFinalGradeLevelId(): ?int
    {
        return $this->final_grade_level_id;
    }
    public function setFinalGradeLevelId(?int $final_grade_level_id): self
    {
        $this->final_grade_level_id = $final_grade_level_id;
        return $this;
    }
    public function getWritingAuthorized(): ?DateTimeImmutable
    {
        return $this->writing_authorized;
    }
    public function setWritingAuthorized(?DateTimeImmutable $writing_authorized): self
    {
        $this->writing_authorized = $writing_authorized;
        return $this;
    }
    public function getWritingAuthorizedBy(): ?int
    {
        return $this->writing_authorized_by;
    }
    public function setWritingAuthorizedBy(?int $writing_authorized_by): self
    {
        $this->writing_authorized_by = $writing_authorized_by;
        return $this;
    }
    public function getWritingExcluded(): ?DateTimeImmutable
    {
        return $this->writing_excluded;
    }
    public function setWritingExcluded(?DateTimeImmutable $writing_excluded): self
    {
        $this->writing_excluded = $writing_excluded;
        return $this;
    }
    public function getWritingExcludedBy(): ?int
    {
        return $this->writing_excluded_by;
    }
    public function setWritingExcludedBy(?int $writing_excluded_by): self
    {
        $this->writing_excluded_by = $writing_excluded_by;
        return $this;
    }
    public function getStitchComment(): ?string
    {
        return $this->stitch_comment;
    }
    public function setStitchComment(?string $stitch_comment): self
    {
        $this->stitch_comment = $stitch_comment;
        return $this;
    }
    public function getLocation(): ?int
    {
        return $this->location;
    }
    public function setLocation(?int $location): self
    {
        $this->location = $location;
        return $this;
    }
    public function getReviewNotification(): int
    {
        return $this->review_notification;
    }
    public function setReviewNotification(int $review_notification): self
    {
        $this->review_notification = $review_notification;
        return $this;
    }

    public function getCorrectionStatus(): CorrectionStatus
    {
        return CorrectionStatus::tryFrom($this->correction_status) ?? CorrectionStatus::OPEN;
    }

    public function setCorrectionStatus(CorrectionStatus $status): self
    {
        $this->correction_status = $status->value;
        return $this;
    }

    public function getCorrectionStatusChanged(): ?DateTimeImmutable
    {
        return $this->correction_status_changed;
    }

    public function setCorrectionStatusChanged(DateTimeImmutable $correction_status_changed): self
    {
        $this->correction_status_changed = $correction_status_changed;
        return $this;
    }

    public function getCorrectionStatusChangedBy(): ?int
    {
        return $this->correction_status_changed_by;
    }

    public function setCorrectionStatusChangedBy(?int $correction_status_changed_by): self
    {
        $this->correction_status_changed_by = $correction_status_changed_by;
        return $this;
    }

    public function getFinalizedFromStatus(): ?CorrectionStatus
    {
        return CorrectionStatus::tryFrom((string) $this->finalized_from_status) ?? null;
    }

    public function setFinalizedFromStatus(?CorrectionStatus $finalized_from_status): self
    {
        $this->finalized_from_status = $finalized_from_status?->value;
        return $this;
    }
}
