<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Data\Writer;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

class WriterAnnotation  extends RecordData
{
    protected const tableName = 'xlas_writer_annotation';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'task_id' => 'integer',
        'writer_id' => 'integer',
        'resource_id' => 'integer',
        'mark_key' => 'text',
        'mark_value' => 'text',
        'parent_number' => 'integer',
        'start_position' => 'integer',
        'end_position' => 'integer',
        'comment' => 'text'
    ];

    protected int $id = 0;
    protected int $task_id = 0;
    protected int $writer_id = 0;
    protected int $resource_id = 0;
    protected string $mark_key = '';
    protected ?string $mark_value = null;
    protected int $parent_number = 0;
    protected int $start_position = 0;
    protected int $end_position = 0;
    protected ?string $comment = null;

    public static function model(): WriterAnnotation
    {
        return new self();
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function setTaskId(int $task_id): WriterAnnotation
    {
        $this->task_id = $task_id;
        return $this;
    }

    public function getWriterId(): int
    {
        return $this->writer_id;
    }

    public function setWriterId(int $writer_id): WriterAnnotation
    {
        $this->writer_id = $writer_id;
        return $this;
    }

    public function getResourceId(): int
    {
        return $this->resource_id;
    }

    public function setResourceId(int $resource_id): WriterAnnotation
    {
        $this->resource_id = $resource_id;
        return $this;
    }

    public function getMarkKey(): string
    {
        return $this->mark_key;
    }

    public function setMarkKey(string $mark_key): WriterAnnotation
    {
        $this->mark_key = $mark_key;
        return $this;
    }

    public function getMarkValue(): ?string
    {
        return $this->mark_value;
    }

    public function setMarkValue(?string $mark_value): WriterAnnotation
    {
        $this->mark_value = $mark_value;
        return $this;
    }

    public function getParentNumber(): int
    {
        return $this->parent_number;
    }

    public function setParentNumber(int $parent_number): WriterAnnotation
    {
        $this->parent_number = $parent_number;
        return $this;
    }

    public function getStartPosition(): int
    {
        return $this->start_position;
    }

    public function setStartPosition(int $start_position): WriterAnnotation
    {
        $this->start_position = $start_position;
        return $this;
    }

    public function getEndPosition(): int
    {
        return $this->end_position;
    }

    public function setEndPosition(int $end_position): WriterAnnotation
    {
        $this->end_position = $end_position;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): WriterAnnotation
    {
        $this->comment = $comment;
        return $this;
    }

}