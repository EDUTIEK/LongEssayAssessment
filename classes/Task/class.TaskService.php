<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseService;
use ILIAS\Plugin\LongEssayTask\Data\ObjectSettings;

/**
 * Class TaskService
 * @package ILIAS\Plugin\LongEssayTask\Task
 */
class TaskService extends BaseService
{

    public function getObjectSettings()
    {
        return ObjectSettings::findOrGetInstance($this->object->getId());
    }
}