<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Object;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * Grade level that is reached with an amount of points
 * See getters for details of the properties
 */
class GradeLevel extends RecordData
{
    protected const tableName = 'xlas_grade_level';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'object_id' => 'integer',
        'min_points' => 'float',
        'grade' => 'text',
        'code' => 'text',
        'passed' => 'integer'
    ];

    protected int $id = 0;
    protected int $object_id = 0;
    protected float $min_points = 0.0;
    protected string $grade = "";
	protected ?string $code = null;
	protected int $passed = 0;

    public static function model() {
        return new self();
    }

    /**
     * The id is a sequence value
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): GradeLevel
    {
        $this->id = $id;
        return $this;
    }

    /**
     * The object id binds this level to an ilias repository object
     */
    public function getObjectId(): int
    {
        return $this->object_id;
    }

    public function setObjectId(int $object_id): GradeLevel
    {
        $this->object_id = $object_id;
        return $this;
    }

    /**
     * Minimum points that must be reached to achieve at least this grade level
     */
    public function getMinPoints(): float
    {
        return $this->min_points;
    }

    public function setMinPoints(float $min_points): GradeLevel
    {
        $this->min_points = $min_points;
        return $this;
    }

    /**
     * Textual representation of the achieved level, e.g. 'sehr gut'
     */
    public function getGrade(): string
    {
        return $this->grade;
    }

    public function setGrade(string $grade): GradeLevel
    {
        $this->grade = $grade;
        return $this;
    }

	/**
	 * Code for the grade level that is used in an external system, e.g. a mark
	 */
	public function getCode(): ?string
	{
		return $this->code;
	}

	public function setCode(?string $code): GradeLevel
	{
		$this->code = $code;
		return $this;
	}

	/**
	 * The assessment is passed if this grade level is reached
	 */
	public function isPassed(): bool
	{
		return $this->passed == 1;
	}

	public function setPassed(bool $passed): GradeLevel
	{
		$this->passed = $passed ? 1 : 0;
		return $this;
	}

}
