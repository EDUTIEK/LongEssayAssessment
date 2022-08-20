<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Writer;

use Edutiek\LongEssayService\Exceptions\ContextException;
use Edutiek\LongEssayService\Writer\Service;
use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\Resource;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\Plugin\LongEssayTask\Task\ResourceAdmin;
use ILIAS\UI\Factory;
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
			case 'downloadResourceFile':
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
                if (!empty($essay->getWritingAuthorized())) {
                    ilUtil::sendInfo($this->plugin->txt('message_writing_authorized'));
                }
                if (!empty($essay->getWritingExcluded())) {
                    ilUtil::sendInfo($this->plugin->txt('message_writing_excluded'));
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
        }

//        $button = \ilLinkButton::getInstance();
//        $button->setUrl($this->ctrl->getLinkTarget($this, 'processText'));
//        $button->setCaption($this->plugin->txt('process_text'), false);
//        $this->toolbar->addButtonInstance($button);

        // Instructions

        $writing_end = null;
        if (!empty($this->task->getWritingEnd())) {
            $writing_end = $this->data->unixTimeToDb(
                $this->data->dbTimeToUnix($this->task->getWritingEnd()) + $extension);
        }

        $contents[] = $this->uiFactory->item()->group($this->plugin->txt('task_instructions'),
            [$this->uiFactory->item()->standard($this->lng->txt('description'))
                ->withDescription($this->task->getDescription() !== null ? $this->task->getDescription() : "")
                ->withProperties(array(
                    $this->plugin->txt('writing_period') => $this->data->formatPeriod(
                        $this->task->getWritingStart(), $writing_end
                    )
                ))]);


        // Resources

		$repo = LongEssayTaskDI::getInstance()->getTaskRepo();
		$writing_resources = [];
		/** @var Resource $resource */
		foreach ($repo->getResourceByTaskId($this->object->getId()) as $resource) {
			if ($resource->getAvailability() == Resource::RESOURCE_AVAILABILITY_BEFORE) {

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
							"Verfügbar" => $resource->getAvailability()));
				}
				else {
					$item = $this->uiFactory->item()->standard($this->uiFactory->link()->standard($resource->getTitle(), $resource->getUrl()))
						->withLeadIcon($this->uiFactory->symbol()->icon()->standard('webr', 'Link', 'medium'))
						->withProperties(array(
							"Webseite" => $resource->getUrl(),
							"Verfügbar" => $resource->getAvailability()));
				}
				$writing_resources[] = $item;
			}
		}
        if (!empty($writing_resources)) {
            $contents[] = $this->uiFactory->item()->group("Material", $writing_resources);
        }

        // Result

        $result_actions = [];

        if ($this->object->canReview() && isset($essay)) {

            $submission_page = $this->uiFactory->modal()->lightboxTextPage(
                (string) $essay->getProcessedText(), $this->plugin->txt('submission'));
            $submission_modal = $this->uiFactory->modal()->lightbox($submission_page);
            $modals[$submission_modal->getShowSignal()->getId()] = $submission_modal;

            $result_actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_submission'), '')
                ->withOnClick($submission_modal->getShowSignal());

            if ($this->object->canReview()) {
                $result_actions[] = $this->uiFactory->button()->shy($this->plugin->txt('download_corrected_submission'),
                    $this->ctrl->getLinkTarget($this, 'downloadWriterPdf'));
            }
        }

        $result_item = $this->uiFactory->item()->standard($this->data->formatFinalResult($essay))
            ->withDescription("")
            ->withProperties(array(
                $this->plugin->txt('review_period') => $this->data->formatPeriod(
                    $this->task->getReviewStart(), $this->task->getReviewEnd()
                )));
        if (!empty($result_actions)) {
            $result_item = $result_item->withActions($this->uiFactory->dropdown()->standard($result_actions));
        }
        $contents[] = $this->uiFactory->item()->group($this->plugin->txt('result'), [$result_item]);


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
         if ($this->object->canReview()) {
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
}