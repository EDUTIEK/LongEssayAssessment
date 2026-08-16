<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskManager as TaskManager;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskType;
use Edutiek\AssessmentService\System\Entity\FullService as EntityService;
use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use Edutiek\AssessmentService\System\Transform\FullService as TransformService;
use Edutiek\AssessmentService\Task\Data\Resource;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use Edutiek\AssessmentService\Task\Data\Settings;
use Edutiek\AssessmentService\Task\Resource\FullService as ResourceService;
use Edutiek\AssessmentService\Task\Settings\FullService as SettingsService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
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
    private EntityService $entity_service;
    private SettingsService $settings_service;
    private ResourceService $resource_service;
    private TransformService $transform_service;
    private ilLongEssayAssessmentUploadHandlerGUI $upload_handler;
    private FileStorage $file_storage;
    private Settings $settings;
    private ?Resource $resource;
    private TaskManager $task_manager;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->entity_service = $this->system_api->entity();
        $this->transform_service = $this->system_api->transform();
        $this->task_manager = $this->task_api->manager();
        $this->file_storage = $this->system_api->fileStorage();
        $this->upload_handler = new ilLongEssayAssessmentUploadHandlerGUI(
            $this->file_storage,
            $this->plugin->dic()->uploadTempFile()
        );
    }

    public function executeCommand(): void
    {
        $this->initTools(true, true, 'tab_instructions_settings');
        $this->settings_service = $this->task_api->settings($this->task_info->getId());
        $this->resource_service = $this->task_api->resource($this->task_info->getId());
        $this->settings = $this->settings_service->get();
        $this->resource = $this->resource_service->oneByType(ResourceType::INSTRUCTIONS);

        switch ($this->ctrl->getCmdClass()) {
            default:
                $cmd = $this->ctrl->getCmd('editSettings');
                switch ($cmd) {
                    case 'create':
                    case 'delete':
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
        $this->tpl->setTitle($this->object->getTitle());
        $modal = $this->getCreateModal();

        if ($this->request->getMethod() == "POST") {
            $modal = $modal->withRequest($this->request);
            $data = $modal->getData();

            $task_id = $this->task_manager->create(new TaskInfo(
                !empty($data['title']) ? $data['title'] : $this->plugin->txt('new_task'),
                TaskType::ESSAY
            ));

            $this->ctrl->setParameter($this, 'task_id', $task_id);
        }

        $this->ctrl->redirect($this, 'editSettings');
    }

    private function delete(): void
    {
        if ($this->task_manager->count() < 2) {
            $this->raisePermissionError();
        }
        $this->task_manager->delete($this->task_info->getId());
        $this->success($this->plugin->txt('task_deleted'), true);
        $this->ctrl->redirect($this, 'editSettings');

    }

    protected function editSettings(): void
    {
        $this->setToolbar();
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
        if ($this->object->getMultiTasks() && !$this->fixation_gui->isDisabled('task_admin')) {
            $this->settings->setTitle($data['form']['title']);
        }
        if (!$this->fixation_gui->isDisabled('task_instructions')) {
            $this->settings->setInstructions(
                $this->transform_service->trimRichText($data['form']['task_instructions'])
            );
        }

        $this->entity_service->secure($this->settings, Settings::class);
        $this->settings_service->save($this->settings);

        if (!$this->fixation_gui->isDisabled('resource_file')) {
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
                    $this->resource_service->save(
                        $this->resource
                            ->setFileId($stored->getId())
                            ->setTitle($stored->getFileName())
                    );
                } elseif ($this->resource !== null) {
                    $this->file_storage->deleteFile($this->resource->getFileId());
                    $this->resource_service->delete($this->resource);
                }
            }
        }

        $this->success($this->lng->txt("settings_saved"), true);
        $this->ctrl->redirect($this, "editSettings");
    }

    private function buildForm(): Standard
    {
        $factory = $this->ui_factory->input()->field();
        $sections = [];
        $fields = [];

        if ($this->object->getMultiTasks() && $this->fixation_gui->isVisible('task_admin')) {
            $fields['title'] = $factory->text($this->lng->txt("title"))
                ->withValue($this->settings->getTitle())
                ->withDisabled($this->fixation_gui->isDisabled('task_admin'));
        }

        if ($this->fixation_gui->isVisible('task_instructions')) {
            $fields['task_instructions'] = $this->plugin_ui_factory->field()
                ->tinyMCE($this->plugin->txt("task_instructions_text"), $this->plugin->txt("task_instructions_info"))
                ->withValue($this->settings->getInstructions() ?? "")
                ->withDisabled($this->fixation_gui->isDisabled('task_instructions'));
        }

        if ($this->fixation_gui->isVisible('resource_file')) {
            $fields['resource_file'] = $factory->file(
                $this->upload_handler,
                $this->plugin->txt("task_instructions_file"),
                $this->plugin->txt("task_instructions_file_info")
            )
                ->withAcceptedMimeTypes(['application/pdf'])
                ->withValue(
                    $this->resource !== null && $this->resource->getFileId() !== null ? [$this->resource->getFileId()] : []
                )
                ->withDisabled($this->fixation_gui->isDisabled('resource_file'));
        }

        $sections["form"] = $factory->section($fields, $this->plugin->txt('tab_instructions_settings'));

        return $this->ui_factory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }

    private function setToolbar(): void
    {
        if ($this->object->getMultiTasks() && !$this->fixation_gui->isDisabled('task_admin')) {
            $this->add($modal = $this->getCreateModal());

            $this->toolbar->addComponent($this->ui_factory->button()->primary(
                $this->plugin->txt("create_task"),
                '#'
            )->withOnClick($modal->getShowSignal()));

            $this->add($modal = $this->ui_factory->modal()->interruptive(
                $this->plugin->txt("delete_task"),
                $this->plugin->txt("delete_task_confirmation"),
                $this->ctrl->getLinkTarget($this, "delete")
            )->withActionButtonLabel($this->plugin->txt("delete_task")));

            $this->toolbar->addComponent($this->ui_factory->button()->standard(
                $this->plugin->txt("delete_task"),
                "#"
            )->withOnClick($modal->getShowSignal())->withUnavailableAction(
                $this->task_manager->count() < 2
            ));
        }
    }

    private function getCreateModal()
    {
        return $this->ui_factory->modal()->roundtrip(
            $this->plugin->txt("create_task"),
            null,
            [
                'title' => $this->ui_factory->input()->field()->text($this->lng->txt("title")),
            ],
            $this->ctrl->getFormAction($this, 'create')
        )->withSubmitLabel($this->plugin->txt("create_task"));
    }
}
