<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorPoints extends RecordData
{
    protected const tableName = 'xlas_corrector_points';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'essay_id' => 'integer',
        'corrector_id' => 'integer',
        'criterion_id' => 'integer',
        'comment_id' => 'integer',
        'points' => 'float'
    ];

    protected int $id = 0;
    protected int $essay_id = 0;
    protected int $corrector_id = 0;
    protected ?int $criterion_id = null;
    protected ?int $comment_id = null;
    protected float $points = 0;

    public static function model()
    {
        return new self();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): CorrectorPoints
    {
        $this->id = $id;
        return $this;
    }

    public function getEssayId(): int
    {
        return $this->essay_id;
    }

    public function setEssayId(int $essay_id): CorrectorPoints
    {
        $this->essay_id = $essay_id;
        return $this;
    }

    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }

    public function setCorrectorId(int $corrector_id): CorrectorPoints
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }

    public function getCriterionId(): ?int
    {
        return $this->criterion_id;
    }

    public function setCriterionId(?int $criterion_id): CorrectorPoints
    {
        $this->criterion_id = $criterion_id;
        return $this;
    }

    public function getCommentId(): ?int
    {
        return $this->comment_id;
    }

    public function setCommentId(?int $comment_id): CorrectorPoints
    {
        $this->comment_id = $comment_id;
        return $this;
    }

    public function getPoints(): float
    {
        return $this->points;
    }

    public function setPoints(float $points): CorrectorPoints
    {
        $this->points = $points;
        return $this;
    }
}
