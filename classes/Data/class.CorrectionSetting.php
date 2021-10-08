<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectionSetting extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_correction_setting';


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
    protected int $task_id;

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
	protected int $required_correctors;

	/**
	 * mutual visibility
	 *
	 * @var string
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       true
	 * @con_fieldtype        text
	 * @con_length           50
	 */
	protected string $mutual_visibility;

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
	protected int $multi_color_highlight;

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
	protected int $max_points;

	/**
	 * @return int
	 */
	public function getTaskId(): int
	{
		return $this->task_id;
	}

	/**
	 * @param int $task_id
	 * @return CorrectionSetting
	 */
	public function setTaskId(int $task_id): CorrectionSetting
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
	 * @return CorrectionSetting
	 */
	public function setRequiredCorrectors(int $required_correctors): CorrectionSetting
	{
		$this->required_correctors = $required_correctors;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMutualVisibility(): string
	{
		return $this->mutual_visibility;
	}

	/**
	 * @param string $mutual_visibility
	 * @return CorrectionSetting
	 */
	public function setMutualVisibility(string $mutual_visibility): CorrectionSetting
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
	 * @return CorrectionSetting
	 */
	public function setMultiColorHighlight(int $multi_color_highlight): CorrectionSetting
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
	 * @return CorrectionSetting
	 */
	public function setMaxPoints(int $max_points): CorrectionSetting
	{
		$this->max_points = $max_points;
		return $this;
	}

}