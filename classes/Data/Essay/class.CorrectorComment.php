<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorComment extends RecordData
{
    const RATING_CARDINAL = 'cardinal';
    const RAITNG_EXCELLENT = 'excellent';

	protected const tableName = 'xlas_corrector_comment';
	protected const hasSequence = true;
	protected const keyTypes = [
		'id' => 'integer',
	];
	protected const otherTypes = [
		'essay_id' => 'integer',
		'corrector_id' => 'integer',
		'comment' => 'text',
		'start_position' => 'integer',
		'end_position' => 'integer',
        'parent_number' => 'integer',
        'rating' => 'text',
        'points' => 'integer',
        'mark' => 'text',
	];

    protected int $id = 0;
    protected int $essay_id = 0;
    protected int $corrector_id = 0;
    protected ?string $comment = null;
    protected int $start_position = 0;
    protected int $end_position = 0;
    protected int $parent_number = 0;
    protected string $rating = '';
    protected int $points = 0;
    protected ?string $mark = null;

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
     * @return CorrectorComment
     */
    public function setId(int $id): CorrectorComment
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
     * @return CorrectorComment
     */
    public function setEssayId(int $essay_id): CorrectorComment
    {
        $this->essay_id = $essay_id;
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
     * @return CorrectorComment
     */
    public function setComment(?string $comment): CorrectorComment
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
     * @return CorrectorComment
     */
    public function setStartPosition(int $start_position): CorrectorComment
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
     * @return CorrectorComment
     */
    public function setEndPosition(int $end_position): CorrectorComment
    {
        $this->end_position = $end_position;
        return $this;
    }

    /**
     * @return string
     */
    public function getRating(): string
    {
        return $this->rating;
    }

    /**
     * @param string $rating
     * @return CorrectorComment
     */
    public function setRating(string $rating): CorrectorComment
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * @return int
     */
    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }

    /**
     * @param int $corrector_id
     * @return CorrectorComment
     */
    public function setCorrectorId(int $corrector_id): CorrectorComment
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getParentNumber(): int
    {
        return $this->parent_number;
    }

    /**
     * @param int $parent_number
     * @return CorrectorComment
     */
    public function setParentNumber(int $parent_number): CorrectorComment
    {
        $this->parent_number = $parent_number;
        return $this;
    }

    /**
     * @return int
     */
    public function getPoints(): int
    {
        return $this->points;
    }

    /**
     * @param int $points
     */
    public function setPoints(int $points): void
    {
        $this->points = $points;
    }

    /**
     * @return string|null
     */
    public function getMark(): ?string
    {
        return $this->mark;
    }

    /**
     * @param string|null $mark
     */
    public function setMark(?string $mark): void
    {
        $this->mark = $mark;
    }
}
