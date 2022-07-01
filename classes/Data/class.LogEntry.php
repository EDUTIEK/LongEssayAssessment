<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class LogEntry extends ActivePluginRecord
{
    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_log_entry';

    /**
     * Writer notice id
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
     * The task_id
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
	 * timestamp (datetime)
	 *
	 * @var string|null
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        timestamp
	 */
	protected $timestamp = null;


	/**
	 * @var string
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        text
	 * @con_length           255
	 */
	protected $category = null;


	/**
	 * @var string
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        text
	 * @con_length           255
	 */
	protected string $title = "";


	/**
     * Entry Text (richtext)
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $entry = null;

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

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return LogEntry
	 */
	public function setTitle(string $title): LogEntry
	{
		$this->title = $title;
		return $this;
	}
}