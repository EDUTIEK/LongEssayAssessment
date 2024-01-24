<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Alert extends RecordData
{

    protected const tableName = 'xlas_alert';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'task_id'=> 'integer',
        'writer_id' => 'integer',
        'title' => 'text',
        'message' => 'text',
        'shown_from' => 'datetime',
        'shown_until' => 'datetime'
    ];

    protected int $id = 0;
    protected int $task_id = 0;
    protected ?int $writer_id = 0;
    protected ?string $title = null;
    protected ?string $message = null;
    protected ?string $shown_from = null;
    protected ?string $shown_until = null;

    public static function model()
    {
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
