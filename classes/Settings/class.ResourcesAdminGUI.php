<?php

declare(strict_types = 1);

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\System\File\Delivery as FileDelivery;
use Edutiek\AssessmentService\System\File\Disposition as Disposition;
use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use Edutiek\AssessmentService\System\Format\FullService as FormatService;
use Edutiek\AssessmentService\Task\Data\Resource;
use Edutiek\AssessmentService\Task\Data\ResourceAvailability;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use Edutiek\AssessmentService\Task\Resource\FullService as ResourceService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory as TableFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ilLongEssayAssessmentUploadHandlerGUI;

/**
 * Resources Administration
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\ResourcesAdminGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_calls ILIAS\Plugin\LongEssayAssessment\Settings\InstructionSettingsGUI: ilLongEssayAssessmentUploadHandlerGUI
 */
class ResourcesAdminGUI extends BaseGUI implements DataTableParent
{
    use ConfirmationIds;

    private TableFactory $table_factory;
    private ResourceService $resource_service;
    private FileStorage $file_storage;
    private FileDelivery $file_delivery;
    private ilLongEssayAssessmentUploadHandlerGUI $upload_handler;
    private FormatService $format_service;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->table_factory = $this->plugin_ui_factory->table();
        $this->file_storage = $this->system_api->fileStorage();
        $this->file_delivery = $this->system_api->fileDelivery();
        $this->format_service = $this->system_api->format();

