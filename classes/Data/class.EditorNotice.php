<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class EditorNotice extends ActivePluginRecord
{
    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_editor_notice';

	/**
	 * Editor notice id
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       true
	 * @con_sequence         true
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $id;

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
	protected int $task_id;

	/**
	 * Notice Text (richtext)
	 *
	 * @var null|string
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        clob
	 */
	protected ?string $notice_text = null;

	/**
	 * Created (datetime)
	 *
	 * @var string|null
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        timestamp
	 */
	protected ?string $created = null;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return EditorNotice
	 */
	public function setId(int $id): EditorNotice
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
	 * @return EditorNotice
	 */
	public function setTaskId(int $task_id): EditorNotice
	{
		$this->task_id = $task_id;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getNoticeText(): ?string
	{
		return $this->notice_text;
	}

	/**
	 * @param string|null $notice_text
	 * @return EditorNotice
	 */
	public function setNoticeText(?string $notice_text): EditorNotice
	{
		$this->notice_text = $notice_text;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getCreated(): ?string
	{
		return $this->created;
	}

	/**
	 * @param string|null $created
	 * @return EditorNotice
	 */
	public function setCreated(?string $created): EditorNotice
	{
		$this->created = $created;
		return $this;
	}
}