<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Alert extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_alert';


    /**
     * alert id
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
     * The task_id currently corresponds to the obj_id of the ILIAS object
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
	protected ?int $writer_id = null;

    /**
     * title
     *
     * @var string
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           255
     */
    protected $title;

    /**
     * Message
     *
     * @var string
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        clob
     */
    protected $message;


    /**
     * Shown From
     *
     * @var string|null
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $shown_from = null;


    /**
     * Shown until
     *
     * @var string|null
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $shown_until = null;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Alert
     */
    public function setId(int $id): Alert
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
     * @return Alert
     */
    public function setTaskId(int $task_id): Alert
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return (string) $this->title;
    }

    /**
     * @param string $title
     * @return Alert
     */
    public function setTitle(string $title): Alert
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return Alert
     */
    public function setMessage(string $message): Alert
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShownFrom(): ?string
    {
        return $this->shown_from;
    }

    /**
     * @param string|null $shown_from
     * @return Alert
     */
    public function setShownFrom(?string $shown_from): Alert
    {
        $this->shown_from = $shown_from;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShownUntil(): ?string
    {
        return $this->shown_until;
    }

    /**
     * @param string|null $shown_until
     * @return Alert
     */
    public function setShownUntil(?string $shown_until): Alert
    {
        $this->shown_until = $shown_until;
        return $this;
    }

	/**
	 * @return int
	 */
	public function getWriterId(): ?int
	{
		return $this->writer_id;
	}

	/**
	 * @param int $writer_id
	 * @return Alert
	 */
	public function setWriterId(?int $writer_id): Alert
	{
		$this->writer_id = $writer_id;
		return $this;
	}
}