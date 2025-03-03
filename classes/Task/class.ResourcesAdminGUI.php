<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\UI\UIService;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\ResourceStorage\Resource\StorableResource;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\ResourcesAdminGUI: ilObjLongEssayAssessmentGUI
 */
class ResourcesAdminGUI extends BaseGUI implements DataTableParent
{
    use ConfirmationIds;

    private \ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory $table_factory;
    protected \ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository $task_repo;
    protected UIService $uiService;

    public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->post = $this->dic->http()->wrapper()->post();
        $this->uiService = $this->localDI->getUIService();
        $this->task_repo = $this->localDI->getTaskRepo();
        $this->table_factory = $this->localDI->getTableFactory();
    }


    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();
        switch ($next_class) {
            default:
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
                break;
        }
    }

    public function getTableActions() : array
    {
        return [$this->openAction(), $this->previewAction(), $this->downloadAction(), $this->editAction(), $this->deleteAction(), $this->createAction()];
    }

    protected function deleteAction() : Action\Confirmation
    {
        return $this->table_factory->action()->confirmation(
            "delete",
            $this->lng->txt('delete'),
            $this->lng->txt('delete'),
            $this->plugin->txt('delete_resource_confirmation'),
            $this->ctrl->getFormAction($this, 'deleteItem'),
            fn (ResourceItem $x) => $this->buildConfirmationNames($x)
        );
    }

    protected function previewAction() : Action\Modal
    {
        return $this->table_factory->action()->modal(
            "preview",
            $this->lng->txt('preview'),
            [$this, "previewModal"],
            fn (ResourceItem $x) => ($x->getType() === Resource::RESOURCE_TYPE_FILE || ($x->isEmbedded() && $x->getType() === Resource::RESOURCE_TYPE_URL)),
            Action\Type::Single
        );
    }

    protected function downloadAction() : Action\Direct
    {
        return $this->table_factory->action()->direct(
            "download",
            $this->lng->txt('download'),
            fn (ResourceItem $x) => $this->downloadResourceFile($x->getIdentifier()),
            fn (ResourceItem $x) => $x->getType() === Resource::RESOURCE_TYPE_FILE,
            Action\Type::Single
        );
    }

    protected function openAction(): Action\Direct
    {
        return $this->table_factory->action()->direct(
            "open",
            $this->lng->txt('open'),
            fn (ResourceItem $x) => $this->ctrl->redirectToURL($x->getUrl()),
            fn (ResourceItem $x) => $x->getType() === Resource::RESOURCE_TYPE_URL,
            Action\Type::Single
        );
    }

    protected function editAction(): Action\Form
    {
        return $this->table_factory->action()->form(
            "edit",
            $this->lng->txt('edit'),
            $this->plugin->txt('save'),
            [$this, "buildFields"],
            [$this, "save"],
            fn (ResourceItem $item) => true,
            Action\Type::Single
        );
    }

    protected function createAction(): Action\Form
    {
        return $this->table_factory->action()->form(
            "create",
            $this->plugin->txt('add_resource'),
            $this->plugin->txt('save'),
            fn (ResourceItem $item) => $this->buildFields($item),
            fn (ResourceItem $item, array $data) => $this->save($item, $data),
            fn ($x) => true,
            Action\Type::Global
        );
    }

    public function buildConfirmationNames(ResourceItem $item): string
    {
        $type = $item->getType() === Resource::RESOURCE_TYPE_FILE
            ? $this->lng->txt('file')
            : $this->plugin->txt('resource_weblink');
        return $item->getTitle() . " ({$type})";
    }

    public function buildFields(ResourceItem $item) : array
    {
        $factory = $this->uiFactory->input()->field();
        $fields = [];

        $title = $factory->text($this->plugin->txt("resource_title"))
                         ->withRequired(true)
                         ->withValue($item->getTitle());

        $description = $factory->textarea($this->lng->txt("description"))
                               ->withValue((string) $item->getDescription());

        $resource_file = $factory->file(new ResourceUploadHandlerGUI($this->storage, $this->localDI->getTaskRepo()), $this->lng->txt("file"))
                                 ->withValue(!empty($item->getIdentifier()) ? [$item->getIdentifier()] : [])
                                 ->withAcceptedMimeTypes(['application/pdf'])
                                 ->withRequired(true)
                                 ->withByline($this->plugin->txt("resource_file_description") . "<br>" . $this->uiService->getMaxFileSizeString());

        $url = $factory->url($this->plugin->txt('resource_weblink'))
                       ->withRequired(true)
                       ->withValue($item->getUrl())
        ->withAdditionalTransformation(
            $this->refinery->custom()->constraint(
                fn(string $x) => preg_match('/^https?:\/\//', $x) === 1 ,
                $this->plugin->txt("not_a_weburl")
            )
        );

        $embedded = $factory->checkbox($this->plugin->txt('resource_embedded'), $this->plugin->txt('resource_embedded_info'))
            ->withValue($item->isEmbedded());

        $availability = $factory->radio($this->plugin->txt("resource_availability"))
                                ->withRequired(true)
                                ->withOption(Resource::RESOURCE_AVAILABILITY_BEFORE, $this->plugin->txt("resource_availability_before"))
                                ->withOption(Resource::RESOURCE_AVAILABILITY_DURING, $this->plugin->txt("resource_availability_during"))
                                ->withOption(Resource::RESOURCE_AVAILABILITY_AFTER, $this->plugin->txt("resource_availability_after"))
                                ->withValue($item->getAvailable());

        $fields['title'] = $title;
        $fields['description'] = $description;

        $group1 = $factory->group(["resource_file" => $resource_file,], $this->lng->txt("file"));
        $group2 = $factory->group(["url" => $url, "embedded" => $embedded], $this->plugin->txt("resource_weblink"));


        $fields['type'] = $factory->switchableGroup([
            Resource::RESOURCE_TYPE_FILE => $group1,
            Resource::RESOURCE_TYPE_URL => $group2,
        ], $this->lng->txt("type"))->withValue($item->getType())
                                  ->withAdditionalTransformation(
                                      $this->refinery->custom()->constraint(
                                          function ($var) {
                                              return !($var[0] === Resource::RESOURCE_TYPE_FILE) || $var[1]["resource_file"] !== null;
                                          },
                                          $this->plugin->txt("missing_file")
                                      )
                                  );
        $fields['availability'] = $availability;

        return $fields;
    }

    public function save(ResourceItem $item, array $data)
    {
        if ($item->getId() === 0) {
            $this->createResource($data);
        } else {
            $this->replaceResource($data, (int)$item->getId());
        }
        $resource_admin = new ResourceAdmin($this->object->getId());

        $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
    }

    /**
     * Show the items
     */
    protected function showItems()
    {


        $di = LongEssayAssessmentDI::getInstance();
        $task_repo = $di->getTaskRepo();
        $resources = $task_repo->getResourceByTaskId($this->object->getId(), [Resource::RESOURCE_TYPE_URL, Resource::RESOURCE_TYPE_FILE]);

        $table = $this->table_factory->dataTable("resources", $this);
        $table->setTitle($this->plugin->txt('task_resources'));
        $table->executeAction();

        $table->addActionToToolbar($this->toolbar, $table->getActionByName("create"), true);

        $this->setContent($this->renderer->render($table->getComponents()));
    }

    /**
     * Create a new resource
     * @param array $a_data
     * @param Resource $a_resource
     * @return void
     */
    protected function createResource(array $a_data)
    {
        $resource_admin = new ResourceAdmin($this->object->getId());

        switch ($a_data["type"][0]) {
            case Resource::RESOURCE_TYPE_FILE:
                $resource_admin->saveFileResource(
                    $a_data["title"],
                    $a_data["description"],
                    $a_data["availability"],
                    (string)$a_data["type"][1]["resource_file"][0]
                );
                break;
            case Resource::RESOURCE_TYPE_URL:
                $resource_admin->saveURLResource(
                    $a_data["title"],
                    $a_data["description"],
                    $a_data["availability"],
                    (string)$a_data["type"][1]["url"],
                    $a_data["type"][1]["embedded"]
                );
                break;
        }
    }

    /**
     * Replace an existing resource
     * @param array $a_data
     * @param int $resource_id
     * @return void
     */
    protected function replaceResource(array $a_data, int $resource_id)
    {
        $resource_admin = new ResourceAdmin($this->object->getId());

        // check if an uploaded file should be kept
        $delete_with_file = true;
        if ($a_data["type"][0] == Resource::RESOURCE_TYPE_FILE
            && isset($a_data["type"][1]["resource_file"][0])
        ) {
            $task_repo = $this->localDI->getTaskRepo();
            if (!empty($resource = $task_repo->getResourceById($resource_id))
                && !empty($resource->getFileId())
                && $resource->getFileId() == $a_data["type"][1]["resource_file"][0]) {
                $delete_with_file = false;
            }
        }

        $resource_admin->deleteResource($resource_id, $delete_with_file);
        $this->createResource($a_data);
    }

    /**
     * Delete Resource items
     * @return void
     */
    protected function deleteItem()
    {
        $resource_admin = new ResourceAdmin($this->object->getId());
        $ids = $this->confirmationIds();

        foreach($ids as $id) {
            $resource_admin->deleteResource($id);
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("resource_deleted"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    protected function downloadResourceFile(?string $identifier = null)
    {
        if($identifier === null) {
            if($this->http->wrapper()->query()->has("resource_id")) {
                $id = $this->http->wrapper()->query()->retrieve("resource_id", $this->refinery->kindlyTo()->int());
                /**
                 * @var $resource Resource
                 */
                $resource = $this->task_repo->getResourceById($id);

                if(
                    $resource !== null &&
                    $resource->getTaskId() === $this->object->getId() &&
                    $resource->getType() === Resource::RESOURCE_TYPE_FILE) {
                    $identifier = $resource->getFileId();
                } else {
                    throw new \Exception("Resource is invalid");
                }
            } else {
                throw new \Exception("Resource not found");
            }
        }

        $resource_info = $this->getFileResource($identifier);
        $this->storage->consume()->download($resource_info->getIdentification())->run();
    }

    public function getColumnMapping(Item $item, ?array $additional_parameters) : array
    {
        /**
         * @var ResourceItem $item
         */
        global $DIC;
        $type = "";
        $info = null;

        switch ($item->getType()) {
            case Resource::RESOURCE_TYPE_FILE:
                try {
                    $resource_info = $this->getFileResource($item->getIdentifier());
                    $name = $resource_info->getCurrentRevision()->getTitle();
                    $version = $resource_info->getCurrentRevision()->getVersionNumber();
                    $size = $this->humanFileSize($resource_info->getFullSize());
                    $info = $this->renderer->render($this->uiFactory->listing()->property()->withItems([
                        [$this->lng->txt("filename"), $name],
                        [$this->lng->txt("version"), (string)$version],
                        [$this->lng->txt("size"), $size],
                    ]));
                } catch(\Exception $e) {
                    $info = "-- Broken File --";
                }
                $type = $this->lng->txt('file');
                break;
            case Resource::RESOURCE_TYPE_URL:
                $info = $this->renderer->render($this->uiFactory->link()->standard($item->getUrl(), $item->getUrl()));

                if($item->isEmbedded()) {
                    $info .= " (" . $this->plugin->txt("resource_embedded") . ")";
                }
                $type = $this->plugin->txt('resource_weblink');
        }

        return [
            "title" => $item->getTitle(),
            "description" => nl2br($item->getDescription()),
            "type" => $type,
            "available" => $this->plugin->txt('resource_availability_'.$item->getAvailable()),
            "info" => $info,
        ];
    }

    public function getColumns(?array $additional_parameters) : array
    {

        $tf = $this->uiFactory->table();
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

    protected function tableItemFromData(Resource $item): ResourceItem
    {
        return new ResourceItem($item->getId(), $item->getTitle(), $item->getType(), $item->getDescription(), $item->getAvailability(), $item->getUrl(), $item->getFileId(), $item->getEmbedded());
    }

    public function getTableItem(int $id) : Item
    {
        $resource = $this->task_repo->getResourceById($id);

        if($resource->getTaskId() === $this->object->getId()) {
            return $this->tableItemFromData($resource);
        }
        throw new \Exception("Resource does not belong to this task");
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = []) : \Generator
    {
        if ($ids === [0]) {
            yield $this->tableItemFromData(Resource::model());
            return;
        }

        foreach ($this->task_repo->getResourceByTaskId($this->object->getId(), [Resource::RESOURCE_TYPE_URL,Resource::RESOURCE_TYPE_FILE]) as $resource) {
            if((empty($ids) || in_array($resource->getId(), $ids)) && $resource->getTaskId() === $this->object->getId()) {
                yield $this->tableItemFromData($resource);
            }
        }
    }

    protected function humanFileSize($size, $unit="")
    {
        if((!$unit && $size >= 1<<30) || $unit == "GB") {
            return number_format($size/(1<<30), 2)."GB";
        }
        if((!$unit && $size >= 1<<20) || $unit == "MB") {
            return number_format($size/(1<<20), 2)."MB";
        }
        if((!$unit && $size >= 1<<10) || $unit == "KB") {
            return number_format($size/(1<<10), 2)."KB";
        }
        return number_format($size)." bytes";
    }

    public function previewModal(ResourceItem $item)
    {
        if($item->getType() === Resource::RESOURCE_TYPE_FILE)
        {
            $this->ctrl->setParameter($this, "resource_id", $item->getId());
            $link = $this->ctrl->getLinkTarget($this, "downloadResourceFile");
            $component = $this->localDI->getUIFactory()->viewer()->pdf($link);
        } elseif($item->isEmbedded() && $item->getType() === Resource::RESOURCE_TYPE_URL) {
            $url = $item->getUrl();
            $component = $this->uiFactory->legacy("<iframe src=\"$url\" width=\"100%\" height=\"500px\"></iframe>");
        }

        $title = $this->lng->txt("preview") . ": " . $item->getTitle();
        return $this->uiFactory->modal()->lightbox([
            $this->uiFactory->modal()->lightboxTextPage($this->renderer->render($component), $title)
        ]);
    }

    protected function getFileResource(string $identifier) : StorableResource
    {
        $resource_id = $this->storage->manage()->find($identifier);
        if($resource_id === null) {
            throw new \ilException("no resource id found");
        }

        return  $this->storage->manage()->getResource($resource_id);
    }
}
