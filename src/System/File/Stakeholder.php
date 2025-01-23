<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\File;

use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;

class Stakeholder extends AbstractResourceStakeholder
{
    public function getId(): string
    {
        return 'LongEssayAssessment';
    }

    public function getOwnerOfNewResources(): int
    {
        return SYSTEM_USER_ID;
    }
}
