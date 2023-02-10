<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;

class Renderer extends AbstractComponentRenderer
{

	/**
	 * @inheritDoc
	 */
	protected function getComponentInterfaceName()
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function render(Component $component, \ILIAS\UI\Renderer $default_renderer)
	{
		return "";
	}

	/**
	 * @param $name
	 * @return mixed|string
	 */
	protected function getTemplatePath($name)
	{
		return $name;
	}
}