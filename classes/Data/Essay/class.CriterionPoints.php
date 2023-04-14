<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;


use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CriterionPoints extends RecordData
{
	protected const tableName = 'xlas_crit_points';
	protected const hasSequence = true;
	protected const keyTypes = [
		'id' => 'integer',
	];
	protected const otherTypes = [
		'rating_id' => 'integer',
		'corr_comment_id' => 'integer',
		'points' => 'integer'
	];

    protected int $id = 0;
    protected int $rating_id = 0;
    protected int $corr_comment_id = 0;
    protected int $points = 0;

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