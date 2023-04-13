<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class WriterNotice extends RecordData
{

	protected const tableName = 'xlas_writer_notice';
	protected const hasSequence = true;
	protected const keyTypes = [
		'id' => 'integer',
	];
	protected const otherTypes = [
		'task_id'=> 'integer',
		'writer_id' => 'integer',
		'notice_text' => 'text',
		'created' => 'datetime'
	];


    protected int $id = 0;
    protected int $task_id = 0;
	protected int $writer_id = 0;
    protected ?string $notice_text = null;
    protected ?string $created = null;

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
	 * @return int
	 */
	public function getWriterId(): ?int
	{
		return $this->writer_id;
	}

	/**
	 * @param ?int $writer_id
	 * @return WriterNotice
	 */
	public function setWriterId(?int $writer_id): WriterNotice
	{
		$this->writer_id = $writer_id;
		return $this;
	}
}