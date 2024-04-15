<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use Edutiek\LongEssayAssessmentService\Writer\Service;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Task\ResourceAdmin;
use \ilUtil;

/**
 * Start page for writers
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Writer
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterStartGUI: ilObjLongEssayAssessmentGUI
 */
class WriterStartGUI extends BaseGUI
{
    /** @var \ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings */
    protected $task;

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $taskRepo = $this->localDI->getTaskRepo();
        $this->task = $taskRepo->getTaskSettingsById($this->object->getId());

        $cmd = $this->ctrl->getCmd('showStartPage');
        switch ($cmd) {
            case 'showStartPage':
            case 'startWriter':
            case 'startWritingReview':
            case 'downloadWriterPdf':
            case 'downloadCorrectedPdf':
            case 'downloadResourceFile':
            case 'viewInstructions':
            case 'downloadInstructions':
            case 'viewSolution':
            case 'downloadSolution':
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Show the items
     */
    protected function showStartPage()
    {
        $contents = [];
        $modals = [];

        if (!empty($essay = $this->data->getOwnEssay())) {
            if (!empty($essay->getWritingExcluded())) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt("message_writing_excluded"), false);
            } elseif (!empty($essay->getWritingAuthorized())) {
                if(!empty($this->task->getClosingMessage())) {
                    $message = $this->displayText($this->task->getClosingMessage());
                } else {
                    $message = $this->plugin->txt('message_writing_authorized');
                }

                $review_message = '';
                $back_link = '';
                if (!empty($this->task->getReviewStart()) || !empty($this->task->getReviewEnd())) {
                    $review_message = '<p>'. sprintf(
                        $this->plugin->txt('message_review_period'),
                        $this->data->formatPeriod($this->task->getReviewStart(), $this->task->getReviewEnd())
                    ) . '</p>';
                }

                if (isset($this->params['returned'])) {
                    $back_url = \ilLink::_getLink($this->dic->repositoryTree()->getParentId($this->object->getRefId()));
                    $back_text = $this->plugin->txt('message_writing_authorized_link');
                    $back_link = '<p><a href="'.$back_url.'">'.$back_text.'</a></p>';
                    $this->tpl->setOnScreenMessage("success", $message. $review_message. $back_link, false);
                } else {
                    $this->tpl->setOnScreenMessage("info", $message. $review_message. $back_link, false);
                }
            }
        }

        // Toolbar

