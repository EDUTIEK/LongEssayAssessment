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

use Edutiek\AssessmentService\Assessment\Manager\FullService as Manager;

/**
 * Repository object
 */
class ilObjLongEssayAssessment extends ilObjectPlugin
{
    /** @var ilLongEssayAssessmentPlugin */
    protected ?ilPlugin $plugin = null;
    private Manager $manager;

    public function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);

        $this->manager = $this->plugin->dic()
            ->assessment($this->id, $this->ref_id, $this->user->getId())
            ->manager();
    }

    final public function initType(): void
    {
        $this->setType(ilLongEssayAssessmentPlugin::ID);
    }

    protected function doCreate(bool $clone_mode = false): void
    {
        $this->manager->create();
    }

    protected function doDelete(): void
    {
        $this->manager->delete();
    }

    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null): void
    {
        $this->manager->clone($new_obj->getId());
    }
}
