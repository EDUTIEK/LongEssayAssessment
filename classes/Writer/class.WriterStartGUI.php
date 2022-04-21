<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Writer;

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

        $cmd = $this->ctrl->getCmd('showStartPage');
        switch ($cmd)
        {
            case 'showStartPage':
            case 'startWriter':
            case 'processText':
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
        $button = \ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'startWriter'));
        $button->setCaption($this->plugin->txt('start_writing'), false);
        $button->setPrimary(true);
        $this->toolbar->addButtonInstance($button);

        $button = \ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'processText'));
        $button->setCaption($this->plugin->txt('process_text'), false);
        $this->toolbar->addButtonInstance($button);


        $description = $this->uiFactory->item()->group($this->plugin->txt('task_instructions'),
            [$this->uiFactory->item()->standard($this->lng->txt('description'))
                ->withDescription($this->task->getInstructions())
                ->withProperties(array(
                    $this->plugin->txt('writing_period') => $this->plugin->formatPeriod(
                        $this->task->getWritingStart(), $this->task->getWritingEnd()
                    )
                ))]);

        // ressources
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

        $resources = $this->uiFactory->item()->group("Material", array(
            $item1,
            $item2
        ));

        $submission_page = $this->uiFactory->modal()->lightboxTextPage((string) $this->essay->getProcessedText(), $this->plugin->txt('submission'));
        $submission_modal = $this->uiFactory->modal()->lightbox($submission_page);

        // todo respect review period
        // todo: insert real result
        $result = $this->uiFactory->item()->group($this->plugin->txt('result'), [
            $this->uiFactory->item()->standard($this->plugin->txt('not_specified'))
                ->withDescription("")
                ->withProperties(array(
                $this->plugin->txt('review_period') => $this->plugin->formatPeriod(
                    $this->task->getReviewStart(), $this->task->getReviewEnd()
                )))
                ->withActions($this->uiFactory->dropdown()->standard([
                    $this->uiFactory->button()->shy($this->plugin->txt('view_submission'), '')
                    ->withOnClick($submission_modal->getShowSignal()),
                    //$this->uiFactory->button()->shy('Bewertung herunterladen', '')
                    ]))
            ]);



        $this->tpl->setContent(
            $this->renderer->render($description) .
            // $this->renderer->render($resources) .
            $this->renderer->render($result) .
            $this->renderer->render($submission_modal)
        );

     }


    /**
     * Start the Writer Web app
     */
     protected function startWriter()
     {
        global $DIC;

         $di = LongEssayTaskDI::getInstance();

         // ensure that an essay record exists
         $essay = $di->getEssayRepo()->getEssayByWriterIdAndTaskId((string) $DIC->user()->getId(), (string) $this->object->getId());
         if (!isset($essay)) {
             $essay = new Essay();
             $essay->setWriterId((string) $DIC->user()->getId());
             $essay->setTaskId((string) $this->object->getId());
             $essay->setUuid($essay->generateUUID4());
             $essay->setRawTextHash('');
             $di->getEssayRepo()->createEssay($essay);
         }

         $context = new WriterContext();
         $context->init((string) $DIC->user()->getId(), (string) $this->object->getRefId());
         $service = new Service($context);
         $service->openFrontend();
     }

     protected function processText()
     {
         global $DIC;

         $context = new WriterContext();
         $context->init((string) $DIC->user()->getId(), (string) $this->object->getRefId());
         $service = new Service($context);
         $service->processWrittenText();

         $this->ctrl->redirect($this);
     }
}