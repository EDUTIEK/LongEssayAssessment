<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fred Neumann <neumann@ilias.de>
 */
class WriterNotice extends RecordData
{
    protected const tableName = 'xlas_writer_notice';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'essay_id' => 'integer',
        'note_no' => 'integer',
        'note_text' => 'text',
        'last_change' => 'datetime',
    ];


    protected int $id = 0;
    protected int $essay_id = 0;
    protected int $note_no = 0;
    protected ?string $note_text = null;
    protected ?string $last_change = null;

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
    public function getEssayId(): int
    {
        return $this->essay_id;
    }

    /**
     * @param int $essay_id
     * @return WriterNotice
     */
    public function setEssayId(int $essay_id): WriterNotice
    {
        $this->essay_id = $essay_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getNoteNo(): int
    {
        return $this->note_no;
    }

    /**
     * @param int $note_no
     */
    public function setNoteNo(int $note_no): WriterNotice
    {
        $this->note_no = $note_no;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNoteText(): ?string
    {
        return $this->note_text;
    }

    /**
     * @param string|null $note_text
     */
    public function setNoteText(?string $note_text): WriterNotice
    {
        $this->note_text = $note_text;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastChange(): ?string
    {
        return $this->last_change;
    }

    /**
     * @param string|null $last_change
     */
    public function setLastChange(?string $last_change): WriterNotice
    {
        $this->last_change = $last_change;
        return $this;
    }
}
