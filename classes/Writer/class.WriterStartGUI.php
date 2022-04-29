<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Writer;

use Edutiek\LongEssayService\Exceptions\ContextException;
use Edutiek\LongEssayService\Writer\Service;
use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
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

    /** @var Essay */
    protected $essay;

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $taskRepo = $this->localDI->getTaskRepo();
        $essayRepo = $this->localDI->getEssayRepo();

        $this->task = $taskRepo->getTaskSettingsById($this->object->getId());
        $this->essay = $essayRepo->getEssayByWriterIdAndTaskId($this->dic->user()->getId(), $this->object->getId());
        if (!isset($this->essay)) {
            $this->essay = new Essay();
            $this->essay->setWriterId($this->dic->user()->getId());
            $this->essay->setTaskId($this->object->getId());
        }

        $cmd = $this->ctrl->getCmd('showStartPage');
        switch ($cmd)
        {
            case 'showStartPage':
            case 'startWriter':
            case 'processText':
            case 'downloadWriterPdf':
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

        // Toolbar

        $button = \ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'startWriter'));
        $button->setCaption($this->plugin->txt('start_writing'), false);
        $button->setPrimary(true);
        $this->toolbar->addButtonInstance($button);

//        $button = \ilLinkButton::getInstance();
//        $button->setUrl($this->ctrl->getLinkTarget($this, 'processText'));
//        $button->setCaption($this->plugin->txt('process_text'), false);
//        $this->toolbar->addButtonInstance($button);

        // Instructions

        $contents[] = $this->uiFactory->item()->group($this->plugin->txt('task_instructions'),
            [$this->uiFactory->item()->standard($this->lng->txt('description'))
                ->withDescription($this->task->getInstructions())
                ->withProperties(array(
                    $this->plugin->txt('writing_period') => $this->plugin->formatPeriod(
                        $this->task->getWritingStart(), $this->task->getWritingEnd()
                    )
                ))]);

        // Resources

        $item1 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Informationen zur Prüfung",''))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('file', 'File', 'medium'))
            ->withProperties(array(
                "Filename" => "Informationen.pdf",
                "Verfügbar" => "vorab"));

        $item2 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Bürgerliches Gesetzbuch", ''))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('webr', 'Link', 'medium'))
            ->withProperties(array(
                "Webseite" => "https://www.gesetze-im-internet.de/bgb/",
                "Verfügbar" => "vorab"));

//        $contents[] = $this->uiFactory->item()->group("Material", array(
//            $item1,
//            $item2
//        ));

        // Result

        $result_actions = [];

        // todo respect review period
        if (true) {
            $submission_page = $this->uiFactory->modal()->lightboxTextPage((string) $this->essay->getProcessedText(), $this->plugin->txt('submission'));
            $submission_modal = $this->uiFactory->modal()->lightbox($submission_page);
            $modals[$submission_modal->getShowSignal()->getId()] = $submission_modal;

            $result_actions[] = $this->uiFactory->button()->shy($this->plugin->txt('view_submission'), '')
                ->withOnClick($submission_modal->getShowSignal());

            $result_actions[] = $this->uiFactory->button()->shy($this->plugin->txt('download_submission'),
            $this->ctrl->getLinkTarget($this, 'downloadWriterPdf'));

        }

        $result_item = $this->uiFactory->item()->standard($this->plugin->txt('not_specified'))
            ->withDescription("")
            ->withProperties(array(
                $this->plugin->txt('review_period') => $this->plugin->formatPeriod(
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
         $di = LongEssayTaskDI::getInstance();

         // ensure that an essay record exists
         $essay = $di->getEssayRepo()->getEssayByWriterIdAndTaskId((string) $this->dic->user()->getId(), (string) $this->object->getId());
         if (!isset($essay)) {
             $essay = new Essay();
             $essay->setWriterId((string) $this->dic->user()->getId());
             $essay->setTaskId((string) $this->object->getId());
             $essay->setUuid($essay->generateUUID4());
             $essay->setRawTextHash('');
             $di->getEssayRepo()->createEssay($essay);
         }

         $context = new WriterContext();
         $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());
         $service = new Service($context);
         $service->openFrontend();
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
         $context = new WriterContext();
         $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());
         $service = new Service($context);

         $filename = 'task' . $this->object->getId() . '_user' . $this->dic->user()->getId(). '.pdf';

         ilUtil::deliverData($service->getProcessedTextAsPdf(), $filename, 'application/pdf');
     }
}