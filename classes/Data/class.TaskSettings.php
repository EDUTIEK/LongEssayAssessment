<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * Plugin Configuration
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class TaskSettings extends \ActiveRecord
{
    use ActiveData;

    /**
     * @var bool
     */
    protected $ar_safe_read = false;
    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_task_settings';


    /**
     * The task_id currently corresponds to the obj_id of the ILIAS object
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $task_id = 0;


    /**
     * The task_id currently corresponds to the obj_id of the ILIAS object
     *
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $description = null;

    /**
     * The task_id currently corresponds to the obj_id of the ILIAS object
     *
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $instructions = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $writing_start = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $writing_end = null;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $correction_start = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $correction_end = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $review_start = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $review_end = null;


    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    /**
     * @param int $task_id
     */
    public function setTaskId(int $task_id): void
    {
        $this->task_id = $task_id;
    }

    /**
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param ?string $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return ?string
     */
    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    /**
     * @param ?string $instructions
     */
    public function setInstructions(?string $instructions): void
    {
        $this->instructions = $instructions;
    }

    /**
     * @return ?string
     */
    public function getWritingStart(): ?string
    {
        return $this->writing_start;
    }

    /**
     * @param ?string $writing_start
     */
    public function setWritingStart(?string $writing_start): void
    {
        $this->writing_start = $writing_start;
    }

    /**
     * @return ?string
     */
    public function getWritingEnd(): ?string
    {
        return $this->writing_end;
    }

    /**
     * @param ?string $writing_end
     */
    public function setWritingEnd(?string $writing_end): void
    {
        $this->writing_end = $writing_end;
    }

    /**
     * @return ?string
     */
    public function getCorrectionStart(): ?string
    {
        return $this->correction_start;
    }

    /**
     * @param ?string $correction_start
     */
    public function setCorrectionStart(?string $correction_start): void
    {
        $this->correction_start = $correction_start;
    }

    /**
     * @return ?string
     */
    public function getCorrectionEnd(): ?string
    {
        return $this->correction_end;
    }

    /**
     * @param ?string $correction_end
     */
    public function setCorrectionEnd(?string $correction_end): void
    {
        $this->correction_end = $correction_end;
    }

    /**
     * @return ?string
     */
    public function getReviewStart(): ?string
    {
        return $this->review_start;
    }

    /**
     * @param ?string $review_start
     */
    public function setReviewStart(?string $review_start): void
    {
        $this->review_start = $review_start;
    }

    /**
     * @return ?string
     */
    public function getReviewEnd(): ?string
    {
        return $this->review_end;
    }

    /**
     * @param ?string $review_end
     */
    public function setReviewEnd(?string $review_end): void
    {
        $this->review_end = $review_end;
    }
}