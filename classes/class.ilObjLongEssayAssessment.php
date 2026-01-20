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
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;

/**
 * Repository object
 */
class ilObjLongEssayAssessment extends ilObjectPlugin implements BaseObjectData
{
    private static ?ilObjLongEssayAssessment $create_template = null;
    private static bool $create_multi_tasks = false;

    private Manager $manager;

    public function __construct($a_ref_id = 0)
    {
        parent::__construct($a_ref_id);
        $this->initServices();
    }

    public static function setCreateMultiTasks(bool $multi)
    {
        self::$create_multi_tasks = $multi;
    }

    public static function setCreateTemplate(ilObjLongEssayAssessment $template)
    {
        self::$create_template = $template;
    }

    public static function getCreateTemplate(): ?ilObjLongEssayAssessment
    {
        return self::$create_template;
    }

    /**
     * Get the assessment id for the assessment services
     */
    public function getAssId(): int
    {
        return $this->getId();
    }

    /**
     * Get the context id for the assessment services
     */
    public function getContextId(): int
    {
        return $this->getRefId();
    }

    public function getMultiTasks(): bool
    {
        return $this->plugin->dic()
            ->assessment($this->getAssId(), $this->user->getId())
            ->orgaSettings()->get()->getMultiTasks();
    }

    protected function initType(): void
    {
        $this->setType(ilLongEssayAssessmentPlugin::ID);
    }

    protected function doCreate(bool $clone_mode = false): void
    {
        if (self::$create_template !== null) {
            self::$create_template->cloneTo($this->getAssId());
        } elseif (!$clone_mode) {
            $this->initServices();  // now the new id is available
            $this->manager->create(self::$create_multi_tasks);
        }
    }

    protected function doDelete(): void
    {
        // re-initialisation needed to get the ids available
        $this->initServices();
        $this->manager->delete();
    }

    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null): void
    {
        $this->initServices();
        $this->cloneTo($new_obj->getId());
    }

    protected function cloneTo(int $ass_id)
    {
        $this->manager->clone($ass_id);
    }

    private function initServices()
    {
        $this->manager = $this->plugin->dic()
            ->assessment($this->getAssId(), $this->user->getId())
            ->manager();
    }
}
