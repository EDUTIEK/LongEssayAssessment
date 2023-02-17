<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Object;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * Settings related to the whole assessment object
 * See getters for details of the properties
 */
class ObjectSettings extends RecordData
{
    const PARTICIPATION_TYPE_FIXED = 'fixed';
    const PARTICIPATION_TYPE_INSTANT = 'instant';

    protected const tableName = 'xlas_object_settings';
    protected const hasSequence = false;
    protected const keyTypes = [
        'obj_id' => 'integer',
    ];
    protected const otherTypes = [
        'online' => 'integer',
        'participation_type' => 'text'
    ];

    protected int $obj_id = 0;
    protected int $online = 0;
    protected string $participation_type = self::PARTICIPATION_TYPE_INSTANT;


    public function __construct(int $obj_id)
    {
        $this->obj_id = $obj_id;
    }

    public static function model()
    {
        return new self(0);
    }

    /**
     * The object id binds these setting to an ilias repository object
     */
    public function getObjId(): int
    {
        return $this->obj_id;
    }

    public function setObjId(int $obj_id): ObjectSettings
    {
        $this->obj_id = $obj_id;
        return $this;
    }

    /**
     * The object must be online to be visible for participants and correctors
     */
    public function isOnline()
    {
        return $this->online;
    }

    public function setOnline(string $online): ObjectSettings
    {
        $this->online = $online;
        return $this;
    }

    /**
     * Instant participation allows all user with read acces to participate
     * Fixed participation requires all participants to be added explictly
     * @see self::PARTICIPATION_TYPE_FIXED
     * @see self::PARTICIPATION_TYPE_INSTANT
     */
    public function getParticipationType(): string
    {
        return $this->participation_type;
    }

    public function setParticipationType(string $participation_type) : ObjectSettings
    {
        $this->participation_type = $participation_type;
        return $this;
    }
}