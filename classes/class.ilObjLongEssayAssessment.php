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

use Edutiek\AssessmentService\Assessment\Lifecycle\FullService as LifecycleService;
/**
 * Repository object
 */
class ilObjLongEssayAssessment extends ilObjectPlugin
{
    /** @var ilLongEssayAssessmentPlugin */
    protected ?ilPlugin $plugin = null;
    private LifecycleService $lifecycle;

    public function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);

        $this->lifecycle = $this->plugin->dic()->assessment($this->id, $this->ref_id)->lifecycle();
    }

    final public function initType(): void
    {
        $this->setType(ilLongEssayAssessmentPlugin::ID);
    }

    protected function doCreate(bool $clone_mode = false): void
    {
        $this->lifecycle->create();
    }

    protected function doDelete(): void
    {
        $this->lifecycle->delete();
    }

    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null): void
    {
        $this->lifecycle->clone($new_obj->getId());
    }
}
