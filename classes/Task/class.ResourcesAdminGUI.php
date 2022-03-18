<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\Resource;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;

use \ilUtil;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayTask\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\Task\ResourcesAdminGUI: ilObjLongEssayTaskGUI
 */
class ResourcesAdminGUI extends BaseGUI
{
    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showItems');
        switch ($cmd)
        {
            case 'showItems':
            case "editItem":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
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

        $di = LongEssayTaskDI::getInstance();
        $task_repo = $di->getTaskRepo();
        $resources = $task_repo->getResourceByTaskId($this->object->getId());

        $list = new ResourceListGUI($this->uiFactory, $this->renderer, $this->lng);
        $list->setItems($resources);

        $this->tpl->setContent($list->render());
    }


    /**
     * Edit and save the settings
     */
    protected function editItem()
    {
//        $di = LongEssayTaskDI::getInstance();
//        $task_repo = $di->getTaskRepo();
//
//        if(($resource_id = $this->getResourceId())!= null)
//        {
//            $resource = $task_repo->getResourceById($resource_id);
//
//            if ($resource->getTaskId() != $this->object->getId()) {
//                $this->raisePermissionError();
//            }
//
//        }else{
//            $resource = new Resource();
//        }
        $resource_admin = new ResourceAdmin($this->object->getId());
        $resource = $resource_admin->getResource($this->getResourceId());

        $form = $this->buildResourceForm($resource);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
            $result = $form->getInputGroup()->getContent();
            $this->ctrl->setParameter($this, 'resource_id', $resource->getId());

            if ($result->isOK()) {


                ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
                $this->ctrl->redirect($this, "editItem");
            }else{
                // TODO: lang var
                ilUtil::sendFailure($this->lng->txt("validation_failure"), true);
            }
        }

        $this->tpl->setContent($this->renderer->render($form));
    }

    /**
     * Build Resource Form
     * @param Resource $a_resource
     * @return \ILIAS\UI\Component\Input\Container\Form\Standard
     */
    protected function buildResourceForm(Resource $a_resource): \ILIAS\UI\Component\Input\Container\Form\Standard
    {
        if ($this->getResourceId() != null) {
            $section_title = $this->plugin->txt('Material bearbeiten');
        }
        else {
            $section_title = $this->plugin->txt('Material hinzufügen');
        }

        $factory = $this->uiFactory->input()->field();

        $sections = [];

        // Object
        $fields = [];
        $fields['title'] = $factory->text($this->lng->txt("title"))
            ->withRequired(true)
            ->withValue($a_resource->getTitle());

        $fields['description'] = $factory->textarea($this->lng->txt("description"))
            ->withValue((string) $a_resource->getDescription());


        $group1 = $this->uiFactory ->input()->field()->group(
            [
                "file" => $this->uiFactory->input()->field()->file(new \ilUIDemoFileUploadHandlerGUI(), "Datei hochladen")
                    ->withAcceptedMimeTypes(['application/pdf']),
            ],
            "Datei"
        );
        $group2 = $this->uiFactory->input()->field()->group(
            [
                "url" =>  $this->uiFactory->input()->field()->text('Url')
                    ->withValue($a_resource->getUrl())
            ],
            "Weblink"
        );

        $fields['type'] = $this->uiFactory->input()->field()->switchableGroup(
            [
                Resource::RESOURCE_TYPE_FILE => $group1,
                Resource::RESOURCE_TYPE_URL => $group2,
            ],
            "Typ"
        )->withValue($a_resource->getType());

        $fields['availability'] = $factory->radio("Verfügbarkeit")
            ->withRequired(true)
            //->withValue($a_resource->getAvailability())
            ->withOption(Resource::RESOURCE_AVAILABILITY_BEFORE, "Vorab")
            ->withOption(Resource::RESOURCE_AVAILABILITY_DURING, "Nach Start der Bearbeitung")
            ->withOption(Resource::RESOURCE_AVAILABILITY_AFTER, "Zur Einsichtnahme");


        $sections['form'] = $factory->section($fields, $section_title);

        return $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);
    }

    /**
     * @param array $a_data
     * @param Resource $a_resource
     * @return void
     */
    protected function updateResource(array $a_data, Resource $a_resource)
    {
        global $DIC;
        $resource_admin = new ResourceAdmin($this->object->getId());

        switch ($a_data["type"])
        {
            case Resource::RESOURCE_TYPE_FILE:
                $upload_result = $DIC->upload()->getResults()['my_uploaded_file'];
                $resource_admin->saveFileResource(
                    $a_data["title"],
                    $a_data["description"],
                    $a_data["availability"],
                    $upload_result,
                    $DIC->user()->getId());
                break;
            case Resource::RESOURCE_TYPE_URL:
                $resource_admin->saveURLResource(
                    $a_data["title"],
                    $a_data["description"],
                    $a_data["availability"],
                    $a_data["url"]);
                break;
        }
    }

    /**
     * @return ?int
     */
    protected function getResourceId(): ?int
    {
        if (isset($_GET["resouce_id"]))
        {
            return (int) $_GET["resouce_id"];
        }
        return null;
    }

    protected function downloadResource(): void
    {
        global $DIC;
        $response = $DIC->http()->response();

        $response->withBody();
    }

}