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
use Edutiek\AssessmentService\Assessment\Data\ParticipationType;
use Edutiek\AssessmentService\Assessment\Data\ResultAvailableType;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_orga_settings')]
class OrgaSettings extends \Edutiek\AssessmentService\Assessment\Data\OrgaSettings
{
    private bool $online = false;
    private string $participation_type = ParticipationType::INSTANT->value;
    #[Key]
    private int $ass_id = 0;
    private ?string $description = null;
    private ?string $closing_message = null;
    private ?DateTimeImmutable $writing_start = null;
    private ?DateTimeImmutable $writing_end = null;
    private ?int $writing_limit_minutes = null;
    private ?DateTimeImmutable $correction_start = null;
    private ?DateTimeImmutable $correction_end = null;
    private ?DateTimeImmutable $review_start = null;
    private ?DateTimeImmutable $review_end = null;
    private bool $keep_available = false;
    private ?DateTimeImmutable $solution_available_date = null;
    private string $result_available_type = '';
    private ?DateTimeImmutable $result_available_date = null;
    private bool $solution_available = false;
    private bool $review_enabled = false;
    private bool $review_notification = false;
    private ?string $review_notif_text = null;
    private bool $statistics_available = false;

    public function getOnline(): bool
    {
        return $this->online;
    }
    public function setOnline(bool $online): self
    {
        $this->online = $online;
        return $this;
    }
    public function getParticipationType(): ParticipationType
    {
        return ParticipationType::tryFrom($this->participation_type) ?? ParticipationType::INSTANT;
    }
    public function setParticipationType(ParticipationType $participation_type): self
    {
        $this->participation_type = $participation_type->value;
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
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    public function getClosingMessage(): ?string
    {
        return $this->closing_message;
    }
    public function setClosingMessage(?string $closing_message): self
    {
        $this->closing_message = $closing_message;
        return $this;
    }
    public function getWritingStart(): ?DateTimeImmutable
    {
        return $this->writing_start;
    }
    public function setWritingStart(?DateTimeImmutable $writing_start): self
    {
        $this->writing_start = $writing_start;
        return $this;
    }
    public function getWritingEnd(): ?DateTimeImmutable
    {
        return $this->writing_end;
    }
    public function setWritingEnd(?DateTimeImmutable $writing_end): self
    {
        $this->writing_end = $writing_end;
        return $this;
    }
    public function getWritingLimitMinutes(): ?int
    {
        return $this->writing_limit_minutes;
    }
    public function setWritingLimitMinutes(?int $writing_limit_minutes): self
    {
        $this->writing_limit_minutes = $writing_limit_minutes;
        return $this;
    }
    public function getCorrectionStart(): ?DateTimeImmutable
    {
        return $this->correction_start;
    }
    public function setCorrectionStart(?DateTimeImmutable $correction_start): self
    {
        $this->correction_start = $correction_start;
        return $this;
    }
    public function getCorrectionEnd(): ?DateTimeImmutable
    {
        return $this->correction_end;
    }
    public function setCorrectionEnd(?DateTimeImmutable $correction_end): self
    {
        $this->correction_end = $correction_end;
        return $this;
    }
    public function getReviewStart(): ?DateTimeImmutable
    {
        return $this->review_start;
    }
    public function setReviewStart(?DateTimeImmutable $review_start): self
    {
        $this->review_start = $review_start;
        return $this;
    }
    public function getReviewEnd(): ?DateTimeImmutable
    {
        return $this->review_end;
    }
    public function setReviewEnd(?DateTimeImmutable $review_end): self
    {
        $this->review_end = $review_end;
        return $this;
    }
    public function getKeepAvailable(): bool
    {
        return $this->keep_available;
    }
    public function setKeepAvailable(bool $keep_available): self
    {
        $this->keep_available = $keep_available;
        return $this;
    }
    public function getSolutionAvailableDate(): ?DateTimeImmutable
    {
        return $this->solution_available_date;
    }
    public function setSolutionAvailableDate(?DateTimeImmutable $solution_available_date): self
    {
        $this->solution_available_date = $solution_available_date;
        return $this;
    }
    public function getResultAvailableType(): ResultAvailableType
    {
        return ResultAvailableType::tryFrom($this->result_available_type) ?? ResultAvailableType::REVIEW;
    }
    public function setResultAvailableType(ResultAvailableType $result_available_type): self
    {
        $this->result_available_type = $result_available_type->value;
        return $this;
    }
    public function getResultAvailableDate(): ?DateTimeImmutable
    {
        return $this->result_available_date;
    }
    public function setResultAvailableDate(?DateTimeImmutable $result_available_date): self
    {
        $this->result_available_date = $result_available_date;
        return $this;
    }
    public function getSolutionAvailable(): bool
    {
        return $this->solution_available;
    }
    public function setSolutionAvailable(bool $solution_available): self
    {
        $this->solution_available = $solution_available;
        return $this;
    }
    public function getReviewEnabled(): bool
    {
        return $this->review_enabled;
    }
    public function setReviewEnabled(bool $review_enabled): self
    {
        $this->review_enabled = $review_enabled;
        return $this;
    }
    public function getReviewNotification(): bool
    {
        return $this->review_notification;
    }
    public function setReviewNotification(bool $review_notification): self
    {
        $this->review_notification = $review_notification;
        return $this;
    }
    public function getReviewNotifText(): ?string
    {
        return $this->review_notif_text;
    }
    public function setReviewNotifText(?string $review_notif_text): self
    {
        $this->review_notif_text = $review_notif_text;
        return $this;
    }
    public function getStatisticsAvailable(): bool
    {
        return $this->statistics_available;
    }
    public function setStatisticsAvailable(bool $statistics_available): self
    {
        $this->statistics_available = $statistics_available;
        return $this;
    }
}
