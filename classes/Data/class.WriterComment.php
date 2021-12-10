<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class WriterComment extends ActivePluginRecord
{
    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_writer_comment';

    /**
     * Writer comment id
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
     * Comment (richtext)
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $comment = null;

    /**
     * @var int
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $start_position = 0;

    /**
     * @var int
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $end_position = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return WriterComment
     */
    public function setId(int $id): WriterComment
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
     * @return WriterComment
     */
    public function setTaskId(int $task_id): WriterComment
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     * @return WriterComment
     */
    public function setComment(?string $comment): WriterComment
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return int
     */
    public function getStartPosition(): int
    {
        return $this->start_position;
    }

    /**
     * @param int $start_position
     * @return WriterComment
     */
    public function setStartPosition(int $start_position): WriterComment
    {
        $this->start_position = $start_position;
        return $this;
    }

    /**
     * @return int
     */
    public function getEndPosition(): int
    {
        return $this->end_position;
    }

    /**
     * @param int $end_position
     * @return WriterComment
     */
    public function setEndPosition(int $end_position): WriterComment
    {
        $this->end_position = $end_position;
        return $this;
    }


}