<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class TaskSettings extends RecordData
{
    const RESULT_AVAILABLE_FINALISED = 'finalised';
    const RESULT_AVAILABLE_REVIEW = 'review';
    const RESULT_AVAILABLE_DATE = 'date';

    protected const tableName = 'xlas_task_settings';
    protected const hasSequence = false;
    protected const keyTypes = [
        'task_id' => 'integer',
    ];
    protected const otherTypes = [
        'description' => 'text',
        'instructions' => 'text',
        'solution' => 'text',
        'closing_message' => 'text',
        'writing_start' => 'datetime',
        'writing_end' => 'datetime',
        'correction_start' => 'datetime',
        'correction_end' => 'datetime',
        'review_enabled' => 'integer',
        'review_notification' => 'integer',
        'review_start' => 'datetime',
        'review_end' => 'datetime',
        'keep_essay_available' => 'integer',
        'solution_available' => 'integer',
        'solution_available_date' => 'datetime',
        'result_available_type' => 'text',
        'result_available_date' => 'datetime'
    ];


    protected int $task_id;
    protected ?string $description = null;
    protected ?string $instructions = null;
    protected ?string $solution = null;
    protected ?string $closing_message = null;
    protected ?string $writing_start = null;
    protected ?string $writing_end = null;
    protected ?string $correction_start = null;
    protected ?string $correction_end = null;
    protected int $review_enabled = 0;
    protected int $review_notification = 0;
    protected ?string $review_start = null;
    protected ?string $review_end = null;
    protected int $keep_essay_available = 0;
    protected int $solution_available = 0;
    protected ?string $solution_available_date = null;
    protected string $result_available_type = self::RESULT_AVAILABLE_REVIEW;
    protected ?string $result_available_date = null;


    public function __construct(int $task_id)
    {
        $this->task_id = $task_id;
    }

    public static function model()
    {
        return new self(0);
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    /**
     * @param int $task_id
     * @return TaskSettings
     */
    public function setTaskId(int $task_id): TaskSettings
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return TaskSettings
     */
    public function setDescription(?string $description): TaskSettings
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    /**
     * @param string $instructions
     * @return TaskSettings
     */
    public function setInstructions(?string $instructions): TaskSettings
    {
        $this->instructions = $instructions;
        return $this;
    }

    /**
     * @return string
     */
    public function getSolution(): ?string
    {
        return $this->solution;
    }

    /**
     * @param string $solution
     * @return TaskSettings
     */
    public function setSolution(?string $solution): TaskSettings
    {
        $this->solution = $solution;
        return $this;
    }

    /**
     * @return string
     */
    public function getClosingMessage(): ?string
    {
        return $this->closing_message;
    }

    /**
     * @param ?string $closing_message
     * @return TaskSettings
     */
    public function setClosingMessage(?string $closing_message): TaskSettings
    {
        $this->closing_message = $closing_message;
        return $this;
    }

    /**
     * @return string
     */
    public function getWritingStart(): ?string
    {
        return $this->writing_start;
    }

    /**
     * @param string $writing_start
     * @return TaskSettings
     */
    public function setWritingStart(?string $writing_start): TaskSettings
    {
        $this->writing_start = $writing_start;
        return $this;
    }

    /**
     * @return string
     */
    public function getWritingEnd(): ?string
    {
        return $this->writing_end;
    }

    /**
     * @param string $writing_end
     * @return TaskSettings
     */
    public function setWritingEnd(?string $writing_end): TaskSettings
    {
        $this->writing_end = $writing_end;
        return $this;
    }

    /**
     * @return string
     */
    public function getCorrectionStart(): ?string
    {
        return $this->correction_start;
    }

    /**
     * @param string $correction_start
     * @return TaskSettings
     */
    public function setCorrectionStart(?string $correction_start): TaskSettings
    {
        $this->correction_start = $correction_start;
        return $this;
    }

    /**
     * @return string
     */
    public function getCorrectionEnd(): ?string
    {
        return $this->correction_end;
    }

    /**
     * @param string $correction_end
     * @return TaskSettings
     */
    public function setCorrectionEnd(?string $correction_end): TaskSettings
    {
        $this->correction_end = $correction_end;
        return $this;
    }

    /**
     * @return string
     */
    public function getReviewStart(): ?string
    {
        return $this->review_start;
    }

    /**
     * @param string $review_start
     * @return TaskSettings
     */
    public function setReviewStart(?string $review_start): TaskSettings
    {
        $this->review_start = $review_start;
        return $this;
    }

    /**
     * @return string
     */
    public function getReviewEnd(): ?string
    {
        return $this->review_end;
    }

    /**
     * @param string $review_end
     * @return TaskSettings
     */
    public function setReviewEnd(?string $review_end): TaskSettings
    {
        $this->review_end = $review_end;
        return $this;
    }

    /**
     * @return bool
     */
    public function getKeepEssayAvailable() : bool
    {
        return (bool) $this->keep_essay_available;
    }

    /**
     * @param bool $keep_essay_available
     * @return TaskSettings
     */
    public function setKeepEssayAvailable(bool $keep_essay_available): TaskSettings
    {
        $this->keep_essay_available = (int) $keep_essay_available;
        return $this;
    }

    /**
     * @return string
     */
    public function getSolutionAvailableDate(): ?string
    {
        return $this->solution_available_date;
    }

    /**
     * @param string|null $solution_available_date
     * @return TaskSettings
     */
    public function setSolutionAvailableDate(?string $solution_available_date): TaskSettings
    {
        $this->solution_available_date = $solution_available_date;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultAvailableType(): string
    {
        return $this->result_available_type;
    }

    /**
     * @param string $result_available_type
     * @return TaskSettings
     */
    public function setResultAvailableType(string $result_available_type): TaskSettings
    {
        $this->result_available_type = $result_available_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultAvailableDate(): ?string
    {
        return $this->result_available_date;
    }

    /**
     * @param string|null $result_available_date
     * @return TaskSettings
     */
    public function setResultAvailableDate(?string $result_available_date): TaskSettings
    {
        $this->result_available_date = $result_available_date;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSolutionAvailable() : bool
    {
        return (bool) $this->solution_available;
    }

    /**
     * @param bool $solution_available
     * @return TaskSettings
     */
    public function setSolutionAvailable(bool $solution_available): TaskSettings
    {
        $this->solution_available = (int) $solution_available;
        return $this;
    }

    /**
     * @return bool
     */
    public function isReviewEnabled(): bool
    {
        return (bool) $this->review_enabled;
    }

    /**
     * @param bool $review_enabled
     * @return $this
     */
    public function setReviewEnabled(bool $review_enabled): TaskSettings
    {
        $this->review_enabled = (int)$review_enabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isReviewNotification(): bool
    {
        return (bool) $this->review_notification;
    }

    /**
     * @param bool $review_notification
     * @return $this
     */
    public function setReviewNotification(bool $review_notification): TaskSettings
    {
        $this->review_notification = (int) $review_notification;
        return $this;
    }
}
