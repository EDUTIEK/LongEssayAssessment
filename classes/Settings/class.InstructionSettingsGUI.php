<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\Manager as TaskManagerService;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo;
use ILIAS\Plugin\LongEssayAssessment\Provider\ToolProvider;

/**
 * Settings GUI for task instructions
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\InstructionSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class InstructionSettingsGUI extends BaseGUI
{
    private TaskManagerService $manager_service;
    private ?TaskInfo $task_info;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->manager_service = $this->task_api->manager();
    }

    public function executeCommand(): void
    {
        if ($this->object->getMultiTasks()) {
            $this->dic->globalScreen()->tool()->context()->current()->getAdditionalData()->add(ToolProvider::NAME, true);
        }

        $cmd = $this->ctrl->getCmd('editSettings');
        switch ($cmd) {
            case "editSettings":
                $this->$cmd();
                break;
            case 'create':
                $this->create();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    protected function editSettings()
    {
        $this->task_info = (
            $this->get->has('task_id') ?
                $this->manager_service->one($this->get->integer('task_id')) :
                $this->manager_service->first()
        );
        if ($this->task_info === null) {
            $this->setContent('wrong parameter task_id');
            return;
        }

        $this->ctrl->setParameter($this, 'task_id', $this->task_info->getId());

        $this->setContent(ILIAS_HTTP_PATH . " task_id: {$this->task_info->getId()}");
    }

    private function create(): void
    {
        $this->setContent('Creating new one');
    }
}
