<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\RatingCriterion;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\CriteriaAdminGUI: ilObjLongEssayAssessmentGUI
 */
class CriteriaAdminGUI extends CriteriaGUI
{

    protected function getRatingCriterionFromContext(): array
    {
        return $this->localDI->getObjectRepo()->getRatingCriteriaByObjectId($this->object->getId());
    }

    protected function getRatingCriterionModelFromContext(): RatingCriterion
    {
        return RatingCriterion::model()->setObjectId($this->object->getId())->setCorrectorId(null);
    }

    protected function getCorrectorIdFromContext(): ?int
    {
        return null;
    }

    protected function allowCopyInContext(): bool
    {
        return false;
    }
}
