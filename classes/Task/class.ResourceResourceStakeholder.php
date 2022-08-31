<?php

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;

class ResourceResourceStakeholder extends \ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder
{
    /**
     * @var int
     */
    protected $owner = 6;

    public function __construct($owner = 6)
    {
        $this->owner = $owner;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return "xlet";
    }

    /**
     * @inheritDoc
     */
    public function getOwnerOfNewResources(): int
    {
        return $this->owner;
    }

    public function resourceHasBeenDeleted(ResourceIdentification $identification): bool
    {
		$let_dic = LongEssayTaskDI::getInstance();
		$task_repo = $let_dic->getTaskRepo();
		$resource = $task_repo->getResourceByFileId((string) $identification);

		if($resource === null){
			return true;
		}
		$task = $task_repo->getTaskSettingsById($resource->getTaskId());

		if($task === null){
			return true;
		}

        return false;
    }

}