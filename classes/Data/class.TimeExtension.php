<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * TimeExtension
 *
 * Indexes: (writer_id, task_id), task_id
 *
 * @author Fabian Wolf <wolf@ilias.de>
 */
class TimeExtension extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_time_extension';


    /**
     * ID
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_sequence         true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $id;

    /**
     * The writer id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $writer_id;

    /**
     * The Task Id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $task_id;

    /**
     * @var int
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $minutes = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return TimeExtension
     */
    public function setId(int $id): TimeExtension
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getWriterId(): int
    {
        return $this->writer_id;
    }

    /**
     * @param int $writer_id
     * @return TimeExtension
     */
    public function setWriterId(int $writer_id): TimeExtension
    {
        $this->writer_id = $writer_id;
        return $this;
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
     * @return TimeExtension
     */
    public function setTaskId(int $task_id): TimeExtension
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinutes(): int
    {
        return $this->minutes;
    }

    /**
     * @param int $minutes
     * @return TimeExtension
     */
    public function setMinutes(int $minutes): TimeExtension
    {
        $this->minutes = $minutes;
        return $this;
    }
}