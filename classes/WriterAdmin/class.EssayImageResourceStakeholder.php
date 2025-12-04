<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;

class EssayImageResourceStakeholder extends AbstractResourceStakeholder
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
        return "xlas_essay_image";
    }

    /**
     * @inheritDoc
     */
    public function getOwnerOfNewResources(): int
    {
        return $this->owner;
    }

}
