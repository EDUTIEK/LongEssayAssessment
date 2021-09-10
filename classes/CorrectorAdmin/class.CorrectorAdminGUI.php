<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\CorrectorAdmin;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayTask\CorrectorAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminGUI: ilObjLongEssayTaskGUI
 */
class CorrectorAdminGUI extends BaseGUI
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
        $button->setCaption('Korrektoren zuweisen', false);
        $button->setPrimary(true);
        $this->toolbar->addButtonInstance($button);



        $actions = array(
            "Alle" => "all",
            "Korrigiert" => "",
            "Noch nicht korrigiert" => "",
            "Stichentscheid gefordert" => "",
        );

        $aria_label = "change_the_currently_displayed_mode";
        $view_control = $this->uiFactory->viewControl()->mode($actions, $aria_label)->withActive("Alle");

        $item1 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Fred Neumann (fred.neumann)",''))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'user', 'medium'))
            ->withProperties(array(
                "Erstkorrektor" => "Volker Reuschenbach",
                "Zweitkorrektor" => "Armin Laschet",
                "Status" => "Stichentscheid gefordert",

            ))
            ->withActions(
                $this->uiFactory->dropdown()->standard([
                    $this->uiFactory->button()->shy('Korrektur einsehen', '#'),
                    $this->uiFactory->button()->shy('Korrektorenzuweung ändern', '#'),
                    $this->uiFactory->button()->shy('Stichentscheid', '#'),

                ]));

        $item2 = $this->uiFactory->item()->standard($this->uiFactory->link()->standard("Matthias Kunkel (matthias.kunkel)", ''))
            ->withLeadIcon($this->uiFactory->symbol()->icon()->standard('adve', 'editor', 'medium'))
            ->withProperties(array(
                "Erstkorrektor" => "Armin Laschet",
                "Zweitkorrektor" => "Volker Reuschenbach",
                "Status" => "noch nicht korrigiert",

            ))
            ->withActions(
                $this->uiFactory->dropdown()->standard([
                    $this->uiFactory->button()->shy('Korrektur einsehen', '#'),
                    $this->uiFactory->button()->shy('Korrektorenzuweung ändern', '#'),
                ]));

        $resources = $this->uiFactory->item()->group("Zu korrigierende Abgaben", array(
            $item1,
            $item2
        ));

        $this->tpl->setContent(

            $this->renderer->render($view_control) . '<br><br>' .
            $this->renderer->render($resources)

        );

     }
}