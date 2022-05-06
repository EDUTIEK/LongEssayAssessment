<?php

namespace ILIAS\Plugin\LongEssayTask\Task;

use ILIAS\Plugin\LongEssayTask\Data\Resource;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

class ResourceListGUI
{
    /**
     * @var Factory
     */
    protected $uiFactory;

    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * @var Resource[]
     */
    protected $items;

    /**
     * @var \ilLanguage
     */
    protected $lng;

    /**
     * @param object $target_class
     * @param Factory $uiFactory
     * @param Renderer $renderer
     * @param \ilLanguage $lng
     */
    public function __construct(object $target_class, Factory $uiFactory, Renderer $renderer, \ilLanguage $lng)
    {
        global $DIC;
        $this->uiFactory = $uiFactory;
        $this->renderer = $renderer;
        $this->lng = $lng;
        $this->ctrl = $DIC->ctrl();
        $this->target_class = $target_class;
    }

    /**
     * Show the items
     */
    public function render()
    {
//        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));
//        $button = \ilLinkButton::getInstance();
//        $button->setUrl($this->ctrl->getLinkTarget($this, 'editItem'));
//        $button->setCaption($this->plugin->txt('add_resource'), false);
//        $this->toolbar->addButtonInstance($button);
        $data = $this->getItemData();



        // TODO: Lang Var tittle Beschreibung
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

        return $this->renderer->render($ptable->withData($this->getItemData()));

        //$this->tpl->setContent($this->renderer->render($ptable->withData($this->getItemData())));
    }

    /**
     * @return Resource[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param Resource[] $items
     * @return ResourceListGUI
     */
    public function setItems(array $items): ResourceListGUI
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return array
     */
    protected function getItemData(): array
    {
        $item_data = [];

        foreach ($this->getItems() as $resource)
        {
            $label = "";
            $action = "";

            switch($resource->getType())
            {
                case Resource::RESOURCE_TYPE_URL:
                    // TODO: Use ilFile

                    $label = "File.file";
                    $this->ctrl->setParameterByClass($this->target_class, "resource_id", $resource->getId());
                    $action = $this->ctrl->getFormAction($this->target_class, "downloadResourceFile");

                    break;
                case Resource::RESOURCE_TYPE_FILE:
                    $label = $action = $resource->getUrl();
                    break;
            }
            //TODO: Lang var Verfügbar und availability
            $item_data[] = [
                'headline' => $resource->getTitle(),
                'subheadline' => $resource->getDescription(),
                'important' => [
                    'Verfügbar' => $resource->getAvailability(),
                    $this->renderer->render($this->uiFactory->link()->standard($label,$action))
                ]
            ];
        }

        return array_merge([
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

        ], $item_data);
    }


}