        if ($this->object->canWrite()) {
            $button = \ilLinkButton::getInstance();
            $button->setUrl($this->ctrl->getLinkTarget($this, 'startWriter'));
            $button->setCaption($this->plugin->txt(empty($essay) ? 'start_writing' : 'continue_writing'), false);
            $button->setPrimary(true);
            $this->toolbar->addButtonInstance($button);

            if (isset($this->params['returned'])) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt('message_writing_returned_interrupted'), false);
            }
        } elseif (!empty($essay) && empty($essay->getWritingAuthorized())) {

            if ($this->object->canReviewWrittenEssay()) {
                $button = \ilLinkButton::getInstance();
                $button->setUrl($this->ctrl->getLinkTarget($this, 'startWritingReview'));
                $button->setCaption($this->plugin->txt('review_writing'), false);
                $this->toolbar->addButtonInstance($button);
                $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('message_writing_to_authorize'), false);
            } else {
                $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('message_writing_not_authorized'), false);
            }
        }


        // Instructions

        $writing_end = null;
        if (!empty($this->task->getWritingEnd())) {
            $writing_end = $this->data->unixTimeToDb(
                $this->data->dbTimeToUnix($this->task->getWritingEnd()) + $this->data->getOwnTimeExtensionSeconds()
            );
        }

        $inst_parts = [];
        if (!empty($this->task->getDescription())) {
            $inst_parts[] = $this->displayText($this->task->getDescription());
        }
        if ($this->data->isInRange(time(), $this->data->dbTimeToUnix($this->task->getWritingStart()), null)) {
            $inst_buttons = [];
            if (!empty($this->task->getInstructions())) {
                $inst_buttons[] = $this->uiFactory->button()->standard(
                    $this->plugin->txt('view_instructions'),
                    $this->ctrl->getLinkTarget($this, 'viewInstructions')
                );
            }
            $task_repo = $this->localDI->getTaskRepo();
            if (!empty($task_repo->getInstructionResource($this->object->getId()))) {
                $inst_buttons[] = $this->uiFactory->button()->standard(
                    $this->plugin->txt('download_instructions'),
                    $this->ctrl->getLinkTarget($this, 'downloadInstructions')
                );
            }

            if (!empty($inst_buttons)) {
                $inst_parts[] = $this->renderer->render($inst_buttons);
            }
        }

        $refresh_link = '';
        if ($this->localDI->getDataService($this->task->getTaskId())->dbTimeToUnix($this->task->getWritingStart()) > time()) {
            $refresh_link = ' ' . $this->renderer->render($this->uiFactory->button()->shy($this->plugin->txt('refresh_page'), $this->ctrl->getLinkTarget($this)));
        }
        $properties = [$this->plugin->txt('writing_period') => $this->data->formatPeriod(
            $this->task->getWritingStart(),
            $writing_end
        ) . $refresh_link];
        
        
        
        if (isset($essay) && $essay->getLocation() !== null) {
            $taskRepo = $this->localDI->getTaskRepo();
            $properties[$this->plugin->txt("location")] = ($location = $taskRepo->getLocationById($essay->getLocation())) !== null ? $location->getTitle() : " - ";
        }

        $contents[] = $this->uiFactory->item()->group(
            $this->plugin->txt('task_instructions'),
            [$this->uiFactory->item()->standard($this->lng->txt('description'))
                ->withDescription($this->createPlaceholder(implode('<br>', $inst_parts)))
                ->withProperties($properties)]
        );


        // Resources

        $repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
        $writing_resources = [];
        $solution_items = [];

        $resources = $repo->getResourceByTaskId($this->object->getId(), [Resource::RESOURCE_TYPE_URL, Resource::RESOURCE_TYPE_FILE]);

        /** @var Resource $resource */
        foreach ($resources as $resource) {
            $item = null;
            if ($this->data->isResourceAvailable($resource, $this->task)) {

                if ($resource->getType() == Resource::RESOURCE_TYPE_FILE && $resource->getFileId() !== null) {
                    $resource_file = $this->dic->resourceStorage()->manage()->find($resource->getFileId());
                    if ($resource_file !== null) {
                        $revision = $this->dic->resourceStorage()->manage()->getCurrentRevision($resource_file);

                        $this->ctrl->setParameter($this, "resource_id", $resource->getId());

                        $item = $this->uiFactory->item()->standard(
                            $this->uiFactory->link()->standard(
                                $resource->getTitle(),
                                $this->ctrl->getLinkTarget($this, "downloadResourceFile")
                            )
                        )
                                                ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('file', 'File', 'medium'))
                                                ->withProperties(array(
                                                    "Filename" => $revision->getInformation()->getTitle(),
                                                    "Verfügbar" => $this->plugin->txt('resource_availability_' . $resource->getAvailability())));
                    }
                } else {
                    $item = $this->uiFactory->item()->standard($this->uiFactory->link()->standard($resource->getTitle(), $resource->getUrl()))
                        ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('webr', 'Link', 'medium'))
                        ->withProperties(array(
                            "Webseite" => $resource->getUrl(),
                            "Verfügbar" => $this->plugin->txt('resource_availability_' . $resource->getAvailability())));
                }

                if ($item !== null) {
                    if ($resource->getAvailability() == Resource::RESOURCE_AVAILABILITY_AFTER) {
                        $solution_items[] = $item;
                    } else {
                        $writing_resources[] = $item;
                    }
                }

            }
        }
        if (!empty($writing_resources)) {
            $contents[] = $this->uiFactory->item()->group("Material", $writing_resources);
        }

        // Result

        $result_actions = [];
        if (isset($essay)) {

            if ($this->object->canReviewWrittenEssay() && !empty($essay->getWritingAuthorized())) {
                $result_actions[] = $this->uiFactory->button()->standard(
                    $this->plugin->txt('download_written_submission'),
                    $this->ctrl->getLinkTarget($this, 'downloadWriterPdf')
                );
            }
            if ($this->object->canReviewCorrectedEssay()) {
                $result_actions[] = $this->uiFactory->button()->standard(
                    $this->plugin->txt('download_corrected_submission'),
                    $this->ctrl->getLinkTarget($this, 'downloadCorrectedPdf')
                );
            }
        }

        $actions_html = '';
        foreach ($result_actions as $action) {
            $actions_html .= $this->renderer->render($action);
        }

        if ($this->object->canViewResult()) {
            $result_text = $this->data->formatFinalResult($essay);
        } else {
            $result_text = $this->data->formatResultAvailability($this->task);
        }

        $result_item = $this->uiFactory->item()->standard($result_text)
            ->withDescription($this->createPlaceholder($actions_html))
            ->withProperties(array(
                $this->plugin->txt('review_period') => $this->data->formatPeriod(
                    $this->task->getReviewStart(),
                    $this->task->getReviewEnd()
                )));


        $contents[] = $this->uiFactory->item()->group($this->plugin->txt('result'), [$result_item]);


        // Solution
        if ($this->object->canViewSolution()) {
            $solution_buttons = [];
            if (!empty($this->task->getSolution())) {
                $solution_buttons[] =$this->uiFactory->button()->standard(
                    $this->plugin->txt('view_solution'),
                    $this->ctrl->getLinkTarget($this, 'viewSolution')
                );
            }
            $task_repo = $this->localDI->getTaskRepo();
            if (!empty($task_repo->getSolutionResource($this->object->getId()))) {
                $solution_buttons[] =$this->uiFactory->button()->standard(
                    $this->plugin->txt('download_solution'),
                    $this->ctrl->getLinkTarget($this, 'downloadSolution')
                );
            }


            if (!empty($solution_buttons)) {
                $solution_item = $this->uiFactory->item()->standard($this->createPlaceholder($this->renderer->render($solution_buttons)));
                $solution_items = array_merge([$solution_item], $solution_items);
            }

            if (!empty($solution_items)) {
                $contents[] = $this->uiFactory->item()->group($this->plugin->txt('task_solution'), $solution_items);
            }
        }


        // Output to the page

        $html = '';
        foreach ($contents as $content) {
            $html .= $this->renderer->render($content);
        }
        foreach ($modals as $id => $modal) {
            $this->tpl->addLightbox($this->renderer->render($modal), $id);
        }

        $this->tpl->setContent($this->fillPlaceholders($html));
    }


    /**
     * Start the Writer Web app
     */
    protected function startWriter()
    {
        if ($this->object->canWrite()) {
            $context = new WriterContext();
            $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());
            $service = new Service($context);
            $service->openFrontend();
        } else {
            $this->raisePermissionError();
        }
    }

    /**
     * Start the Writer Web app for review
     */
    protected function startWritingReview()
    {
        if ($this->object->canReviewWrittenEssay()) {
            $context = new WriterContext();
            $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());
            $service = new Service($context);
            $service->openFrontend();
        } else {
            $this->raisePermissionError();
        }
    }


    /**
     * Download a generated pdf from the processed written text
     */
    protected function downloadWriterPdf()
    {
        if ($this->object->canViewWriterScreen()) {
            $service = $this->localDI->getWriterAdminService($this->object->getId());
            $repoWriter = $this->localDI->getWriterRepo()->getWriterByUserIdAndTaskId($this->dic->user()->getId(), $this->object->getId());

            $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-writing.pdf';
            ilUtil::deliverData($service->getWritingAsPdf($this->object, $repoWriter), $filename, 'application/pdf');

            //$this->common_services->fileHelper()->deliverData($service->getWritingAsPdf($this->object, $repoWriter), $filename, 'application/pdf');
        } else {
            $this->raisePermissionError();
        }
    }

    /**
     * Download a generated pdf from the processed written text
     */
    protected function downloadCorrectedPdf()
    {
        if ($this->object->canReviewCorrectedEssay()) {
            $service = $this->localDI->getCorrectorAdminService($this->object->getId());
            $repoWriter = $this->localDI->getWriterRepo()->getWriterByUserIdAndTaskId($this->dic->user()->getId(), $this->object->getId());

            $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-correction.pdf';
            $this->common_services->fileHelper()->deliverData($service->getCorrectionAsPdf($this->object, $repoWriter), $filename, 'application/pdf');
        } else {
            $this->raisePermissionError();
        }
    }

    /**
     * @return ?int
     */
    protected function getResourceId(): ?int
    {
        if (isset($_GET["resource_id"]) && is_numeric($_GET["resource_id"])) {
            return (int) $_GET["resource_id"];
        }
        return null;
    }


    protected function downloadResourceFile()
    {
        $identifier = "";
        if (($resource_id = $this->getResourceId()) !== null) {
            $resource_admin = new ResourceAdmin($this->object->getId());
            $resource = $resource_admin->getResource($resource_id);

            if ($resource->getTaskId() != $this->object->getId()) {
                $this->raisePermissionError();
            }
            if (!$this->data->isResourceAvailable($resource, $this->task)) {
                $this->raisePermissionError();
            }

            if ($resource->getType() == Resource::RESOURCE_TYPE_FILE && is_string($resource->getFileId())) {
                $this->common_services->fileHelper()->deliverResource($resource->getFileId(), 'attachment');
            }
        }
    }

    protected function viewInstructions()
    {
        $this->toolbar->addComponent($this->uiFactory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        $content = [];
        if ($this->data->isInRange(time(), $this->data->dbTimeToUnix($this->task->getWritingStart()), null)) {
            $content[] = $this->uiFactory->panel()->standard(
                $this->plugin->txt('task_instructions'),
                $this->uiFactory->legacy($this->displayText($this->task->getInstructions()))
            );
        }

        $this->tpl->setContent($this->renderer->render($content));
    }

    protected function downloadInstructions()
    {
        if ($this->data->isInRange(time(), $this->data->dbTimeToUnix($this->task->getWritingStart()), null)) {
            $task_repo = $this->localDI->getTaskRepo();
            if (!empty($resource = $task_repo->getInstructionResource($this->object->getId()))) {
                $this->common_services->fileHelper()->deliverResource($resource->getFileId(), 'attachment');
            }
        }
    }
    
    protected function viewSolution()
    {
        $this->toolbar->addComponent($this->uiFactory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        $content = [];
        if ($this->object->canViewSolution()) {
            $content[] = $this->uiFactory->panel()->standard(
                $this->plugin->txt('task_solution'),
                $this->uiFactory->legacy($this->displayText($this->task->getSolution()))
            );
        }

        $this->tpl->setContent($this->renderer->render($content));
    }


    protected function downloadSolution()
    {
        if ($this->object->canViewSolution()) {
            $task_repo = $this->localDI->getTaskRepo();
            if (!empty($resource = $task_repo->getSolutionResource($this->object->getId()))) {
                $this->common_services->fileHelper()->deliverResource($resource->getFileId(), 'attachment');
            }
        }
    }
}
