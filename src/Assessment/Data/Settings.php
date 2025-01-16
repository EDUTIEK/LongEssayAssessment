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
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_settings')]
class Settings extends \Edutiek\AssessmentService\Assessment\Data\Settings
{
    private int $online = 0;
    private string $participation_type = '';
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
    private int $keep_available = 0;
    private ?DateTimeImmutable $solution_available_date = null;
    private string $result_available_type = '';
    private ?DateTimeImmutable $result_available_date = null;
    private int $solution_available = 0;
    private int $review_enabled = 0;
    private int $review_notification = 0;
    private ?string $review_notif_text = null;
    private int $statistics_available = 0;

    public function getOnline(): int
    {
        return $this->online;
    }
    public function setOnline(int $online): void
    {
        $this->online = $online;
    }
    public function getParticipationType(): string
    {
        return $this->participation_type;
    }
    public function setParticipationType(string $participation_type): void
    {
        $this->participation_type = $participation_type;
    }
    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): void
    {
        $this->ass_id = $ass_id;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
    public function getClosingMessage(): ?string
    {
        return $this->closing_message;
    }
    public function setClosingMessage(?string $closing_message): void
    {
        $this->closing_message = $closing_message;
    }
    public function getWritingStart(): ?DateTimeImmutable
    {
        return $this->writing_start;
    }
    public function setWritingStart(?DateTimeImmutable $writing_start): void
    {
        $this->writing_start = $writing_start;
    }
    public function getWritingEnd(): ?DateTimeImmutable
    {
        return $this->writing_end;
    }
    public function setWritingEnd(?DateTimeImmutable $writing_end): void
    {
        $this->writing_end = $writing_end;
    }
    public function getWritingLimitMinutes(): ?int
    {
        return $this->writing_limit_minutes;
    }
    public function setWritingLimitMinutes(?int $writing_limit_minutes): void
    {
        $this->writing_limit_minutes = $writing_limit_minutes;
    }
    public function getCorrectionStart(): ?DateTimeImmutable
    {
        return $this->correction_start;
    }
    public function setCorrectionStart(?DateTimeImmutable $correction_start): void
    {
        $this->correction_start = $correction_start;
    }
    public function getCorrectionEnd(): ?DateTimeImmutable
    {
        return $this->correction_end;
    }
    public function setCorrectionEnd(?DateTimeImmutable $correction_end): void
    {
        $this->correction_end = $correction_end;
    }
    public function getReviewStart(): ?DateTimeImmutable
    {
        return $this->review_start;
    }
    public function setReviewStart(?DateTimeImmutable $review_start): void
    {
        $this->review_start = $review_start;
    }
    public function getReviewEnd(): ?DateTimeImmutable
    {
        return $this->review_end;
    }
    public function setReviewEnd(?DateTimeImmutable $review_end): void
    {
        $this->review_end = $review_end;
    }
    public function getKeepAvailable(): int
    {
        return $this->keep_available;
    }
    public function setKeepAvailable(int $keep_available): void
    {
        $this->keep_available = $keep_available;
    }
    public function getSolutionAvailableDate(): ?DateTimeImmutable
    {
        return $this->solution_available_date;
    }
    public function setSolutionAvailableDate(?DateTimeImmutable $solution_available_date): void
    {
        $this->solution_available_date = $solution_available_date;
    }
    public function getResultAvailableType(): string
    {
        return $this->result_available_type;
    }
    public function setResultAvailableType(string $result_available_type): void
    {
        $this->result_available_type = $result_available_type;
    }
    public function getResultAvailableDate(): ?DateTimeImmutable
    {
        return $this->result_available_date;
    }
    public function setResultAvailableDate(?DateTimeImmutable $result_available_date): void
    {
        $this->result_available_date = $result_available_date;
    }
    public function getSolutionAvailable(): int
    {
        return $this->solution_available;
    }
    public function setSolutionAvailable(int $solution_available): void
    {
        $this->solution_available = $solution_available;
    }
    public function getReviewEnabled(): int
    {
        return $this->review_enabled;
    }
    public function setReviewEnabled(int $review_enabled): void
    {
        $this->review_enabled = $review_enabled;
    }
    public function getReviewNotification(): int
    {
        return $this->review_notification;
    }
    public function setReviewNotification(int $review_notification): void
    {
        $this->review_notification = $review_notification;
    }
    public function getReviewNotifText(): ?string
    {
        return $this->review_notif_text;
    }
    public function setReviewNotifText(?string $review_notif_text): void
    {
        $this->review_notif_text = $review_notif_text;
    }
    public function getStatisticsAvailable(): int
    {
        return $this->statistics_available;
    }
    public function setStatisticsAvailable(int $statistics_available): void
    {
        $this->statistics_available = $statistics_available;
    }
}
