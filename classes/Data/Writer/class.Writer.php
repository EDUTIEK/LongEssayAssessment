<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Writer;


use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

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
		'editor_font_size' => 'integer'
	];

    protected int $id = 0;
    protected int $user_id = 0;
	protected int $task_id = 0;
    protected $pseudonym = null;
    protected int $editor_font_size = 0;

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