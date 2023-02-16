<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Object;


use ILIAS\Plugin\LongEssayAssessment\Data\ActivePluginRecord;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class GradeLevel extends ActivePluginRecord
{
    /**
     * @var string
     */
    protected $connector_container_name = 'xlas_grade_level';


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
     * @var float
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        float
     * @con_length           4
     */
    protected $min_points = 0.;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           255
     */
    protected $grade = "";

	/**
	 * @var string
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        text
	 * @con_length           255
	 */
	protected $code = "";

	/**
	 * Bestanden flag
	 *
	 * @var int
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           1
	 */
	protected $passed = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return GradeLevel
     */
    public function setId(int $id): GradeLevel
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
     * @return GradeLevel
     */
    public function setObjectId(int $object_id): GradeLevel
    {
        $this->object_id = $object_id;
        return $this;
    }

    /**
     * @return float
     */
    public function getMinPoints(): float
    {
        return $this->min_points;
    }

    /**
     * @param float $min_points
     * @return GradeLevel
     */
    public function setMinPoints(float $min_points): GradeLevel
    {
        $this->min_points = $min_points;
        return $this;
    }

    /**
     * @return string
     */
    public function getGrade(): string
    {
        return $this->grade;
    }

    /**
     * @param string $grade
     * @return GradeLevel
     */
    public function setGrade(string $grade): GradeLevel
    {
        $this->grade = $grade;
        return $this;
    }

	/**
	 * @return ?string
	 */
	public function getCode(): ?string
	{
		return $this->code;
	}

	/**
	 * @param ?string $code
	 * @return GradeLevel
	 */
	public function setCode(?string $code): GradeLevel
	{
		$this->code = $code;
		return $this;
	}

	/**
	 * @return int
	 */
	public function isPassed(): bool
	{
		return $this->passed == 1;
	}

	/**
	 * @param bool $passed
	 * @return GradeLevel
	 */
	public function setPassed(bool $passed): GradeLevel
	{
		$this->passed = $passed ? 1 : 0;
		return $this;
	}




}
