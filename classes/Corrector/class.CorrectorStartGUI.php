<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Corrector;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
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
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
        $button = \ilLinkButton::getInstance();
        $button->setUrl('./Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask/lib/corrector/index.html');
        $button->setCaption('Korrektur starten', false);
        $button->setPrimary(true);
        $this->toolbar->addButtonInstance($button);


        $actions = array(
            "Alle" => "all",
            "Bestanden" => "",
            "Nicht Bestanden" => "",
            "Große Abweichung" => "",
        );

        $aria_label = "change_the_currently_displayed_mode";
        $view_control = $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive("Alle");

        $result = $this->uiFactory->item()->group("", [
            $this->uiFactory->item()->standard("´Korrekturstatus")
                ->withDescription("")
                ->withProperties(array(
                    "Bewertete Abgaben" => "1",
                    "Offene Abgaben:" => "1",
                    "Durchschnittsnote" => "10"))
        ]);



        $item1 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Fred Neumann (fred.neumann)",'./Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask/lib/corrector/index.html'))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
            ->withProperties(array(
                "Abgabe-Status" => "abgegeben",
                "Korrektur-Status" => "vorläufig",
                "Punkte:" => 10,
                "Notenstufe" => "bestanden",
                "Zweitkorrektor: Volker Reuschenback (volker.reuschenbach)"
            ))
            ->withActions(
                $this->uiFactory->dropdown()->standard([
                    $this->uiFactory->button()->shy('Korrektur beearbeiten', '#'),
                    $this->uiFactory->button()->shy('Korrektur finalisieren', '#')
                ]));

        $item2 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Matthias Kunkel (matthias.kunkel)", ''))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'editor', 'medium'))
            ->withProperties(array(
                "Abgabe-Status" => "abgegeben",
                "Korrektur-Status" => "noch nicht begonnen",
                "Erstkorrektor Armin Laschet (armin.laschet)"
            ))
            ->withActions(
                $this->uiFactory->dropdown()->standard([
                    $this->uiFactory->button()->shy('Korrektur beearbeiten', '#'),
                ]));

        $resources = $this->uiFactory->item()->group("Zugeteilte Abgaben", array(
            $item1,
            $item2
        ));

        $this->tpl->setContent(

            $this->renderer->render($result) . '<br>'.
            $this->renderer->render($view_control) . '<br><br>' .
            $this->renderer->render($resources)

        );

     }
}