<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Corrector;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * Corrector
 * Indexes: user_id
 *
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Corrector extends RecordData
{

    protected const tableName = 'xlas_corrector';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'user_id' => 'integer',
        'task_id' => 'integer',
        'criterion_copy' => 'integer'
    ];

    /**
     * alert id
     */
    protected int $id;

    /**
     * The ILIAS user id
     *
     * @var integer
     */
    protected int $user_id;

    /**
     * The task_id currently corresponds to the obj_id of the ILIAS object
     */
    protected int $task_id;
    protected int $criterion_copy = 0;


    public function __construct(int $id)
    {
        $this->id = $id;
    }



    public static function model(): Corrector
    {
        return new self(0);
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
     * @return Corrector
     */
    public function setId(int $id): Corrector
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     * @return Corrector
     */
    public function setUserId(int $user_id): Corrector
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    /**
     * @param int $task_id
     * @return Corrector
     */
    public function setTaskId(int $task_id): Corrector
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCriterionCopyEnabled(): bool
    {
        return $this->criterion_copy === 1;
    }

    /**
     * @param bool $criterion_copy
     * @return Corrector
     */
    public function setCriterionCopyEnabled(bool $criterion_copy): Corrector
    {
        $this->criterion_copy = $criterion_copy ? 1 : 0;
        return $this;
    }
}
