<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\UI\UIService;
use ilUtil;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Task\ResourcesAdminGUI: ilObjLongEssayAssessmentGUI
 */
class ResourcesAdminGUI extends BaseGUI
{
	protected UIService $uiService;

	public function __construct(\ilObjLongEssayAssessmentGUI $objectGUI)
	{
		parent::__construct($objectGUI);
		$this->uiService = $this->localDI->getUIService();
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

    /**
     * Show the items
     */
    protected function showItems()
    {
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
        $button = \ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'editItem'));
        $button->setCaption($this->plugin->txt('add_resource'), false);
        $this->toolbar->addButtonInstance($button);

        $di = LongEssayAssessmentDI::getInstance();
        $task_repo = $di->getTaskRepo();
        $resources = $task_repo->getResourceByTaskId($this->object->getId(), [Resource::RESOURCE_TYPE_URL, Resource::RESOURCE_TYPE_FILE]);

        $list = new ResourceListGUI($this, $this->uiFactory, $this->renderer, $this->lng, $this->plugin);
        $list->setItems($resources);

        $this->tpl->setContent($list->render());
    }




    /**
     * Build Resource Form
     * @param \ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource $a_resource
     * @return \ILIAS\UI\Component\Input\Container\Form\Standard
     */
    protected function buildResourceForm(Resource $a_resource): \ILIAS\UI\Component\Input\Container\Form\Standard
    {
        if ($this->getResourceId() != null) {
            $section_title = $this->plugin->txt('resource_edit');
        }
        else {
            $section_title = $this->plugin->txt('resource_add');
        }
        $factory = $this->uiFactory->input()->field();

        $title = $factory->text($this->plugin->txt("resource_title"))
            ->withRequired(true)
            ->withValue($a_resource->getTitle());

        $description = $factory->textarea($this->lng->txt("description"))
            ->withValue((string) $a_resource->getDescription());

        $resource_file = $factory->file(new ResourceUploadHandlerGUI($this->storage, $this->localDI->getTaskRepo()), $this->lng->txt("file"))
			->withValue($a_resource->getFileId() !== null ? [$a_resource->getFileId()] : null)
            ->withAcceptedMimeTypes(['application/pdf'])
            ->withByline($this->plugin->txt("resource_file_description") . "<br>" . $this->uiService->getMaxFileSizeString());

        $url = $factory->text($this->lng->txt('url'))
			->withRequired(true)
            ->withValue($a_resource->getUrl());

        $availability = $factory->radio($this->plugin->txt("resource_availability"))
            ->withRequired(true)
            ->withOption(Resource::RESOURCE_AVAILABILITY_BEFORE, $this->plugin->txt("resource_availability_before"))
            ->withOption(Resource::RESOURCE_AVAILABILITY_DURING, $this->plugin->txt("resource_availability_during"))
            ->withOption(Resource::RESOURCE_AVAILABILITY_AFTER, $this->plugin->txt("resource_availability_after"))
            ->withValue($a_resource->getAvailability());

        $sections = [];
        // Object
        $fields = [];
        $fields['title'] = $title;
        $fields['description'] = $description;

		$group1 = $factory->group(["resource_file" => $resource_file,], $this->lng->txt("file"));
        $group2 = $factory->group(["url" => $url, ],$this->plugin->txt("resource_weblink"));


        $fields['type'] = $factory->switchableGroup([
            Resource::RESOURCE_TYPE_FILE => $group1,
            Resource::RESOURCE_TYPE_URL => $group2,
        ], $this->lng->txt("type"))->withValue($a_resource->getType())
			->withAdditionalTransformation(
				$this->refinery->custom()->constraint(
					function ($var){
						return !($var[0] === Resource::RESOURCE_TYPE_FILE) || $var[1]["resource_file"] !== null;
					}, $this->plugin->txt("missing_file")
				)
			);
        $fields['availability'] = $availability;
        $sections['form'] = $factory->section($fields, $section_title);
        $action = $this->ctrl->getFormAction($this, "editItem");

        return $this->uiFactory->input()->container()->form()->standard($action, $sections);
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

        switch ($a_data["type"][0])
        {
            case Resource::RESOURCE_TYPE_FILE:
                $resource_admin->saveFileResource(
                    $a_data["title"],
                    $a_data["description"],
                    $a_data["availability"],
                    (string)$a_data["type"][1]["resource_file"][0]);
                break;
            case Resource::RESOURCE_TYPE_URL:
                $resource_admin->saveURLResource(
                    $a_data["title"],
                    $a_data["description"],
                    $a_data["availability"],
                    $a_data["type"][1]["url"]);
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
            && !isset($a_data["type"][1]["resource_file"][0])
        ) {
            $task_repo = $this->localDI->getTaskRepo();
            if (!empty($resource = $task_repo->getResourceById($resource_id))
            && !empty($resource->getFileId())) {
                $a_data["type"][1]["resource_file"][0] = $resource->getFileId();
                $delete_with_file = false;
            }
        }

        $resource_admin->deleteResource($resource_id, $delete_with_file);
        $this->createResource($a_data);
    }

    /**
     * Edit and save the settings
     */
    protected function editItem()
    {
		$this->tabs->setBackTarget($this->lng->txt("back"), $this->ctrl->getLinkTarget($this));

        $resource_admin = new ResourceAdmin($this->object->getId());
        $resource_id = $this->getResourceId();
        if ($resource_id != null) {
            $this->ctrl->setParameter($this, 'resource_id', $resource_id);
        }
        $resource = $resource_admin->getResource($resource_id);

        if ($resource_id != null && $resource->getTaskId() != $this->object->getId()) {
            $this->raisePermissionError();
        }

        $form = $this->buildResourceForm($resource);

		if($this->request->getMethod() === "POST") {
			$form = $form->withRequest($this->request);

			if (($data = $form->getData()) !== null) {
				if ($resource_id == null) {
					$this->createResource($data["form"]);
				} else {
					$this->replaceResource($data["form"], (int)$resource_id);
				}
				ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
				$this->ctrl->redirect($this, "showItems");
			}
		}

        $this->tpl->setContent($this->renderer->render($form));
    }

	/**
	 * Delete Resource items
	 * @return void
	 */
	protected function deleteItem(){
		$identifier = "";
		if(($resource_id = $this->getResourceId()) !== null) {
			$resource_admin = new ResourceAdmin($this->object->getId());
			$resource = $resource_admin->getResource($resource_id);

			if($resource->getTaskId() == $this->object->getId()){
				$resource_admin->deleteResource($resource_id);
				ilUtil::sendSuccess($this->lng->txt("resource_deleted"), true);
			}else {
				ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
			}
		}else{
			// TODO: Error no resource ID in GET
		}
		$this->ctrl->redirect($this, "showItems");
	}

    /**
     * @return ?int
     */
    protected function getResourceId(): ?int
    {
		if (isset($_GET["resource_id"]) && is_numeric($_GET["resource_id"]))
		{
			return (int) $_GET["resource_id"];
		}
		return null;
    }

    protected function downloadResourceFile() {
        global $DIC;
        $identifier = "";
        if(($resource_id = $this->getResourceId()) !== null) {
			$resource_admin = new ResourceAdmin($this->object->getId());
			$resource = $resource_admin->getResource($resource_id);

			if ($resource->getType() == Resource::RESOURCE_TYPE_FILE && is_string($resource->getFileId())) {
				$identifier = $resource->getFileId();
			}

			if ($resource->getTaskId() != $this->object->getId()) {
				ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
				$this->ctrl->redirect($this, "showItems");
			}
		}else{
			// TODO: Error no resource ID in GET
		}

		$resource = $DIC->resourceStorage()->manage()->find($identifier);

        if ($resource !== null) {
            $DIC->resourceStorage()->consume()->download($resource)->run();
        }else{
			// TODO: Error resource not in Storage
		}
    }
}