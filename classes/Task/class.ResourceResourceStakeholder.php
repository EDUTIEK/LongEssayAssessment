<?php

namespace ILIAS\Plugin\LongEssayTask\Task;

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
        //TODO: Check Repository whether Task or Resource is in DB

        return parent::resourceHasBeenDeleted($identification);
    }

}