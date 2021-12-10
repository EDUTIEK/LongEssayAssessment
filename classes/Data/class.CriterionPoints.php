<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CriterionPoints extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_crit_points';


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
    protected $rating_id;

    /**
     * The corrector comment id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $corr_comment_id;

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
     * @return CriterionPoints
     */
    public function setId(int $id): CriterionPoints
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getRatingId(): int
    {
        return $this->rating_id;
    }

    /**
     * @param int $rating_id
     * @return CriterionPoints
     */
    public function setRatingId(int $rating_id): CriterionPoints
    {
        $this->rating_id = $rating_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getCorrCommentId(): int
    {
        return $this->corr_comment_id;
    }

    /**
     * @param int $corr_comment_id
     * @return CriterionPoints
     */
    public function setCorrCommentId(int $corr_comment_id): CriterionPoints
    {
        $this->corr_comment_id = $corr_comment_id;
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
     * @return CriterionPoints
     */
    public function setPoints(int $points): CriterionPoints
    {
        $this->points = $points;
        return $this;
    }
}