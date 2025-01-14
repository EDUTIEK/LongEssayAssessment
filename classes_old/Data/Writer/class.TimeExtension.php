<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Writer;


use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * TimeExtension
 *
 * Indexes: (writer_id, task_id), task_id
 *
 * @author Fabian Wolf <wolf@ilias.de>
 */
class TimeExtension extends RecordData
{

	protected const tableName = 'xlas_time_extension';
	protected const hasSequence = true;
	protected const keyTypes = [
		'id' => 'integer',
	];
	protected const otherTypes = [
		'id' => 'integer',
		'writer_id'=> 'integer',
		'task_id' => 'integer',
		'minutes' => 'integer'
	];

    protected int $id = 0;
    protected int $writer_id = 0;
    protected int $task_id = 0;
    protected int $minutes = 0;

	public static function model() {
		return new self();
	}

    /**
     * @return int
     */
    public function getId(): ?int
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