        $this->upload_handler = new ilLongEssayAssessmentUploadHandlerGUI(
            $this->file_storage,
            $this->plugin->dic()->uploadTempFile());
    }

    public function executeCommand()
    {
        $this->initForTask();
        $this->resource_service = $this->task_api->resource($this->task_info->getId());

        $cmd = $this->ctrl->getCmd('showItems');
        switch ($cmd) {
            case 'showItems':
            case "editItem":
            case "downloadResourceFile":
            case "deleteItem":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    public function getTableActions() : array
    {
        return [
            $this->openAction(),
            $this->previewAction(),
            $this->downloadAction(),
            $this->editAction(),
            $this->deleteAction(),
            $this->createAction()
        ];
    }

    protected function deleteAction() : Action\Confirmation
    {
        return $this->table_factory->action()->confirmation(
            "delete",
            $this->lng->txt('delete'),
            $this->lng->txt('delete'),
            $this->plugin->txt('delete_resource_confirmation'),
            $this->ctrl->getFormAction($this, 'deleteItem'),
            fn(ResourceItem $x) => $this->buildConfirmationNames($x)
        );
    }

    protected function previewAction() : Action\Modal
    {
        return $this->table_factory->action()->modal(
            "preview",
            $this->lng->txt('preview'),
            [$this, "previewModal"],
            fn(ResourceItem $x
            ) => ($x->getType() === ResourceType::FILE || ($x->isEmbedded() && $x->getType() === ResourceType::URL)),
            Action\Type::Single
        );
    }

    protected function downloadAction() : Action\Direct
    {
        return $this->table_factory->action()->direct(
            "download",
            $this->lng->txt('download'),
            fn(ResourceItem $x) => $this->downloadResourceFile($x->getIdentifier()),
            fn(ResourceItem $x) => $x->getType() === ResourceType::FILE,
            Action\Type::Single
        );
    }

    protected function openAction() : Action\Direct
    {
        return $this->table_factory->action()->direct(
            "open",
            $this->lng->txt('open'),
            fn(ResourceItem $x) => $this->ctrl->redirectToURL($x->getUrl()),
            fn(ResourceItem $x) => $x->getType() === ResourceType::URL,
            Action\Type::Single
        );
    }

    protected function editAction() : Action\Form
    {
        return $this->table_factory->action()->form(
            "edit",
            $this->lng->txt('edit'),
            $this->plugin->txt('save'),
            [$this, "buildFields"],
            [$this, "save"],
            fn(ResourceItem $item) => true,
            Action\Type::Single
        );
    }

    protected function createAction() : Action\Form
    {
        return $this->table_factory->action()->form(
            "create",
            $this->plugin->txt('add_resource'),
            $this->plugin->txt('save'),
            fn(ResourceItem $item) => $this->buildFields($item),
            fn(ResourceItem $item, array $data) => $this->save($item, $data),
            fn($x) => true,
            Action\Type::Global
        );
    }

    public function buildConfirmationNames(ResourceItem $item) : string
    {
        $type = $item->getType() === ResourceType::FILE
            ? $this->lng->txt('file')
            : $this->plugin->txt('resource_weblink');
        return $item->getTitle() . " ($type)";
    }

    public function buildFields(ResourceItem $item) : array
    {
        $factory = $this->ui_factory->input()->field();
        $fields = [];

        $title = $factory->text($this->plugin->txt("resource_title"))
            ->withRequired(true)
            ->withValue($item->getTitle());

        $description = $factory->textarea($this->lng->txt("description"))
            ->withValue((string) $item->getDescription());

        $resource_file = $factory->file($this->upload_handler, $this->lng->txt("file"))
            ->withValue(!empty($item->getIdentifier()) ? [$item->getIdentifier()] : [])
            ->withAcceptedMimeTypes(['application/pdf'])
            ->withRequired(true)
            ->withByline($this->plugin->txt("resource_file_description") . "<br>" . $this->plugin_ui_service->getMaxFileSizeString());

        $url = $factory->url($this->plugin->txt('resource_weblink'))
            ->withRequired(true)
            ->withValue($item->getUrl())
            ->withAdditionalTransformation(
                $this->refinery->custom()->constraint(
                    fn(string $x) => preg_match('/^https?:\/\//', $x) === 1,
                    $this->plugin->txt("not_a_weburl")
                )
            );

        $embedded = $factory->checkbox($this->plugin->txt('resource_embedded'),
            $this->plugin->txt('resource_embedded_info'))
            ->withValue($item->isEmbedded());

        $availability = $factory->radio($this->plugin->txt("resource_availability"))
            ->withRequired(true)
            ->withOption(ResourceAvailability::BEFORE->value, $this->plugin->txt("resource_availability_before"))
            ->withOption(ResourceAvailability::DURING->value, $this->plugin->txt("resource_availability_during"))
            ->withOption(ResourceAvailability::AFTER->value, $this->plugin->txt("resource_availability_after"))
            ->withValue($item->getAvailable()->value);

        $fields['title'] = $title;
        $fields['description'] = $description;

        $group1 = $factory->group(["resource_file" => $resource_file], $this->lng->txt("file"));
        $group2 = $factory->group(["url" => $url, "embedded" => $embedded], $this->plugin->txt("resource_weblink"));


        $fields['type'] = $factory->switchableGroup([
            ResourceType::FILE->value => $group1,
            ResourceType::URL->value => $group2,
        ], $this->lng->txt("type"))->withValue($item->getType()->value)
            ->withAdditionalTransformation(
                $this->refinery->custom()->constraint(
                    function ($var) {
                        return !($var[0] === ResourceType::FILE->value) || $var[1]["resource_file"] !== null;
                    },
                    $this->plugin->txt("missing_file")
                )
            );
        $fields['availability'] = $availability;

        return $fields;
    }

    public function save(ResourceItem $item, array $data): void
    {
        if ($item->getId() !== 0) {
            $resource = $this->resource_service->one($item->getId());
        } else {
            $resource = $this->resource_service->new();
        }
        $resource
            ->setTitle((string) $data["title"])
            ->setDescription((string) $data["description"])
            ->setAvailability(ResourceAvailability::tryFrom($data["availability"]));

        switch ($data["type"][0]) {
            case ResourceType::FILE->value:
                $identifier = $data["type"][1]["resource_file"][0]; // required, always set

                if ($identifier !== $resource->getFileId()) {
                    if ($resource->getFileId() !== null) {
                        $this->file_storage->deleteFile($resource->getFileId());
                    }
                    $stored = $this->file_storage->saveFile(
                        $this->upload_handler->getApiStream($identifier),
                        $this->upload_handler->getApiInfo($identifier)
                    );
                    $resource->setFileId($stored?->getId());
                }
                $this->resource_service->save($resource
                    ->setType(ResourceType::FILE)
                    ->setUrl('')
                    ->setEmbedded(true)
                );
                break;

            case ResourceType::URL->value:
                if ($resource->getFileId()) {
                    $this->file_storage->deleteFile($resource->getFileId());
                }
                $this->resource_service->save($resource
                    ->setType(ResourceType::URL)
                    ->setFileId(null)
                    ->setUrl((string) $data["type"][1]["url"])
                    ->setEmbedded((bool) $data["type"][1]["embedded"])
                );
                break;
        }

        $this->success($this->lng->txt("settings_saved"), true);
    }

    protected function showItems(): void
    {
        $table = $this->table_factory->dataTable("resources", $this);
        $table->setTitle($this->plugin->txt('task_resources'));
        $table->executeAction();

        $table->addActionToToolbar($this->toolbar, $table->getActionByName("create"), true);

        $this->add($table->getComponents())->show();
    }

    protected function deleteItem(): void
    {
        $ids = $this->confirmationIds();

        foreach ($ids as $id) {
            if ($resource = $this->resource_service->one($id)) {
                $this->resource_service->delete($resource);
            }
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("resource_deleted"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    protected function downloadResourceFile(?string $identifier = null)
    {
        if ($identifier === null) {
            if ($this->get->has("resource_id")) {
                $id = $this->get->integer("resource_id", 0);
                $resource = $this->resource_service->one($id);
                if (
                    $resource !== null &&
                    $resource->getType() === ResourceType::FILE) {
                    $identifier = $resource->getFileId();
                } else {
                    throw new \Exception("Resource is invalid");
                }
            } else {
                throw new \Exception("Resource not found");
            }
        }
        $this->file_delivery->sendFile($identifier, Disposition::ATTACHMENT);
    }

    /**
     * @param ResourceItem $item
     */
    public function getColumnMapping(Item $item, ?array $additional_parameters) : array
    {
        $type = "";
        $info = null;

        switch ($item->getType()) {
            case ResourceType::FILE:
                try {
                    $file_info = $this->file_storage->getFileInfo($item->getIdentifier());
                    $name = $file_info->getFileName();
                    $size = $this->format_service->fileSize($file_info->getSize());
                    $info = $this->renderer->render($this->ui_factory->listing()->property()->withItems([
                        [$this->lng->txt("filename"), $name],
                        [$this->lng->txt("size"), $size],
                    ]));
                } catch (\Exception $e) {
                    $info = "-- Broken File --";
                }
                $type = $this->lng->txt('file');
                break;

            case ResourceType::URL:
                $info = $this->renderer->render($this->ui_factory->link()->standard(
                    $item->getUrl(), $item->getUrl()));
                if ($item->isEmbedded()) {
                    $info .= " (" . $this->plugin->txt("resource_embedded") . ")";
                }
                $type = $this->plugin->txt('resource_weblink');
                break;

            default:
                $info = '';
        }

        return [
            "title" => $item->getTitle(),
            "description" => nl2br($item->getDescription()),
            "type" => $type,
            "available" => $this->plugin->txt('resource_availability_' . $item->getAvailable()->value),
            "info" => $info,
        ];
    }

    public function getColumns(?array $additional_parameters) : array
    {
        $tf = $this->ui_factory->table();
        return [
            "title" => $tf->column()->text($this->lng->txt('title')),
            "description" => $tf->column()->text($this->lng->txt('description')),
            "type" => $tf->column()->status($this->lng->txt("type")),
            "available" => $tf->column()->status($this->plugin->txt('resource_availability')),
            "info" => $tf->column()->text($this->lng->txt("info"))->withIsSortable(false)
        ];
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters) : ?int
    {
        return -1;
    }

    protected function tableItemFromData(Resource $resource) : ResourceItem
    {
        return new ResourceItem($resource->getId(), $resource->getTitle(), $resource->getType(), $resource->getDescription(),
            $resource->getAvailability(), $resource->getUrl(), $resource->getFileId(), $resource->getEmbedded());
    }

    public function getTableItem(int $id) : Item
    {
        $resource = $this->resource_service->one($id);
        return $this->tableItemFromData($resource);
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = []) : \Generator
    {
        if ($ids === [0]) {
            yield $this->tableItemFromData($this->resource_service->new());
            return;
        }

        foreach ($this->resource_service->allByTypes(
            [ResourceType::FILE, ResourceType::URL]) as $resource) {
            if ((empty($ids) || in_array($resource->getId(), $ids))) {
                yield $this->tableItemFromData($resource);
            }
        }
    }

    public function previewModal(ResourceItem $item)
    {
        $components = [];
        if ($item->getType() === ResourceType::FILE) {
            $this->ctrl->setParameter($this, "resource_id", $item->getId());
            $link = $this->ctrl->getLinkTarget($this, "downloadResourceFile");
            $components[] = $this->plugin_ui_factory->viewer()->pdf($link);
        } elseif ($item->isEmbedded() && $item->getType() === ResourceType::URL) {
            $url = $item->getUrl();
            $components[] = $this->ui_factory->legacy("<iframe src=\"$url\" width=\"100%\" height=\"500px\"></iframe>");
        }

        $title = $this->lng->txt("preview") . ": " . $item->getTitle();
        return $this->ui_factory->modal()->lightbox([
            $this->ui_factory->modal()->lightboxTextPage($this->renderer->render($components), $title)
        ]);
    }
}
