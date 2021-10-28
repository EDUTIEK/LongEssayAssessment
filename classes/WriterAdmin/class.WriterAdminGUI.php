<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayTask\WriterAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\WriterAdmin\WriterAdminGUI: ilObjLongEssayTaskGUI
 */
class WriterAdminGUI extends BaseGUI
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
        $button->setUrl('');
        $button->setCaption('Teilnehmer hinzufügen', false);
        $button->setPrimary(false);
        $this->toolbar->addButtonInstance($button);


        $actions = array(
            "Alle" => "all",
            "Teilgenommen" => "",
            "Nicht Teilgenommen" => "",
            "Mit Zeitverlängerung" => "",
        );

        $aria_label = "change_the_currently_displayed_mode";
        $view_control = $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive("Alle");

        $item1 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Fred Neumann (fred.neumann)",''))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('usr', 'user', 'medium'))
            ->withProperties(array(
                "Abgabe-Status" => "noch nicht abgegeben",
                "Zeitverlängerung" => "10 min",
                "Letzte Speicherung" => "Heute, 13:50",

            ))
            ->withActions(
                $this->uiFactory->dropdown()->standard([
                    $this->uiFactory->button()->shy('Bearbeitung einsehen', '#'),
                    $this->uiFactory->button()->shy('Abgabe autorisieren', '#'),
                    $this->uiFactory->button()->shy('Zeit verlängern', '#'),
                    $this->uiFactory->button()->shy('Von Bearbeitung ausschließen', '#'),

                ]));

        $item2 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Matthias Kunkel (matthias.kunkel)", ''))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('usr', 'editor', 'medium'))
            ->withProperties(array(
                "Abgabe-Status" => "abgegeben",
                "Zeitverlängerung" => "keine",
                "Letzte Speicherung" => "Heute, 12:45",

            ))
            ->withActions(
                $this->uiFactory->dropdown()->standard([
                    $this->uiFactory->button()->shy('Bearbeitung einsehen', '#'),
                ]));

        $resources = $this->uiFactory->item()->group("Teilnehmer", array(
            $item1,
            $item2
        ));

        $this->tpl->setContent(

            $this->renderer->render($view_control) . '<br><br>' .
            $this->renderer->render($resources)

        );

     }
}