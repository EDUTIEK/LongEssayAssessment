<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment;

use ILIAS\Plugin\LongEssayAssessment\Common\BaseGUI;

/**
 * Organisational Settings
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Assessment\OrgaSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class OrgaSettingsGUI extends BaseGUI
{
    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('editSettings');
        switch ($cmd) {
            case "editSettings":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    private function editSettings(): void
    {
        $this->tpl->setContent('edit settings');
    }
}
