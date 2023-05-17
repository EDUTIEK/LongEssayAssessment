<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI;

use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormItem;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\Numeric;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\FieldRenderer;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ItemRenderer;
use ILIAS\UI\Implementation\Render\DecoratedRenderer;
use ILIAS\UI\Renderer;

//inherit from DecoratedRender to align your renderer with other potential renders in ILIAS to allow manipulations from
//different sources to be chained behind each other.
class PluginRenderer extends DecoratedRenderer
{
	private ItemRenderer $item_renderer;
	protected FieldRenderer $field_render;

	public function __construct(Renderer $default, ItemRenderer $item_renderer, FieldRenderer $field_render)
	{
		parent::__construct($default);
		$this->item_renderer = $item_renderer;
		$this->field_render = $field_render;
	}


	//define your manipulations. This example add an "A" before every button in ILIAS
	protected function manipulateRendering($component, Renderer $root): ?string
	{
		switch(true){
			case ($component instanceof FormItem):
			case ($component instanceof FormGroup):
				return $this->item_renderer->render($component, $root);
			case ($component instanceof Numeric):
				return $this->field_render->render($component, $root);
		}

		//skip components that are not important to you with returning null
		return null;
	}
}
