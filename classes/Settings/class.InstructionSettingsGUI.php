<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use Edutiek\AssessmentService\Task\Resource\FullService as ResourceService;
use Edutiek\AssessmentService\Task\Settings\FullService as SettingsService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\Manager as TaskManagerService;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo;
use ILIAS\Plugin\LongEssayAssessment\Provider\ToolProvider;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ilLongEssayAssessmentUploadHandlerGUI;

/**
 * Settings GUI for task instructions
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\InstructionSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class InstructionSettingsGUI extends BaseGUI
{
    private TaskManagerService $manager_service;
    private ?TaskInfo $task_info;
    private SettingsService $settings_service;
    private ResourceService $resource_service;
    private ilLongEssayAssessmentUploadHandlerGUI $upload_handler;
    private FileStorage $file_storage;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->upload_handler = new ilLongEssayAssessmentUploadHandlerGUI(
            $this->plugin->dic()->uploadTempFile());
    }

    public function executeCommand(): void
    {
        $this->initTask();
        $this->settings_service = $this->task_api->settings($this->task_info->getId());
        $this->resource_service = $this->task_api->resource($this->task_info->getId());
        $this->file_storage = $this->system_api->fileStorage();

        $cmd = $this->ctrl->getCmd('editSettings');
        switch ($cmd) {
            case 'create':
            case "editSettings":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    private function initTask()
    {
        $this->manager_service = $this->task_api->manager();

        $this->task_info = (
        $this->get->has('task_id') ?
            $this->manager_service->one($this->get->integer('task_id')) :
            $this->manager_service->first()
        );
        if ($this->task_info === null) {
            $this->tpl->setContent('wrong parameter task_id');
            return;
        }

        $this->tpl->setTitle($this->object->getTitle() . ' | '. $this->task_info->getTitle());
        $this->ctrl->setParameter($this, 'task_id', $this->task_info->getId());

        if ($this->object->getMultiTasks()) {
            $this->dic->globalScreen()->tool()->context()->current()->getAdditionalData()->add(ToolProvider::NAME, true);
        }
    }

    private function create(): void
    {
        $this->tpl->setContent('Creating new one');
    }

    protected function editSettings()
    {
        $form = $this->buildForm();
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
            $result = $form->getInputGroup()->getContent();

            if ($result->isOK()) {
                $this->updateSettings($data);
            }
        }
        $this->add($form);
        $this->show();


       //$this->tpl->setContent(" task_id: {$this->task_info->getId()}");
    }

    private function updateSettings(array $data): void
    {

    }

    private function buildForm(): Standard
    {
        $settings = $this->settings_service->get();
        $resource = $this->resource_service->oneByType(ResourceType::INSTRUCTIONS);
        $this->upload_handler->setFileInfo($this->file_storage->getFileInfo($resource->getFileId() ?? ''));

        $factory = $this->ui_factory->input()->field();
        $sections = [];
        $fields = [];

        $fields['task_instructions'] = $this->plugin_ui_factory->field()
            ->tinyMCE($this->plugin->txt("task_description"), $this->plugin->txt("task_description_info"))
            ->withValue($settings->getInstructions() ?? "");

        $fields['resource_file'] = $factory->file($this->upload_handler,
            $this->plugin->txt("task_instructions"),
            $this->plugin->txt("task_instructions_file_info")
            . "<br>" . $this->plugin_ui_service->getMaxFileSizeString()
        )
            ->withAcceptedMimeTypes(['application/pdf'])
            ->withValue($resource !== null && $resource->getFileId() !== null ? [$resource->getFileId()] : []);

        $sections["form"] = $factory->section($fields, $this->plugin->txt('tab_instructions_settings'));

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }
}
