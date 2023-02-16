<?php

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
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
	 * @param \ilPlugin $plugin
     */
    public function __construct(object $target_class, Factory $uiFactory, Renderer $renderer, \ilLanguage $lng, \ilPlugin $plugin)
    {
        global $DIC;
        $this->uiFactory = $uiFactory;
        $this->renderer = $renderer;
        $this->lng = $lng;
        $this->ctrl = $DIC->ctrl();
        $this->target_class = $target_class;
		$this->plugin = $plugin;
    }

    /**
     * Show the items
     */
    public function render()
    {
        $data = $this->getItemData();

        $ptable = $this->uiFactory->table()->presentation(
            $this->plugin->txt('task_resources'),
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
                    ->withContent($ui_factory->listing()->descriptive([$this->lng->txt('description') => $record['subheadline']]))
                    ->withFurtherFieldsHeadline('')
                    ->withFurtherFields($record['important'])
                    ->withAction(
                        $ui_factory->dropdown()->standard([
                            $ui_factory->button()->shy($this->lng->txt('edit'), $record["edit_action"]),
                            $ui_factory->button()->shy($this->lng->txt('delete'), $record["delete_action"])
                        ])
                            ->withLabel($this->lng->txt("actions"))
                    );
            }
        );

        return $this->renderer->render($ptable->withData($this->getItemData()));
    }

    /**
     * @return Resource[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param \ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource[] $items
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
			$this->ctrl->setParameter($this->target_class, "resource_id", (string) $resource->getId());
			$edit_action = $this->ctrl->getFormAction($this->target_class, "editItem");
			$this->ctrl->setParameter($this->target_class, "resource_id", (string) $resource->getId());
			$delete_action = $this->ctrl->getFormAction($this->target_class, "deleteItem");

            switch($resource->getType())
            {
                case Resource::RESOURCE_TYPE_FILE:
                    $label = $this->lng->txt("download");
                    $this->ctrl->setParameter($this->target_class, "resource_id", (string) $resource->getId());
                    $action = $this->ctrl->getFormAction($this->target_class, "downloadResourceFile");

                    break;
                case Resource::RESOURCE_TYPE_URL:
                    $label = $action = $resource->getUrl();

					if (strlen($label) >= 20) {
						$label = substr($label, 0, 17) . "...";
					}

                    break;
            }
            //TODO: Lang var Verfügbar und availability
            $item_data[] = [
                'headline' => $resource->getTitle(),
                'subheadline' => $resource->getDescription(),
                'important' => [
                    $this->plugin->txt('resource_available') => $this->plugin->txt('resource_availability_'.$resource->getAvailability()),
                    $this->renderer->render($this->uiFactory->link()->standard($label,$action))
                ],
				'edit_action' => $edit_action,
				'delete_action' => $delete_action,
            ];
        }

        return $item_data;
    }


}