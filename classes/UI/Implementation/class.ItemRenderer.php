<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ILIAS\UI\Implementation\Render\Template;
use \ILIAS\Plugin\LongEssayAssessment\UI\Component as F;
use ILIAS\UI\Renderer as RendererInterface;

class ItemRenderer extends \ILIAS\UI\Implementation\Component\Item\Renderer
{
	/**
	 * @inheritdoc
	 */
	public function render(Component $component, RendererInterface $default_renderer)
	{
		/**
		 * @var $component Input
		 */
		$this->checkComponent($component);

		switch (true) {
			case ($component instanceof F\Numeric):
				return $this->renderCustomNumericField($component, $default_renderer);
			default:
				throw new \LogicException("Cannot render '" . get_class($component) . "'");
		}
	}


	protected function getComponentInterfaceName(): array
	{
		return [

		];
	}

	/**
	 * @param $name
	 * @return mixed|string
	 */
	protected function getTemplatePath($name)
	{
		if(in_array($name, $this->getPluginTemplateFiles()))
		{
			return "Item/$name";
		}

		return "src/UI/templates/default/Item/$name";
	}

	protected function getPluginTemplateFiles(): array
	{
		if($this->files_cache === null){

			$this->files_cache =  array_filter(scandir(dirname(__FILE__). "/../../../templates/Input"), function($item){
				return str_starts_with($item, "tpl.");
			});

		}

		return $this->files_cache;
	}
}