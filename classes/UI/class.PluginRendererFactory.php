<?php

namespace ILIAS\Plugin\LongEssayTask\UI;

use ILIAS\Plugin\LongEssayTask\UI\Implementation\FieldRenderer;
use ILIAS\Plugin\LongEssayTask\UI\Implementation\Renderer;
use ILIAS\UI\Implementation\Render;
use ILIAS\UI\Component;

class PluginRendererFactory extends Render\DefaultRendererFactory
{
	public function getRendererInContext(Component\Component $component, array $contexts)
	{
		switch(true){
			case $component instanceof \ILIAS\UI\Implementation\Component\Input\Field\Input:
				return new FieldRenderer(
					$this->ui_factory,
					$this->tpl_factory,
					$this->lng,
					$this->js_binding,
					$this->refinery,
					$this->image_path_resolver
				);
			default:
				return new Renderer($this->ui_factory,
					$this->tpl_factory,
					$this->lng,
					$this->js_binding,
					$this->refinery,
					$this->image_path_resolver);
		}
	}
}

