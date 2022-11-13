<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Writer;

use Edutiek\LongEssayService\Exceptions\ContextException;
use Edutiek\LongEssayService\Writer\Service;
use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\Resource;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\Plugin\LongEssayTask\Task\ResourceAdmin;
use \ilUtil;

/**
 * Start page for writers
 *
 * @package ILIAS\Plugin\LongEssayTask\Writer
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\Writer\WriterStartGUI: ilObjLongEssayTaskGUI
 */
class WriterStartGUI extends BaseGUI
{
    /** @var TaskSettings */
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
        switch ($cmd)
        {
            case 'showStartPage':
            case 'startWriter':
            case 'processText':
            case 'downloadWriterPdf':
            case 'downloadCorrectedPdf':
			case 'downloadResourceFile':
            case 'viewInstructions':
            case 'viewSolution':
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
		global $DIC;
        $contents = [];
        $modals = [];
        $essay = null;

        $extension = 0;
        if (!empty($writer = $this->localDI->getWriterRepo()->getWriterByUserId(
            $this->dic->user()->getId(), $this->task->getTaskId()))) {
            if (!empty($essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId(
                $writer->getId(), $this->task->getTaskId()
            ))) {
                if (!empty($essay->getWritingExcluded())) {
                    ilUtil::sendInfo($this->plugin->txt('message_writing_excluded'));
                }
                elseif (!empty($essay->getWritingAuthorized())) {
                    $message = $this->plugin->txt('message_writing_authorized');
                    $review_message = '';
                    $back_link = '';
                    if (!empty($this->task->getReviewStart()) || !empty($this->task->getReviewEnd())) {
                        $review_message = '<p>'. sprintf($this->plugin->txt('message_review_period'),
                                $this->data->formatPeriod($this->task->getReviewStart(), $this->task->getReviewEnd())) . '</p>';
                    }
                    if (isset($this->params['returned'])) {
                        $back_url = \ilLink::_getLink($this->dic->repositoryTree()->getParentId($this->object->getRefId()));
                        $back_text = $this->plugin->txt('message_writing_authorized_link');
                        $back_link = '<p><a href="'.$back_url.'">'.$back_text.'</a></p>';
                    }
                    ilUtil::sendInfo($message. $review_message. $back_link);
                }
            }

            if (!empty($timeExtension = $this->localDI->getWriterRepo()->getTimeExtensionByWriterId(
                $writer->getId(), $this->task->getTaskId()))) {
                $extension = $timeExtension->getMinutes() * 60;
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
                ilUtil::sendInfo($this->plugin->txt('message_writing_returned_interrupted'));
            }
        }

        // Instructions

        $writing_end = null;
        if (!empty($this->task->getWritingEnd())) {
            $writing_end = $this->data->unixTimeToDb(
                $this->data->dbTimeToUnix($this->task->getWritingEnd()) + $extension);
        }

        $inst_parts = [];
        if (!empty($this->task->getDescription())) {
            $inst_parts[] = $this->task->getDescription();
        }
        if (!empty($this->task->getInstructions())
            && $this->data->isInRange(time(), $this->data->dbTimeToUnix($this->task->getWritingStart()), null)) {
            $inst_parts[] = $this->renderer->render($this->uiFactory->button()->standard($this->plugin->txt('view_instructions'),
                $this->ctrl->getLinkTarget($this, 'viewInstructions')));
        }

        $contents[] = $this->uiFactory->item()->group($this->plugin->txt('task_instructions'),
            [$this->uiFactory->item()->standard($this->lng->txt('description'))
                ->withDescription(implode('<br>', $inst_parts))
                ->withProperties(array(
                    $this->plugin->txt('writing_period') => $this->data->formatPeriod(
                        $this->task->getWritingStart(), $writing_end
                    )
                ))]);


        // Resources

		$repo = LongEssayTaskDI::getInstance()->getTaskRepo();
		$writing_resources = [];
        $solution_items = [];

		/** @var Resource $resource */
		foreach ($repo->getResourceByTaskId($this->object->getId()) as $resource) {
			if ($this->data->isResourceAvailable($resource, $this->task)) {

				if ($resource->getType() == Resource::RESOURCE_TYPE_FILE) {
					$resource_file = $DIC->resourceStorage()->manage()->find($resource->getFileId());
					$revision = $DIC->resourceStorage()->manage()->getCurrentRevision($resource_file);

					$this->ctrl->setParameter($this, "resource_id", $resource->getId());

					$item = $this->uiFactory->item()->standard(
						$this->uiFactory->link()->standard(
							$resource->getTitle(),
							$this->ctrl->getLinkTarget($this, "downloadResourceFile"))
					)
						->withLeadIcon($this->uiFactory->symbol()->icon()->standard('file', 'File', 'medium'))
						->withProperties(array(
							"Filename" => $revision->getInformation()->getTitle(),
							"Verfügbar" => $this->plugin->txt('resource_availability_' . $resource->getAvailability())));
				}
				else {
					$item = $this->uiFactory->item()->standard($this->uiFactory->link()->standard($resource->getTitle(), $resource->getUrl()))
						->withLeadIcon($this->uiFactory->symbol()->icon()->standard('webr', 'Link', 'medium'))
						->withProperties(array(
							"Webseite" => $resource->getUrl(),
							"Verfügbar" => $this->plugin->txt('resource_availability_' . $resource->getAvailability())));
				}

                if ($resource->getAvailability() == Resource::RESOURCE_AVAILABILITY_AFTER) {
                    $solution_items[] = $item;
                }
                else {
                     $writing_resources[] = $item;
                }

			}
		}
        if (!empty($writing_resources)) {
            $contents[] = $this->uiFactory->item()->group("Material", $writing_resources);
        }

        // Result

        $result_actions = [];
        if (isset($essay)) {

            $result_actions[] = $this->uiFactory->button()->standard($this->plugin->txt('download_written_submission'),
                $this->ctrl->getLinkTarget($this, 'downloadWriterPdf'));

            if ($this->object->canReview()) {
                $result_actions[] = $this->uiFactory->button()->standard($this->plugin->txt('download_corrected_submission'),
                    $this->ctrl->getLinkTarget($this, 'downloadCorrectedPdf'));
            }
        }

        $actions_html = '';
        foreach ($result_actions as $action) {
            $actions_html .= $this->renderer->render($action);
        }

        $result_item = $this->uiFactory->item()->standard($this->data->formatFinalResult($essay))
            ->withDescription($actions_html)
            ->withProperties(array(
                $this->plugin->txt('review_period') => $this->data->formatPeriod(
                    $this->task->getReviewStart(), $this->task->getReviewEnd()
                )));


        $contents[] = $this->uiFactory->item()->group($this->plugin->txt('result'), [$result_item]);


        // Solution
        if ($this->object->canReview()) {
            if (!empty($this->task->getSolution())) {

                $solution_button = $this->renderer->render($this->uiFactory->button()->standard($this->plugin->txt('view_solution'),
                    $this->ctrl->getLinkTarget($this, 'viewSolution')));

                $solution_item = $this->uiFactory->item()->standard($solution_button);
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

        $this->tpl->setContent($html);
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
         }
         else {
             $this->raisePermissionError();
         }
     }

    /**
     * Process the written text
     * (just to test the html processing - will be done automatically when written text is saved)
     * @throws ContextException
     */
     protected function processText()
     {
         $context = new WriterContext();
         $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());
         $service = new Service($context);
         $service->processWrittenText();
         $this->ctrl->redirect($this);
     }

