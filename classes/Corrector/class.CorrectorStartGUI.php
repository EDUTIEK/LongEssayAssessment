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
        $this->service = $this->object->getCorrectorAdminService();
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
        // todo: this should be done in the corrector admin GUI
        $this->service->addUserAsCorrector($this->dic->user()->getId());
        $this->service->assignMissingCorrectors();

        $corrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());
        foreach ($this->localDI->getCorrectorRepo()->getAssignmentsByCorrectorId($corrector->getId()) as $assignment) {
            $writer = $this->localDI->getWriterRepo()->getWriterById($assignment->getWriterId());

            $properties = [];
            if ($essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($assignment->getWriterId(), $this->settings->getTaskId())) {
                $properties = [
                    "Abgabe-Status:" => empty($essay->getWritingAuthorized()) ? 'nicht abgegeben' : 'abgegeben',
                    "Korrektur-Status:" => "vorläufig",
                    "Punkte" => $essay->getFinalPoints(),
                    "Notenstufe" => $essay->getFinalGradeLevel()
                ];
            }
            foreach ($this->localDI->getCorrectorRepo()->getAssignmentsByWriterId($assignment->getWriterId()) as $otherAssignment) {
                if ($otherAssignment->getCorrectorId() != $corrector->getId()) {
                    switch ($otherAssignment->getPosition()) {
                        case 0:
                            $label = "Erstkorrektor/in";
                            break;
                        case 1:
                            $label = "Zweitkorrektor/in";
                            break;
                        default:
                            $label = "Weiterer Korrektor/innen";
                            break;
                    }
                    $otherCorrector = $this->localDI->getCorrectorRepo()->getCorrectorById($otherAssignment->getCorrectorId());
                    $properties[$label] = \ilObjUser::_lookupFullname($otherCorrector->getUserId())
                        . ' ('. \ilObjUser::_lookupLogin($otherCorrector->getUserId()) . ')';

                }
            }


            $this->ctrl->setParameter($this, 'writer_id', $assignment->getWriterId());
            $items[] = $this->uiFactory->item()->standard($this->uiFactory->link()->standard($writer->getPseudonym(),$this->ctrl->getLinkTarget($this, 'startCorrector')))
                ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
                ->withProperties($properties)
                ->withActions(
                    $this->uiFactory->dropdown()->standard([
                        $this->uiFactory->button()->shy('Korrektur bearbeiten', $this->ctrl->getLinkTarget($this, 'startCorrector')),
                        $this->uiFactory->button()->shy('Korrektur finalisieren',  $this->ctrl->getLinkTarget($this, 'finalizeCorrection'))
                    ]));

        }

        if (!empty($items)) {
            $this->ctrl->clearParameters($this);
            $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
            $button = \ilLinkButton::getInstance();
            $button->setUrl($this->ctrl->getLinkTarget($this, "startCorrector"));
            $button->setCaption('Korrektur starten', false);
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

        $essays = $this->uiFactory->item()->group("Zugeteilte Abgaben", $items);
        $this->tpl->setContent(
//            $this->renderer->render($result) . '<br>'.
//            $this->renderer->render($view_control) . '<br><br>' .
            $this->renderer->render($essays)

        );
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