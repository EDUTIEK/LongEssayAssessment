<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * Writer
 *
 * Indexes: (user_id, task_id), task_id
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Writer extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_writer';

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
	protected int $id;

    /**
     * The ILIAS user id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected int $user_id;

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
	protected int $task_id;

	/**
	 * @var string
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        text
	 * @con_length           255
	 */
	protected ?string $pseudonym = null;

	/**
	 * @var integer
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $editor_font_size = 0;

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
		return $this->user_id;
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

	/**
	 * @return int
	 */
	public function getEditorFontSize(): int
	{
		return $this->editor_font_size;
	}

	/**
	 * @param int $editor_font_size
	 * @return Writer
	 */
	public function setEditorFontSize(int $editor_font_size): Writer
	{
		$this->editor_font_size = $editor_font_size;
		return $this;
	}


}