<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class TaskSettings extends ActivePluginRecord
{
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
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $description = null;

    /**
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
     * @con_fieldtype        clob
     */
    protected $solution = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     * Format IL_CAL_DATETIME in default timezone of the installation
     */
    protected $writing_start = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     * Format IL_CAL_DATETIME in default timezone of the installation
     */
    protected $writing_end = null;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     * Format IL_CAL_DATETIME in default timezone of the installation
     */
    protected $correction_start = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     * Format IL_CAL_DATETIME in default timezone of the installation
     */
    protected $correction_end = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     * Format IL_CAL_DATETIME in default timezone of the installation
     */
    protected $review_start = null;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     * Format IL_CAL_DATETIME in default timezone of the installation
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
}