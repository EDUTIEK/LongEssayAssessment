<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data;


/**
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class ActiveRecordDummy extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlas_record_dummy';


    /**
     * The record id
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
     * The task_id currently corresponds to the obj_id of the ILIAS object
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
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param ?int $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return ?int
     */
    public function getTaskId(): ?int
    {
        return $this->task_id;
    }

    /**
     * @param ?int $task_id
     */
    public function setTaskId(?int $task_id): void
    {
        $this->task_id = $task_id;
    }


    /**
     * Dummy save
     */
    public function save()
    {
        // do nothing
    }

    /**
     * @param string $value
     * @return string
     */
    public function getStringDummy($value = ''): string
    {
        return (string)$value;
    }

    /**
     * @param bool $value
     * @return bool
     */
    public function getBoolDummy($value = false): bool
    {
        return (bool)$value;
    }

    /**
     * @param int $value
     * @return int
     */
    public function getIntegerDummy($value = 0): int
    {
        return (int)$value;
    }


    /**
     * @param mixed $value
     */
    public function setMixedDummy($value): void
    {
        // do nothing
    }
}