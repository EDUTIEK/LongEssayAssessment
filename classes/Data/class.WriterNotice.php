<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class WriterNotice extends ActivePluginRecord
{
    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_writer_notice';

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
	 * Recipient
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       false
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected $writer_id;

	/**
	 * @var string
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        text
	 * @con_length           255
	 */
	protected string $title = "";

    /**
     * Notice Text (richtext)
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $notice_text = null;

    /**
     * Created (datetime)
     *
     * @var string|null
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $created = null;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return WriterNotice
     */
    public function setId(int $id): WriterNotice
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
     * @return WriterNotice
     */
    public function setTaskId(int $task_id): WriterNotice
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
     * @return WriterNotice
     */
    public function setNoticeText(?string $notice_text): WriterNotice
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
     * @return WriterNotice
     */
    public function setCreated(?string $created): WriterNotice
    {
        $this->created = $created;
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
	 * @return WriterNotice
	 */
	public function setTitle(string $title): WriterNotice
	{
		$this->title = $title;
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
	 * @return WriterNotice
	 */
	public function setWriterId(int $writer_id): WriterNotice
	{
		$this->writer_id = $writer_id;
		return $this;
	}
}