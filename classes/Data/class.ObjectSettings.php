<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data;

/**
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class ObjectSettings extends ActivePluginRecord
{
    const PARTICIPATION_TYPE_FIXED = 'fixed';
    const PARTICIPATION_TYPE_INSTANT = 'instant';

    /**
     * @var string
     */
    protected $connector_container_name = 'xlas_object_settings';


    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $obj_id = 0;


    /**
     * @var bool
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $online = 0;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           10
     */
    protected $participation_type = self::PARTICIPATION_TYPE_INSTANT;

    /**
     * @return int
     */
    public function getObjId(): int
    {
        return $this->obj_id;
    }

    /**
     * @param int $obj_id
     * @return ObjectSettings
     */
    public function setObjId(int $obj_id): ObjectSettings
    {
        $this->obj_id = $obj_id;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOnline()
    {
        return $this->online;
    }

    /**
     * @param bool $online
     * @return ObjectSettings
     */
    public function setOnline($online)
    {
        $this->online = $online;
        return $this;
    }

    /**
     * @return string
     */
    public function getParticipationType(): string
    {
        return $this->participation_type;
    }

    /**
     * @param string $participation_type
     * @return ObjectSettings
     */
    public function setParticipationType(string $participation_type): ObjectSettings
    {
        $this->participation_type = $participation_type;
        return $this;
    }
}