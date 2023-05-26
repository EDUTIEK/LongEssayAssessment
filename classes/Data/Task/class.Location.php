<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Location extends RecordData
{
	protected const tableName = 'xlas_location';
	protected const hasSequence = true;
	protected const keyTypes = [
		'id' => 'integer',
	];
	protected const otherTypes = [
		'task_id'=> 'integer',
		'title' => 'text'
	];

	protected int $id = 0;
	protected int $task_id = 0;
	protected string $title = "";

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
	 * @return Location
	 */
	public function setId(int $id): Location
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
	 * @return Location
	 */
	public function setTaskId(int $task_id): Location
	{
		$this->task_id = $task_id;
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
	 * @return Location
	 */
	public function setTitle(string $title): Location
	{
		$this->title = $title;
		return $this;
	}
}