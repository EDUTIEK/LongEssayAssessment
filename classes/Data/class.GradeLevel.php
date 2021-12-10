<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class GradeLevel extends ActivePluginRecord
{
    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_grade_level';


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
     * @var int
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $min_points = 0;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           255
     */
    protected $grade = "";

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
     * @return int
     */
    public function getMinPoints(): int
    {
        return $this->min_points;
    }

    /**
     * @param int $min_points
     * @return GradeLevel
     */
    public function setMinPoints(int $min_points): GradeLevel
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


}
