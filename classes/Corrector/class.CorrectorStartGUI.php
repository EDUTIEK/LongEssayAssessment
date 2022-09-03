<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Corrector;

use Edutiek\LongEssayService\Corrector\Service;
use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayTask\Data\CorrectionSettings;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 *Start page for correctors
 *
 * @package ILIAS\Plugin\LongEssayTask\Writer
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\Corrector\CorrectorStartGUI: ilObjLongEssayTaskGUI
 */
class CorrectorStartGUI extends BaseGUI
{
    /** @var CorrectorAdminService */
    protected $service;

    /** @var CorrectionSettings  */
    protected $settings;


    public function __construct(\ilObjLongEssayTaskGUI $objectGUI)
    {
        parent::__construct($objectGUI);
        $this->service = $this->localDI->getCorrectorAdminService($this->object->getId());
        $this->settings = $this->service->getSettings();
    }


    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showStartPage');
        switch ($cmd)
        {
            case 'showStartPage':
            case 'startCorrector':
            case 'finalizeCorrection':
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
        $canCorrect = $this->object->canCorrect();
        $readyItems = 0;

        $corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());
        foreach ($this->localDI->getCorrectorRepo()->getAssignmentsByCorrectorId($corrector->getId()) as $assignment) {
            $writer = $this->localDI->getWriterRepo()->getWriterById($assignment->getWriterId());
            $essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($assignment->getWriterId(), $this->settings->getTaskId());

            if (!empty($essay) && !empty($essay->getWritingExcluded())) {
                continue;
            }

            $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId(
                isset($essay) ? $essay->getId() : 0, $corrector->getId());

            $properties = [
                $this->plugin->txt('writing_status') => $this->data->formatWritingStatus($essay),
                $this->plugin->txt('correction_status') => $this->data->formatCorrectionStatus($essay),
                $this->plugin->txt('own_grading') => $this->data->formatCorrectionResult($summary),
                $this->plugin->txt('result') => $this->data->formatFinalResult($essay)
            ];
            foreach ($this->localDI->getCorrectorRepo()->getAssignmentsByWriterId($assignment->getWriterId()) as $otherAssignment) {
                if ($otherAssignment->getCorrectorId() != $corrector->getId()) {
                    $properties[$this->data->formatCorrectorPosition($otherAssignment)] = $this->data->formatCorrectorAssignment($otherAssignment);
                }
            }

            $title = $writer->getPseudonym();
            if ($canCorrect && $this->service->isCorrectionPossible($essay, $summary)) {
                $readyItems++;
                $this->ctrl->setParameter($this, 'writer_id', $assignment->getWriterId());
                $title = $this->uiFactory->link()->standard($title, $this->ctrl->getLinkTarget($this, 'startCorrector'));
            }

            $items[] = $this->uiFactory->item()->standard($title)
                ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
                ->withProperties($properties);
        }

        if ($canCorrect && $readyItems > 0) {
            $this->ctrl->clearParameters($this);
            //$this->toolbar->setFormAction($this->ctrl->getFormAction($this));
            $button = \ilLinkButton::getInstance();
            $button->setUrl($this->ctrl->getLinkTarget($this, "startCorrector"));
            $button->setCaption($this->plugin->txt('start_correction'), false);
            $button->setPrimary(true);
            $this->toolbar->addButtonInstance($button);
        }

//        $actions = array(
//            "Alle" => "all",
//            "Offen" => "",
//            "Vorläufig" => "",
//            "Korrigiert" => "",
//            "Große Abweichung" => "",
//        );

//        $aria_label = "change_the_currently_displayed_mode";
//        $view_control = $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive("Alle");
//
//        $result = $this->uiFactory->item()->group("", [
//            $this->uiFactory->item()->standard("Korrekturstatus")
//                ->withDescription("")
//                ->withProperties(array(
//                    "Bewertete Abgaben:" => "1",
//                    "Offene Abgaben:" => "1",
//                    "Durchschnittsnote:" => "10"))
//        ]);

        if (!empty($items)) {
            $essays = $this->uiFactory->item()->group($this->plugin->txt('assigned_writings'), $items);
            $this->tpl->setContent(
//            $this->renderer->render($result) . '<br>'.
//            $this->renderer->render($view_control) . '<br><br>' .
                $this->renderer->render($essays));
        }
        else {
            ilUtil::sendInfo($this->plugin->txt('message_no_correction_items'));
        }
     }


    /**
     * Start the Writer Web app
     */
    protected function startCorrector()
    {
        $context = new CorrectorContext();
        $context->init((string) $this->dic->user()->getId(), (string) $this->object->getRefId());

        $params = $this->request->getQueryParams();
        if (!empty($params['writer_id'])) {
            $context->selectWriterId((int) $params['writer_id']);
        }
        $service = new Service($context);
        $service->openFrontend();
    }

    protected function finalizeCorrection()
    {

    }

}