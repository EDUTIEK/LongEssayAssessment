<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\Assessment\TaskInterfaces\Manager as TaskManagerService;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo;
use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use Edutiek\AssessmentService\System\Transform\FullService as TransformService;
use Edutiek\AssessmentService\Task\Data\Resource;
use Edutiek\AssessmentService\Task\Data\ResourceAvailability;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use Edutiek\AssessmentService\Task\Data\Settings;
use Edutiek\AssessmentService\Task\Resource\FullService as ResourceService;
use Edutiek\AssessmentService\Task\Settings\FullService as SettingsService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\Provider\ToolProvider;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ilLongEssayAssessmentUploadHandlerGUI;

/**
 * Settings GUI for task instructions
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\InstructionSettingsGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_calls ILIAS\Plugin\LongEssayAssessment\Settings\InstructionSettingsGUI: ilLongEssayAssessmentUploadHandlerGUI
 */
class InstructionSettingsGUI extends BaseGUI
{
    private SettingsService $settings_service;
    private ResourceService $resource_service;
    private TransformService $transform_service;
    private ilLongEssayAssessmentUploadHandlerGUI $upload_handler;
    private FileStorage $file_storage;
    private Settings $settings;
    private ?Resource $resource;

    public function __construct(BaseObjectData $object) {
        parent::__construct($object);

        $this->transform_service = $this->system_api->transform();
        $this->file_storage = $this->system_api->fileStorage();
        $this->upload_handler = new ilLongEssayAssessmentUploadHandlerGUI(
            $this->file_storage,
            $this->plugin->dic()->uploadTempFile());
    }

    public function executeCommand(): void
    {
        $this->initTask();
        $this->settings_service = $this->task_api->settings($this->task_info->getId());
        $this->resource_service = $this->task_api->resource($this->task_info->getId());
        $this->settings = $this->settings_service->get();
        $this->resource = $this->resource_service->oneByType(ResourceType::INSTRUCTIONS);

        switch ($this->ctrl->getCmdClass()) {
            default:
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
        $this->add($form)->show();
    }

    private function updateSettings(array $data): void
    {
        $this->settings->setInstructions(
            $this->transform_service->trimRichText(
                $this->transform_service->cleanupRichText($data['form']['task_instructions'])));
        $this->settings_service->save($this->settings);

        $id = $data['form']['resource_file'][0] ?? null;
        if ($id !== $this->resource?->getFileId()) {
            if ($id !== null) {
                if ($this->resource === null) {
                    $this->resource = $this->resource_service->new()->setType(ResourceType::INSTRUCTIONS);
                } else {
                    $this->file_storage->deleteFile($this->resource->getFileId());
                }
                $stored = $this->file_storage->saveFile(
                    $this->upload_handler->getApiStream($id),
                    $this->upload_handler->getApiInfo($id)
                );
                $this->resource_service->save($this->resource
                    ->setFileId($stored->getId())
                    ->setTitle($stored->getFileName())
                );
            }
            elseif ($this->resource !== null) {
                $this->file_storage->deleteFile($this->resource->getFileId());
                $this->resource_service->delete($this->resource);
            }
        }

        $this->success( $this->lng->txt("settings_saved"), true);
        $this->ctrl->redirect($this, "editSettings");
    }

    private function buildForm(): Standard
    {
        $factory = $this->ui_factory->input()->field();
        $sections = [];
        $fields = [];

        $fields['task_instructions'] = $this->plugin_ui_factory->field()
            ->tinyMCE($this->plugin->txt("description"), $this->plugin->txt("task_description_info"))
            ->withValue($this->settings->getInstructions() ?? "");

        $fields['resource_file'] = $factory->file($this->upload_handler,
            $this->plugin->txt("task_instructions"),
            $this->plugin->txt("task_instructions_file_info")
        )
            ->withAcceptedMimeTypes(['application/pdf'])
            ->withValue($this->resource !== null && $this->resource->getFileId() !== null ? [$this->resource->getFileId()] : []);

        $sections["form"] = $factory->section($fields, $this->plugin->txt('tab_instructions_settings'));

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }
}
