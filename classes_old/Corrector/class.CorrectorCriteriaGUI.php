<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\RatingCriterion;
use ILIAS\Plugin\LongEssayAssessment\Task\CriteriaGUI;

/**
 * Cretiera page for correctors
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorCriteriaGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorCriteriaGUI extends CriteriaGUI
{
    protected ?Corrector $corrector;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->object->getId());
    }

    public function executeCommand()
    {
        if($this->corrector === null) {
            $this->tpl->setContent('unknown corrector ');
            return;
        }
        parent::executeCommand();
    }


    protected function getRatingCriterionFromContext(): array
    {
        return $this->localDI->getObjectRepo()->getRatingCriteriaByObjectId($this->object->getId(), $this->getCorrectorIdFromContext());
    }

    protected function getRatingCriterionModelFromContext(): RatingCriterion
    {
        return RatingCriterion::model()->setObjectId($this->object->getId())->setCorrectorId($this->getCorrectorIdFromContext());
    }

    protected function getCorrectorIdFromContext(): ?int
    {
        return $this->corrector->getId();
    }

    protected function allowCopyInContext(): bool
    {
        return true;
    }
}
