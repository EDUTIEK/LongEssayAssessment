<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Object;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * Criterion that is used for rating an essay
 * See getters for details of the properties
 */
class RatingCriterion extends RecordData
{
    protected const tableName = 'xlas_rating_crit';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'object_id' => 'integer',
        'corrector_id' => 'integer',
        'title'=> 'text',
        'description' => 'text',
        'points' => 'integer'
    ];


    protected int $id = 0;
    protected int $object_id = 0;
    protected ?int $corrector_id = null;
    protected string $title = "";
    protected ?string $description = null;
    protected int $points = 0;


    public static function model()
    {
        return new self();
    }


    /**
     * The id is a sequence value
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): RatingCriterion
    {
        $this->id = $id;
        return $this;
    }

    /**
     * The object id binds this criterion to an ilias repository object
     */
    public function getObjectId(): int
    {
        return $this->object_id;
    }

    public function setObjectId(int $object_id): RatingCriterion
    {
        $this->object_id = $object_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCorrectorId(): ?int
    {
        return $this->corrector_id;
    }

    /**
     * @param int|null $corrector_id
     * @return RatingCriterion
     */
    public function setCorrectorId(?int $corrector_id): RatingCriterion
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }

    /**
     * The title should fit into a single line with half screen width
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): RatingCriterion
    {
        $this->title = $title;
        return $this;
    }

    /**
     * The description may be a longer text
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): RatingCriterion
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Maximum sum of points that can be given for this criterion
     */
    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): RatingCriterion
    {
        $this->points = $points;
        return $this;
    }
}
