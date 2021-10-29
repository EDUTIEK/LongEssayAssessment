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
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\WriterAdmin\WriterAdminLogGUI: ilObjLongEssayTaskGUI
 */
class WriterAdminLogGUI extends BaseGUI
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
        $modal1 = $this->uiFactory->modal()->roundtrip('Notiz hinzufügen', [
            $this->uiFactory->input()->field()->text('Titel')->withRequired(true),
            $this->uiFactory->input()->field()->textarea('Text')->withRequired(true)
        ])->withActionButtons([$this->uiFactory->button()->primary('Hinzufügen','#')]);
        $button1 = $this->uiFactory->button()->standard('Notiz hinzufügen', '#')
            ->withOnClick($modal1->getShowSignal());
        $this->toolbar->addComponent($button1);

        $modal2 = $this->uiFactory->modal()->roundtrip('Benachrichtigung senden', [
            $this->uiFactory->input()->field()->text('Titel')->withRequired(true),
            $this->uiFactory->input()->field()->textarea('Text')->withRequired(true)
        ])->withActionButtons([$this->uiFactory->button()->primary('Sende','#')]);
        $button2 = $this->uiFactory->button()->standard('Benachrichtigung senden', '#')
            ->withOnClick($modal2->getShowSignal());
        $this->toolbar->addComponent($button2);

        $item1 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Hinweis zur Angabe",''))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('coms', 'coms', 'medium'))
            ->withDescription('In Zeile 3 hat sich ein Fehler eingeschlichen. Es muss "Kauf" statt "Verkauf heißen"')
            ->withProperties(array(
                "Typ" => "Benachrichtigung",
                "Gesendet" => "Heute, 13:50",
                "Empfänger" => "Alle"

            ));

        $item2 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Teilnehmer A ohne Studentenausweis", ''))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('nots', 'notes', 'medium'))
            ->withDescription('Personalausweis vorgelegt.')
            ->withProperties(array(
                "Typ" => "Notiz",
                "Eingetragen" => "Heute, 14:10",
            ));

        $resources = $this->uiFactory->item()->group("Protokolleinträge", array(
            $item1,
            $item2
        ));

        $this->tpl->setContent(
            $this->renderer->render([$resources, $modal1, $modal2])

        );

     }
}