<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectionSettings extends ActivePluginRecord
{
    public const ASSIGN_MODE_RANDOM_EQUAL = 'random_equal';

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_corr_setting';


    /**
     * The task id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $task_id;

    /**
     * required correctors
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $required_correctors = 2;

    /**
     * mutual visibility
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $mutual_visibility = 1;

    /**
     * multi color highlight
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $multi_color_highlight = 1;

    /**
     * max points
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $max_points = 0;


    /**
     * max distance of points for automated finalisation
     *
     * @var float
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        float
     */
    protected $max_auto_distance = 0;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           50
     */
    protected $assign_mode = self::ASSIGN_MODE_RANDOM_EQUAL;



    /**
     * require a stitch decision when the distance of points is higher than allowed
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $stitch_when_distance = 1;


    /**
     * require a stitch decision when the average points are not integer
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $stitch_when_decimals = 1;


    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    /**
     * @param int $task_id
     * @return CorrectionSettings
     */
    public function setTaskId(int $task_id): CorrectionSettings
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getRequiredCorrectors(): int
    {
        return $this->required_correctors;
    }

    /**
     * @param int $required_correctors
     * @return CorrectionSettings
     */
    public function setRequiredCorrectors(int $required_correctors): CorrectionSettings
    {
        $this->required_correctors = $required_correctors;
        return $this;
    }

    /**
     * @return int
     */
    public function getMutualVisibility(): int
    {
        return $this->mutual_visibility;
    }

    /**
     * @param int $mutual_visibility
     * @return CorrectionSettings
     */
    public function setMutualVisibility(int $mutual_visibility): CorrectionSettings
    {
        $this->mutual_visibility = $mutual_visibility;
        return $this;
    }

    /**
     * @return int
     */
    public function getMultiColorHighlight(): int
    {
        return $this->multi_color_highlight;
    }

    /**
     * @param int $multi_color_highlight
     * @return CorrectionSettings
     */
    public function setMultiColorHighlight(int $multi_color_highlight): CorrectionSettings
    {
        $this->multi_color_highlight = $multi_color_highlight;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxPoints(): int
    {
        return $this->max_points;
    }

    /**
     * @param int $max_points
     * @return CorrectionSettings
     */
    public function setMaxPoints(int $max_points): CorrectionSettings
    {
        $this->max_points = $max_points;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxAutoDistance(): float
    {
        return $this->max_auto_distance;
    }

    /**
     * @param int $max_auto_distance
     * @return CorrectionSettings
     */
    public function setMaxAutoDistance(float $max_auto_distance): CorrectionSettings
    {
        $this->max_auto_distance = $max_auto_distance;
        return $this;
    }

    /**
     * @return string
     */
    public function getAssignMode(): string
    {
        return $this->assign_mode;
    }

    /**
     * @param string $assign_mode
     * @return CorrectionSettings
     */
    public function setAssignMode(string $assign_mode): CorrectionSettings
    {
        $this->assign_mode = $assign_mode;
        return $this;
    }

    /**
     * @return int
     */
    public function getStitchWhenDistance(): bool
    {
        return (bool) $this->stitch_when_distance;
    }

    /**
     * @param int $stitch_when_distance
     */
    public function setStitchWhenDistance(bool $stitch_when_distance): void
    {
        $this->stitch_when_distance = (int) $stitch_when_distance;
    }

    /**
     * @return int
     */
    public function getStitchWhenDecimals(): bool
    {
        return (bool) $this->stitch_when_decimals;
    }

    /**
     * @param int $stitch_when_decimals
     */
    public function setStitchWhenDecimals(bool $stitch_when_decimals): void
    {
        $this->stitch_when_decimals = (int) $stitch_when_decimals;
    }

}