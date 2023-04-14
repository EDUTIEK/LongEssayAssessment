<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Corrector;


use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * CorrectorAssignment
 *
 * Index (writer_id, corrector_id), corrector_id
 *
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorAssignment extends RecordData
{
	protected const tableName = 'xlas_corrector_ass';
	protected const hasSequence = true;
	protected const keyTypes = [
		'id' => 'integer',
	];
	protected const otherTypes = [
		'writer_id' => 'integer',
		'corrector_id' => 'integer',
		'position' => 'integer'
	];

    protected int $id = 0;
    protected int $writer_id = 0;
    protected int $corrector_id = 0;
    protected int $position = 0;

	public static function model(): CorrectorAssignment
	{
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