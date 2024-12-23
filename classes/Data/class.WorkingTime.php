<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data;

use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use DateTimeZone;
use DateTimeImmutable;

class WorkingTime
{
    private DateTimeZone $time_zone;

    private ?DateTimeImmutable $common_earliest_start = null;
    private ?DateTimeImmutable $common_latest_end = null;
    private ?DateTimeImmutable $writer_earliest_start = null;
    private ?DateTimeImmutable $writer_latest_end = null;

    private ?int $common_time_limit_minutes = null;
    private ?int $writer_time_limit_minutes = null;

    private ?DateTimeImmutable $working_start = null;


    public function __construct(
        TaskSettings $task,
        ?Writer $writer = null
    )
    {
        $this->time_zone = new DateTimeZone( date_default_timezone_get());

        if ($task->getWritingStart() !== null) {
            $this->common_earliest_start = new DateTimeImmutable($task->getWritingStart(), $this->time_zone);
        }

        if ($task->getWritingEnd() !== null) {
            $this->common_latest_end =new DateTimeImmutable($task->getWritingEnd(), $this->time_zone);
        }

        if ($task->getWritingLimitMinutes() !== null) {
            $this->common_time_limit_minutes = $task->getWritingLimitMinutes();
        }

        if ($writer !== null) {
            $this->writer_earliest_start = $writer->getEarliestStart();
            $this->writer_latest_end = $writer->getLatestEnd();
            $this->working_start = $writer->getWorkingStart();

            if ($writer->getTimeLimitMinutes() !== null) {
                $this->writer_time_limit_minutes = $writer->getTimeLimitMinutes();
            }
        }
    }

    /**
     * Get the earliest time the writing can be started
     */
    public function getEarliestStart(): ?DateTimeImmutable
    {
       return $this->writer_earliest_start ?? $this->common_earliest_start;
    }

    /**
     * Get the latest time the writing can be ended
     */
    public function getLatestEnd(): ?DateTimeImmutable
    {
        return $this->writer_latest_end ?? $this->common_latest_end;
    }

    /**
     * Get a time limit in seconds from the working start
     */
    public function getTimeLimitMinutes(): ?int
    {
        return $this->writer_time_limit_minutes ?? $this->common_time_limit_minutes;
    }

    /**
     * Get the time limit parts (days, hours, minutes)
     * @return array{?int, ?int, ?int}
     */
    public function getTimeLimitParts(): array
    {
        if ($this->getTimeLimitMinutes() === null) {
            return [null, null, null];
        }

        $limit = $this->getTimeLimitMinutes();
        $days = floor($limit / (24 * 60));
        $hours = floor(($limit - $days * 24 * 60) / 60);
        $minutes = $limit % 60;
        return [$days > 0 ? $days : null, $hours > 0 ? $hours : null, $minutes > 0 ? $minutes : null];
    }

    /**
     * Get the start of working
     * This is actively set by the writer and makes the instructions and material available
     */
    public function getWorkingStart(): ?DateTimeImmutable
    {
        return $this->working_start;
    }

    /**
     * Get the individual deadline for the end of working
     * This is calculated from the working start, time limit and latest end
     * After the deadline no inputs should be accepted
     */
    public function getWorkingDeadline(): ?DateTimeImmutable
    {
        if ($this->getWorkingStart() == null || $this->getTimeLimitMinutes() == null) {
            return $this->getLatestEnd();
        }

        $relative_end = $this->getWorkingStart()->getTimestamp() + $this->getTimeLimitMinutes() * 60;

        if ($this->getLatestEnd() == null) {
            return DateTimeImmutable::createFromFormat('U', (string) $relative_end, $this->time_zone);
        }

        $latest_end = $this->getLatestEnd()->getTimestamp();

        return DateTimeImmutable::createFromFormat('U', (string) min($relative_end, $latest_end), $this->time_zone);
    }

    /**
     * Check if the writer has individual time settings
     */
    public function isIndividual(): bool
    {
        return $this->writer_earliest_start !== null ||
            $this->writer_latest_end !== null ||
            $this->writer_time_limit_minutes !== null;
    }

    /**
     * Check if the working time has any kind of limit
     */
    public function isLimited(): bool
    {
        return $this->getEarliestStart() !== null ||
            $this->getLatestEnd() !== null ||
            $this->getTimeLimitMinutes() !== null;
    }

    /**
     * Check if the writer has a time limit counting from the start
     */
    public function hasTimeLimitFromStart(): bool
    {
        return $this->getTimeLimitMinutes() !== null;
    }

    /**
     * Check if the working has already been started
     */
    public function isStarted(): bool
    {
        return $this->getWorkingStart() !== null;
    }

    /**
     * Check if current time is before the earliest start
     * Returns false if no earliest start is defined
     */
    public function isNowBeforeAllowedTime(): bool
    {
        $start = $this->getEarliestStart();
        if ($start === null) {
            return false;
        }

        return time() < $start->getTimestamp();
    }

    /**
     * Check if the current time is after the deadline
     * Returns false if no deadline is defined
     */
    public function isNowAfterAllowedTime(): bool
    {
        $deadline = $this->getWorkingDeadline();
        if ($deadline === null) {
            return false;
        }
        return time() > $deadline->getTimestamp();
    }

    /**
     * Check if current time is within the allowed time frame
     */
    public function isNowInAllowedTime(): bool
    {
        return !$this->isNowBeforeAllowedTime() && !$this->isNowAfterAllowedTime();
    }

    /**
     * Check if the latest end is before the earliest start
     * This should cause an error
     */
    public function isEndBeforeStart(): bool
    {
        return $this->getEarliestStart() !== null &&
            $this->getLatestEnd() !== null &&
            $this->getLatestEnd()->getTimestamp() < $this->getEarliestStart()->getTimestamp();
    }

    /**
     * Check if the time limit exceeds the time span between the earliest start and latest end
     */
    public function isTimeLimitTooLong(): bool
    {
        return $this->getEarliestStart() !== null &&
            $this->getLatestEnd() != null &&
            $this->getTimeLimitMinutes() != null &&
            $this->getTimeLimitMinutes() * 60 > $this->getLatestEnd()->getTimestamp() - $this->getEarliestStart()->getTimestamp();
    }
}