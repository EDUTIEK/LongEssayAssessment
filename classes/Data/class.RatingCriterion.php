<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class RatingCriterion extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlas_rating_crit';


    /**
     * ID
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
     * The object id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $object_id;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           255
     */
    protected $title = "";

    /**
     * Description Text (richtext)
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $description = null;

    /**
     * @var int
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $points = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RatingCriterion
     */
    public function setId(int $id): RatingCriterion
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->object_id;
    }

    /**
     * @param int $object_id
     * @return RatingCriterion
     */
    public function setObjectId(int $object_id): RatingCriterion
    {
        $this->object_id = $object_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return RatingCriterion
     */
    public function setTitle(string $title): RatingCriterion
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return RatingCriterion
     */
    public function setDescription(?string $description): RatingCriterion
    {
        $this->description = $description;
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
     * @return RatingCriterion
     */
    public function setPoints(int $points): RatingCriterion
    {
        $this->points = $points;
        return $this;
    }
}