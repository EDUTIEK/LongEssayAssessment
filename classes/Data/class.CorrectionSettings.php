<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectionSettings extends ActivePluginRecord
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
	 * @return string
	 */
	public function getMutualVisibility(): string
	{
		return $this->mutual_visibility;
	}

	/**
	 * @param string $mutual_visibility
	 * @return CorrectionSettings
	 */
	public function setMutualVisibility(string $mutual_visibility): CorrectionSettings
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

}