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

    public function resourceHasBeenDeleted(ResourceIdentification $identification): bool
    {
        $let_dic = LongEssayAssessmentDI::getInstance();
        $essay_repo = $let_dic->getEssayRepo();
        $task_repo = $let_dic->getTaskRepo();
        
        
        $essay = $essay_repo->getEssayByPDFVersionFileID((string) $identification);

        if($essay === null) {
            return true;
        }
        $task = $task_repo->getTaskSettingsById($essay->getTaskId());

        if($task === null) {
            return true;
        }

        return false;
    }

}
