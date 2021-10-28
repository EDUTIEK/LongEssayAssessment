<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\ActiveRecordDummy;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 * Resources Administration
 *
 * @package ILIAS\Plugin\LongEssayTask\Task
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\Task\CriteriaAdminGUI: ilObjLongEssayTaskGUI
 */
class CriteriaAdminGUI extends BaseGUI
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
                'headline' => 'Bewertungskriterium Eins',
                'subheadline' => 'Hier wird bewertet, dass ...',
                'important' => [
                    'Max. Punkte' => 2
                ],
            ],
            [
                'headline' => 'Bewertungskriterium Zwei',
                'subheadline' => 'Hier wird bewertet, dass ...',
                'important' => [
                    'Max. Punkte' => 3
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
        $button->setCaption('Kriterium hinzufügen', false);
        $this->toolbar->addButtonInstance($button);


        $ptable = $this->uiFactory->table()->presentation(
            'Bewertungskriterien',
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
            $section_title = $this->plugin->txt('Kriterium bearbeiten');
        }
        else {
            $record = new ActiveRecordDummy();
            $record->setTaskId($this->object->getId());
            $section_title = $this->plugin->txt('Kriterium hinzufügen');
        }

        $factory = $this->uiFactory->input()->field();

        $sections = [];

        $fields = [];
        $fields['title'] = $factory->text($this->lng->txt("title"))
            ->withRequired(true)
            ->withValue($record->getStringDummy());

        $fields['description'] = $factory->textarea($this->lng->txt("description"))
            ->withValue($record->getStringDummy());

        $fields['points'] = $factory->text('Max. Punkte', "Maximal vergebbare Punkte für dieses Kriterium.")
            ->withValue($record->getStringDummy('0'));


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