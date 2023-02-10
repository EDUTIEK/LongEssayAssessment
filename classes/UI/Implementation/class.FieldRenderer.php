<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ILIAS\UI\Implementation\Render\Template;
use \ILIAS\Plugin\LongEssayAssessment\UI\Component as F;
use ILIAS\UI\Renderer as RendererInterface;

class FieldRenderer extends \ILIAS\UI\Implementation\Component\Input\Field\Renderer
{

	/**
	 * @var array
	 */
	protected $files_cache;

	/**
	 * @inheritdoc
	 */
	public function render(Component $component, RendererInterface $default_renderer)
	{
		/**
		 * @var $component Input
		 */
		$this->checkComponent($component);

		$component = $this->setSignals($component);

		switch (true) {
			case ($component instanceof F\Numeric):
				return $this->renderCustomNumericField($component, $default_renderer);
			default:
				throw new \LogicException("Cannot render '" . get_class($component) . "'");
		}
	}

	protected function applyStep(Numeric $component, Template $tpl) : ?string
	{
		$step = $component->getStep();
		if($step != 1.)
		{
			$tpl->setVariable("STEP", $step);
		}

		return $step;
	}

	protected function renderCustomNumericField(F\Numeric $component) : string
	{
		$tpl = $this->getTemplate("tpl.numeric.html", true, true);
		$this->applyName($component, $tpl);
		$this->applyValue($component, $tpl, $this->escapeSpecialChars());
		$this->applyStep($component, $tpl);
		$this->maybeDisable($component, $tpl);
		$id = $this->bindJSandApplyId($component, $tpl);
		return $this->wrapInFormContext($component, $tpl->get(), $id);
	}

	protected function getComponentInterfaceName(): array
	{
		return [
			F\Numeric::class,
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
			return "Input/$name";
		}

		return "src/UI/templates/default/Input/$name";
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