    /**
     * Download a generated pdf from the processed written text
     */
     protected function downloadWriterPdf()
     {
         if ($this->object->canViewWriterScreen()) {

             $context = new WriterContext();
             $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());
             $service = new Service($context);

             $filename = 'task' . $this->object->getId() . '_user' . $this->dic->user()->getId(). '.pdf';
             ilUtil::deliverData($service->getProcessedTextAsPdf(), $filename, 'application/pdf');
         }
         else {
             $this->raisePermissionError();
         }
     }

     /**
          * Download a generated pdf from the processed written text
          */
     protected function downloadCorrectedPdf()
     {
         if ($this->object->canReview()) {
             $service = $this->localDI->getCorrectorAdminService($this->object->getId());
             $repoTask = $this->localDI->getTaskRepo()->getTaskSettingsById($this->object->getId());
             $repoWriter = $this->localDI->getWriterRepo()->getWriterByUserId($this->dic->user()->getId(), $this->object->getId());

             $filename = 'task' . $this->object->getId() . '_user' . $this->dic->user()->getId(). '.pdf';
             ilUtil::deliverData($service->getCorrectionAsPdf($this->object, $repoTask, $repoWriter), $filename, 'application/pdf');
         }
         else {
             $this->raisePermissionError();
         }
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


	protected function downloadResourceFile()
	{
		global $DIC;
		$identifier = "";
		if (($resource_id = $this->getResourceId()) !== null) {
			$resource_admin = new ResourceAdmin($this->object->getId());
			$resource = $resource_admin->getResource($resource_id);

			if ($resource->getType() == Resource::RESOURCE_TYPE_FILE && is_string($resource->getFileId())) {
				$identifier = $resource->getFileId();
			}

			if ($resource->getTaskId() != $this->object->getId()) {
				ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
				$this->ctrl->redirect($this, "showItems");
			}
		} else {
			// TODO: Error no resource ID in GET
		}

		$resource = $DIC->resourceStorage()->manage()->find($identifier);

		if ($resource !== null) {
			$DIC->resourceStorage()->consume()->download($resource)->run();
		} else {
			// TODO: Error resource not in Storage
		}
	}

    protected function viewInstructions()
    {
        $this->toolbar->addComponent( $this->uiFactory->button()->standard($this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')));

        $content = [];
        if ($this->data->isInRange(time(), $this->data->dbTimeToUnix($this->task->getWritingStart()), null)) {
            $content[] = $this->uiFactory->panel()->standard($this->plugin->txt('task_instructions'), $this->uiFactory->legacy($this->task->getInstructions()));
        }

        $this->tpl->setContent($this->renderer->render($content));
    }

    protected function viewSolution()
    {
        $this->toolbar->addComponent( $this->uiFactory->button()->standard($this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')));

        $content = [];
        if ($this->object->canReview()) {
            $content[] = $this->uiFactory->panel()->standard($this->plugin->txt('task_solution'), $this->uiFactory->legacy($this->task->getSolution()));
        }

        $this->tpl->setContent($this->renderer->render($content));
    }
}