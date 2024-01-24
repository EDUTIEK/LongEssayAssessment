<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class WriterHistory extends RecordData
{
    protected const tableName = 'xlas_writer_history';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'essay_id' => 'integer',
        'timestamp' => 'timestamp',
        'content' => 'text',
        'is_delta' => 'integer',
        'hash_before' => 'text',
        'hash_after' => 'text'
    ];

    protected int $id = 0;
    protected int $essay_id = 0;
    protected ?string $timestamp = null;
    protected ?string $content = null;
    protected int $is_delta = 0;
    protected ?string $hash_before = null;
    protected ?string $hash_after = null;

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
     * @return WriterHistory
     */
    public function setId(int $id): WriterHistory
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getEssayId(): int
    {
        return $this->essay_id;
    }

    /**
     * @param int $essay_id
     * @return WriterHistory
     */
    public function setEssayId(int $essay_id): WriterHistory
    {
        $this->essay_id = $essay_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }

    /**
     * @param string|null $timestamp
     * @return WriterHistory
     */
    public function setTimestamp(?string $timestamp): WriterHistory
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     * @return WriterHistory
     */
    public function setContent(?string $content): WriterHistory
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return bool
     */
    public function isIsDelta(): bool
    {
        return (bool) $this->is_delta;
    }

    /**
     * @param bool $is_delta
     * @return WriterHistory
     */
    public function setIsDelta(bool $is_delta): WriterHistory
    {
        $this->is_delta = (int) $is_delta;
        return $this;
    }

    /**
     * @return string
     */
    public function getHashBefore(): string
    {
        return $this->hash_before;
    }

    /**
     * @param string $hash_before
     * @return WriterHistory
     */
    public function setHashBefore(string $hash_before): WriterHistory
    {
        $this->hash_before = $hash_before;
        return $this;
    }

    /**
     * @return string
     */
    public function getHashAfter(): string
    {
        return $this->hash_after;
    }

    /**
     * @param string $hash_after
     * @return WriterHistory
     */
    public function setHashAfter(string $hash_after): WriterHistory
    {
        $this->hash_after = $hash_after;
        return $this;
    }

}
