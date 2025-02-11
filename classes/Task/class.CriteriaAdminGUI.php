<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\RatingCriterion;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\CriteriaAdminGUI: ilObjLongEssayAssessmentGUI
 */
class CriteriaAdminGUI extends CriteriaGUI
{

    protected function getRatingCriteriaFromContext(): array
    {
        return $this->object_repo->getRatingCriteriaByObjectId($this->object->getId());
    }

    protected function getRatingCriterionModelFromContext(): RatingCriterion
    {
        return RatingCriterion::model()->setObjectId($this->object->getId())->setCorrectorId(null);
    }

    protected function getCorrectorIdFromContext(): ?int
    {
        return null;
    }

    protected function allowChangeInContext(): bool
    {
        switch ($this->settings->getCriteriaMode()) {
            case CorrectionSettings::CRITERIA_MODE_NONE:
                return false;
            case CorrectionSettings::CRITERIA_MODE_FIXED:
            case CorrectionSettings::CRITERIA_MODE_CORRECTOR:
                return !$this->hasAuthorizedCorrections();
        }
        return false;
    }

    protected function allowSettingsInContext(): bool
    {
        return !$this->hasAuthorizedCorrections();
    }

    protected function allowShareInContext(): bool
    {
        return false;
    }
}
