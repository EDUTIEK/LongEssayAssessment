<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorAssignment extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_corrector_ass';


	/**
	 * Editor notice id
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       true
	 * @con_sequence         true
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $id;

	/**
	 * The writer id
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $writer_id;

	/**
	 * The Corrector Id
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $corrector_id;

	/**
	 * @var int
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $position = 0;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return CorrectorAssignment
	 */
	public function setId(int $id): CorrectorAssignment
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getWriterId(): int
	{
		return $this->writer_id;
	}

	/**
	 * @param int $writer_id
	 * @return CorrectorAssignment
	 */
	public function setWriterId(int $writer_id): CorrectorAssignment
	{
		$this->writer_id = $writer_id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getCorrectorId(): int
	{
		return $this->corrector_id;
	}

	/**
	 * @param int $corrector_id
	 * @return CorrectorAssignment
	 */
	public function setCorrectorId(int $corrector_id): CorrectorAssignment
	{
		$this->corrector_id = $corrector_id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPosition(): int
	{
		return $this->position;
	}

	/**
	 * @param int $position
	 * @return CorrectorAssignment
	 */
	public function setPosition(int $position): CorrectorAssignment
	{
		$this->position = $position;
		return $this;
	}
}