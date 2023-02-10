<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorComment extends ActivePluginRecord
{
    const RATING_CARDINAL = 'cardinal';
    const RAITNG_FAILURE = 'failure';
    const RAITNG_EXCELLENT = 'excellent';

    /**
     * @var string
     */
    protected $connector_container_name = 'xlas_corrector_comment';

    /**
     * Editor notice id
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
     * The Essay Id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $essay_id;

    /**
     * The Corrector Id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $corrector_id;

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
     * @var int
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $points = 0;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           16
     */
    protected $rating = self::RATING_CARDINAL;

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
     * @return int
     */
    public function getPoints(): int
    {
        return $this->points;
    }

    /**
     * @param int $points
     * @return CorrectorComment
     */
    public function setPoints(int $points): CorrectorComment
    {
        $this->points = $points;
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
}