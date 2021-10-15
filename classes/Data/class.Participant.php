<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Participant extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_participant';

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
	protected ?string $pseudonyme = null;

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
	public function getUserId(): int
	{
		return $this->user_id;
	}

	/**
	 * @param int $user_id
	 * @return Participant
	 */
	public function setUserId(int $user_id): Participant
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
	 * @return Participant
	 */
	public function setTaskId(int $task_id): Participant
	{
		$this->task_id = $task_id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPseudonyme(): ?string
	{
		return $this->pseudonyme;
	}

	/**
	 * @param string $pseudonyme
	 * @return Participant
	 */
	public function setPseudonyme(?string $pseudonyme): Participant
	{
		$this->pseudonyme = $pseudonyme;
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
	 * @return Participant
	 */
	public function setEditorFontSize(int $editor_font_size): Participant
	{
		$this->editor_font_size = $editor_font_size;
		return $this;
	}


}