<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI;

use ILIAS\UI\Implementation\Render\DecoratedRenderer;
use ILIAS\UI\Renderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Input\TinyMCE;
use ILIAS\Plugin\LongEssayAssessment\UI\Viewer\PdfViewer;
use ILIAS\Plugin\LongEssayAssessment\UI\Statistic\ExtendableStatisticGroup;
use ILIAS\Plugin\LongEssayAssessment\UI\Statistic\GraphStatisticGroup;
use ILIAS\Plugin\LongEssayAssessment\UI\Statistic\Statistic;
use ILIAS\Plugin\LongEssayAssessment\UI\Input\BlankForm;
use ILIAS\Plugin\LongEssayAssessment\UI\Input\Numeric;
use ILIAS\Plugin\LongEssayAssessment\UI\Input\ItemListInput;
use ILIAS\Plugin\LongEssayAssessment\UI\Item\FormGroup;
use ILIAS\Plugin\LongEssayAssessment\UI\Item\FormItem;
use ILIAS\Plugin\LongEssayAssessment\UI\Item\ItemRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Viewer\ViewerRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Input\InputRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Statistic\StatisticRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Table;

//inherit from DecoratedRender to align your renderer with other potential renders in ILIAS to allow manipulations from
//different sources to be chained behind each other.
class PluginRenderer extends DecoratedRenderer
{
    private ItemRenderer $item_renderer;
    private ViewerRenderer $viewer_render;
    protected InputRenderer $field_render;
    protected StatisticRenderer $statistic_renderer;

    public function __construct(
        Renderer $default,
        ItemRenderer $item_renderer,
        InputRenderer $field_render,
        StatisticRenderer $statistic_renderer,
        ViewerRenderer $viewer_render,
    ) {
        parent::__construct($default);
        $this->item_renderer = $item_renderer;
        $this->field_render = $field_render;
        $this->statistic_renderer = $statistic_renderer;
        $this->viewer_render = $viewer_render;
    }


    //define your manipulations. This example add an "A" before every button in ILIAS
    protected function manipulateRendering($component, Renderer $root): ?string
    {
        switch (true) {
            case ($component instanceof FormItem):
            case ($component instanceof FormGroup):
                return $this->item_renderer->render($component, $root);
            case ($component instanceof ItemListInput):
            case ($component instanceof Numeric):
            case ($component instanceof BlankForm):
            case ($component instanceof TinyMCE):
                return $this->field_render->render($component, $root);
            case ($component instanceof Statistic):
            case ($component instanceof GraphStatisticGroup):
            case ($component instanceof ExtendableStatisticGroup):
                return $this->statistic_renderer->render($component, $root);
            case ($component instanceof PdfViewer):
                return $this->viewer_render->render($component, $root);
            case ($component instanceof Table):
                return $root->render($component->getComponents());
        }

        //skip components that are not important to you with returning null
        return null;
    }
}
