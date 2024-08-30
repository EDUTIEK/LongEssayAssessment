<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI;

use ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormItem;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\ItemListInput;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\Numeric;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\InputRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ItemRenderer;
use ILIAS\UI\Implementation\Render\DecoratedRenderer;
use ILIAS\UI\Renderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\StatisticRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\Statistic;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\GraphStatisticGroup;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ExtendableStatisticGroup;

//inherit from DecoratedRender to align your renderer with other potential renders in ILIAS to allow manipulations from
//different sources to be chained behind each other.
class PluginRenderer extends DecoratedRenderer
{
    private ItemRenderer $item_renderer;
    protected InputRenderer $field_render;
    protected StatisticRenderer $statistic_renderer;

    public function __construct(Renderer $default, ItemRenderer $item_renderer, InputRenderer $field_render, StatisticRenderer $statistic_renderer)
    {
        parent::__construct($default);
        $this->item_renderer = $item_renderer;
        $this->field_render = $field_render;
        $this->statistic_renderer = $statistic_renderer;
    }


    //define your manipulations. This example add an "A" before every button in ILIAS
    protected function manipulateRendering($component, Renderer $root): ?string
    {
        switch(true) {
            case ($component instanceof FormItem):
            case ($component instanceof FormGroup):
                return $this->item_renderer->render($component, $root);
            case ($component instanceof ItemListInput):
            case ($component instanceof Numeric):
            case ($component instanceof BlankForm):
                return $this->field_render->render($component, $root);
            case ($component instanceof Statistic):
            case ($component instanceof GraphStatisticGroup):
            case ($component instanceof ExtendableStatisticGroup):
                return $this->statistic_renderer->render($component, $root);
        }

        //skip components that are not important to you with returning null
        return null;
    }
}
