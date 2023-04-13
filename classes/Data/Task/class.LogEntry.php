<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class LogEntry extends RecordData
{
	const CATEGORY_AUTHORIZE = "authorize";
	const CATEGORY_NOTE = "note";
	const CATEGORY_EXTENSION = "extension";
	const CATEGORY_EXCLUSION = "exclusion";

	protected const tableName = 'xlas_log_entry';
	protected const hasSequence = true;
	protected const keyTypes = [
		'id' => 'integer',
	];
	protected const otherTypes = [
		'task_id'=> 'integer',
		'timestamp' => 'datetime',
		'category' => 'text',
		'entry' => 'text'
	];

    protected int $id = 0;
    protected int $task_id = 0;
	protected ?string $timestamp = null;
	protected string $category = self::CATEGORY_NOTE;
    protected ?string $entry = null;

	public static function model() {
		return new self();
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
	 * @return LogEntry
	 */
	public function setId(int $id): LogEntry
	{
		$this->id = $id;
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
	 * @return LogEntry
	 */
	public function setTaskId(int $task_id): LogEntry
	{
		$this->task_id = $task_id;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getTimestamp(): ?string
	{
		return $this->timestamp;
	}

	/**
	 * @param string|null $timestamp
	 * @return LogEntry
	 */
	public function setTimestamp(?string $timestamp): LogEntry
	{
		$this->timestamp = $timestamp;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCategory(): ?string
	{
		return $this->category;
	}

	/**
	 * @param string $category
	 * @return LogEntry
	 */
	public function setCategory(?string $category): LogEntry
	{
		$this->category = $category;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getEntry(): ?string
	{
		return $this->entry;
	}

	/**
	 * @param string|null $entry
	 * @return LogEntry
	 */
	public function setEntry(?string $entry): LogEntry
	{
		$this->entry = $entry;
		return $this;
	}
}