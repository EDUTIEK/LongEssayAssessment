<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Writer;


use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Writer
 *
 * Indexes: (user_id, task_id), task_id
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Writer extends RecordData
{
	protected const tableName = 'xlas_writer';
	protected const hasSequence = true;
	protected const keyTypes = [
		'id' => 'integer',
	];
	protected const otherTypes = [
		'user_id'=> 'integer',
		'task_id' => 'integer',
		'pseudonym' => 'text',
        'earliest_start' => 'datetime',
        'latest_end' => 'datetime',
        'time_limit_minutes' => 'integer',
        'working_start' => 'datetime'
	];

    protected int $id = 0;
    protected int $user_id = 0;
	protected int $task_id = 0;
    protected $pseudonym = null;
    protected ?string $earliest_start = null;
    protected ?string $latest_end = null;
    protected ?string $working_start = null;
    protected ?int $time_limit_minutes = null;

    protected DateTimeZone $time_zone;

	public static function model() {
		return new self();
	}

    public function __construct() {
        $this->time_zone = new DateTimeZone(date_default_timezone_get());
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Writer
     */
    public function setId(int $id): Writer
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return (int) $this->user_id;
    }

    /**
     * @param int $user_id
     * @return Writer
     */
    public function setUserId(int $user_id): Writer
    {
        $this->user_id = $user_id;
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
     * @return Writer
     */
    public function setTaskId(int $task_id): Writer
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getPseudonym(): ?string
    {
        return $this->pseudonym;
    }

    /**
     * @param string $pseudonym
     * @return Writer
     */
    public function setPseudonym(?string $pseudonym): Writer
    {
        $this->pseudonym = $pseudonym;
        return $this;
    }

    public function getEarliestStart(): ?DateTimeImmutable
    {
        if ($this->earliest_start !== null) {
            return new DateTimeImmutable($this->earliest_start, $this->time_zone);
        }
        return null;
    }

    public function setEarliestStart(?DateTimeImmutable $earliest_start): Writer
    {
        if ($earliest_start !== null) {
            $this->earliest_start = $earliest_start->setTimezone($this->time_zone)->format('Y-m-d H:i:s');
        } else {
            $this->earliest_start = null;
        }
        return $this;
    }

    public function getLatestEnd(): ?DateTimeImmutable
    {
        if ($this->latest_end !== null) {
            return new DateTimeImmutable($this->latest_end, $this->time_zone);
        }
        return null;
    }

    public function setLatestEnd(?DateTimeImmutable $latest_end): Writer
    {
        if ($latest_end !== null) {
            $this->latest_end = $latest_end->setTimezone($this->time_zone)->format('Y-m-d H:i:s');
        } else {
            $this->latest_end = null;
        }
        return $this;
    }

    public function getTimeLimitMinutes(): ?int
    {
        return $this->time_limit_minutes;
    }

    public function setTimeLimitMinutes(?int $time_limit_minutes): Writer
    {
        $this->time_limit_minutes = $time_limit_minutes;
        return $this;
    }

    public function getWorkingStart(): ?DateTimeImmutable
    {
        if ($this->working_start !== null) {
            return new DateTimeImmutable($this->working_start, $this->time_zone);
        }
        return null;
    }

    public function setWorkingStart(?DateTimeImmutable $working_start): Writer
    {
        if ($working_start !== null) {
            $this->working_start = $working_start->setTimezone($this->time_zone)->format('Y-m-d H:i:s');
        } else {
            $this->working_start = null;
        }
        return $this;
    }

}