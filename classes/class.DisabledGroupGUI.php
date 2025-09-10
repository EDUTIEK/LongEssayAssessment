<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment;

use ILIAS\Plugin\LongEssayAssessment\Settings\OrgaSettingsGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;

/**
 * @ilCtrl_IsCalledBy ILIAS\Plugin\LongEssayAssessment\DisabledGroupGUI: ilObjLongEssayAssessmentGUI
 */
class DisabledGroupGUI extends BaseGUI
{
    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
    }

    public function executeCommand(): void
    {
        if ($this->ctrl->getCmd() === 'saveGroups'){
            $this->saveGroups();
        }
    }

    public function saveGroups(): void
    {
        $this->disabled_group->saveModal();
        $url = $this->get->string('return_url') ?: $this->ctrl->getLinkTargetByClass(OrgaSettingsGUI::class, 'editSettings');
        $this->ctrl->redirectToUrl($url);
    }
}
