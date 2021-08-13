<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\ActiveRecordDummy;
use ILIAS\Plugin\LongEssayTask\Data\EditorSettings;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayTask\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\Task\ResourcesAdminGUI: ilObjLongEssayTaskGUI
 */
class ResourcesAdminGUI extends BaseGUI
{
    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showItems');
        switch ($cmd)
        {
            case 'showItems':
            case "editItem":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Get the Table Data
     */
    protected function getItemData()
    {
        return [
            [
                'headline' => 'Informationen zur Klausur',
                'subheadline' => 'Hier finden Sie wichtige Informationen zum Ablauf der Klausur',
                'important' => [
                    'Verfügbar' => 'vorab',
                     $this->renderer->render($this->uiFactory->link()->standard('Informationen.pdf','#'))
                ],
            ],
            [
                'headline' => 'BGB',
                'subheadline' => 'Online-Ausgabe des Bürgerlichen Gesetzbuchs',
                'type' => 'url',
                'important' => [
                    'Verfügbar' => 'vorab',
                    $this->renderer->render($this->uiFactory->link()->standard('https://www.gesetze-im-internet.de/bgb/','https://www.gesetze-im-internet.de/bgb/'))
                ],
            ],
            [
                'headline' => 'Vertragsentwurf',
                'subheadline' => 'Der zu begutachtende Vertragsentwurf',
                'important' => [
                    'Verfügbar' => 'nach Start',
                     $this->renderer->render($this->uiFactory->link()->standard('Vertrag.pdf','#'))
                ],
            ],

        ];
    }

    /**
     * Show the items
     */
    protected function showItems()
    {
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
        $button = \ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'editItem'));
        $button->setCaption($this->plugin->txt('add_resource'), false);
        $this->toolbar->addButtonInstance($button);


        $ptable = $this->uiFactory->table()->presentation(
            'Materialien zur Aufgabe',
            [],
            function (
                PresentationRow $row,
                array $record,
                Factory $ui_factory,
                $environment) {
                return $row
                    ->withHeadline($record['headline'])
                    //->withSubheadline($record['subheadline'])
                    ->withImportantFields($record['important'])
                    ->withContent($ui_factory->listing()->descriptive(['Beschreibung' => $record['subheadline']]))
                    ->withFurtherFieldsHeadline('')
                    ->withFurtherFields($record['important'])
                    ->withAction(
                        $ui_factory->dropdown()->standard([
                            $ui_factory->button()->shy($this->lng->txt('edit'), '#'),
                            $ui_factory->button()->shy($this->lng->txt('delete'), '#')
                            ])
                            ->withLabel($this->lng->txt("actions"))
                    )
                    ;
            }
        );

        $this->tpl->setContent($this->renderer->render($ptable->withData($this->getItemData())));
    }


    /**
     * Edit and save the settings
     */
    protected function editItem()
    {
        $params = $this->request->getQueryParams();
        if (!empty($params['id'])) {
            $record = ActiveRecordDummy::findOrGetInstance($params['id']);
            if ($record->getTaskId() != $this->object->getId()) {
                $this->raisePermissionError();
            }
            $section_title = $this->plugin->txt('Material bearbeiten');
        }
        else {
            $record = new ActiveRecordDummy();
            $record->setTaskId($this->object->getId());
            $section_title = $this->plugin->txt('Material hinzufügen');
        }

        $factory = $this->uiFactory->input()->field();

        $sections = [];

        // Object
        $fields = [];
        $fields['title'] = $factory->text($this->lng->txt("title"))
            ->withRequired(true)
            ->withValue($record->getStringDummy());

        $fields['description'] = $factory->textarea($this->lng->txt("description"))
            ->withValue($record->getStringDummy());


        $group1 = $this->uiFactory ->input()->field()->group(
            [
                "file" => $this->uiFactory->input()->field()->file(new \ilUIDemoFileUploadHandlerGUI(), "Datei hochladen")
                ->withAcceptedMimeTypes(['application/pdf']),
            ],
            "Datei"
        );
        $group2 = $this->uiFactory->input()->field()->group(
            [
                "url" =>  $this->uiFactory->input()->field()->text('Url')
            ],
            "Weblink"
        );

        $fields['type'] = $this->uiFactory->input()->field()->switchableGroup(
            [
                "1" => $group1,
                "2" => $group2,
            ],
            "Typ"
        );

        $fields['availability'] = $factory->radio("Verfügbarkeit")
            ->withRequired(true)
            ->withOption("before", "Vorab")
            ->withOption("writing", "Nach Start der Bearbeitung")
            ->withOption("review", "Zur Einsichtnahme");


        $sections['form'] = $factory->section($fields, $section_title);

        $form = $this->uiFactory->input()->container()->form()->standard($this->ctrl->getFormAction($this), $sections);

        // apply inputs
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
        }

        // inputs are ok => save data
        if (isset($data)) {
            $record->setMixedDummy($data['form']['title']);
            $record->save();

            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);

            $this->ctrl->setParameter($this, 'id', $record->getId());
            $this->ctrl->redirect($this, "editItem");
        }

        $this->tpl->setContent($this->renderer->render($form));
    }
}