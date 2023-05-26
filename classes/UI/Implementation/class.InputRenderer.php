<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Test\JSTestComponent;
use ILIAS\UI\Implementation\Component\Button\Button;
use ILIAS\UI\Implementation\Component\Button\Close;
use ILIAS\UI\Implementation\Component\Button\Month;
use ILIAS\UI\Implementation\Component\Button\Toggle;
use ILIAS\UI\Implementation\Component\Card\Card;
use ILIAS\UI\Implementation\Component\Dropdown\Dropdown;
use ILIAS\UI\Implementation\Component\Dropzone\File\File;
use ILIAS\UI\Implementation\Component\Image\Image;
use ILIAS\UI\Implementation\Component\Input\Container\Filter\Filter;
use ILIAS\UI\Implementation\Component\Input\Container\Filter\ProxyFilterField;
use ILIAS\UI\Implementation\Component\Input\Field\Checkbox;
use ILIAS\UI\Implementation\Component\Input\Field\DateTime;
use ILIAS\UI\Implementation\Component\Input\Field\Duration;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ILIAS\UI\Implementation\Component\Input\Field\OptionalGroup;
use ILIAS\UI\Implementation\Component\Input\Field\Password;
use ILIAS\UI\Implementation\Component\Input\Field\Radio;
use ILIAS\UI\Implementation\Component\Input\Field\SwitchableGroup;
use ILIAS\UI\Implementation\Component\Input\Field\Tag;
use ILIAS\UI\Implementation\Component\Input\Field\Textarea;
use ILIAS\UI\Implementation\Component\Item\Notification;
use ILIAS\UI\Implementation\Component\Layout\Page\Standard;
use ILIAS\UI\Implementation\Component\Legacy\Legacy;
use ILIAS\UI\Implementation\Component\Link\Bulky;
use ILIAS\UI\Implementation\Component\MainControls\MainBar;
use ILIAS\UI\Implementation\Component\MainControls\MetaBar;
use ILIAS\UI\Implementation\Component\MainControls\Slate\Slate;
use ILIAS\UI\Implementation\Component\MainControls\SystemInfo;
use ILIAS\UI\Implementation\Component\Menu\Menu;
use ILIAS\UI\Implementation\Component\Modal\Modal;
use ILIAS\UI\Implementation\Component\Popover\Popover;
use ILIAS\UI\Implementation\Component\Symbol\Avatar\Avatar;
use ILIAS\UI\Implementation\Component\Symbol\Glyph\Glyph;
use ILIAS\UI\Implementation\Component\Symbol\Icon\Icon;
use ILIAS\UI\Implementation\Component\Table\PresentationRow;
use ILIAS\UI\Implementation\Component\Tree\Expandable;
use ILIAS\UI\Implementation\Component\Tree\Node\Node;
use ILIAS\UI\Implementation\Component\ViewControl\Pagination;
use ILIAS\UI\Implementation\Component\ViewControl\Sortation;
use ILIAS\UI\Implementation\Render\Template;
use \ILIAS\Plugin\LongEssayAssessment\UI\Component as F;
use ILIAS\UI\Renderer as RendererInterface;

class InputRenderer extends \ILIAS\UI\Implementation\Component\Input\Field\Renderer
{

	/**
	 * @var array
	 */
	protected $files_cache;

	/**
	 * @inheritdoc
	 */
	public function render(Component $component, RendererInterface $default_renderer): string
	{
		if($component instanceof Input)
		{
			//$this->checkComponent($component);
			$component = $this->setSignals($component);
		}

		switch (true) {
			case ($component instanceof F\Numeric):
				return $this->renderCustomNumericField($component);
			case ($component instanceof F\ItemListInput):
				return $this->renderItemListInput($component, $default_renderer);
			case ($component instanceof F\BlankForm):
				return $this->renderBlankForm($component, $default_renderer);
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

	protected function renderBlankForm (F\BlankForm $form, RendererInterface $default_renderer)
	{
		$tpl = $this->getTemplate("tpl.blank_form.html", true, true);
		$form = $this->registerBlankFormSignals($form);

		$id = $this->bindJavaScript($form);
		$tpl->setVariable('ID', $id);

		if ($form->getPostURL() != "") {
			$tpl->setCurrentBlock("action");
			$tpl->setVariable("URL", $form->getPostURL());
			$tpl->parseCurrentBlock();
		}

		$tpl->setVariable("INPUTS", $default_renderer->render($form->getInputGroup()));

		$error = $form->getError();
		if (!is_null($error)) {
			$tpl->setVariable("ERROR", $error);
		}
		return $tpl->get();
	}

	protected function renderItemListInput(F\ItemListInput $component, RendererInterface $default_renderer): string
	{
		$tpl = $this->getTemplate("tpl.item_list_input.html", true, true);
		$component = $this->registerItemListInputSignals($component);

		$id = $this->bindJavaScript($component);
		$tpl->setVariable('ID', $id);

		$this->applyName($component, $tpl);
		$this->applyValue($component, $tpl, $this->escapeSpecialChars());
		$this->maybeDisable($component, $tpl);
		$id = $this->bindJSandApplyId($component, $tpl);
		return $this->wrapInFormContext($component, $tpl->get(), $id);
	}

	protected function getComponentInterfaceName(): array
	{
		return [
			F\ItemListInput::class,
			F\Numeric::class,
			F\BlankForm::class
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

	/**
	 * @param BlankForm $form
	 * @return BlankForm
	 */
	protected function registerBlankFormSignals(BlankForm $form): BlankForm
	{
		$submit = $form->getSubmitSignal();

		return $form->withAdditionalOnLoadCode(function ($id) use ($submit) {
			return "$(document).on('{$submit}', function() { document.forms['{$id}'].submit(); return false; });";
		});
	}

	/**
	 * @param ItemListInput $input
	 * @return ItemListInput
	 */
	protected function registerItemListInputSignals(ItemListInput $input):ItemListInput
	{
		$trigger_load = $input->getTriggerLoadSignal();
		$data_source = $input->getListDataSource();

		return $input->withAdditionalOnLoadCode(
			function ($id) use ($trigger_load, $data_source) {
				return "$(document).on('{$trigger_load}', function() {
				 			$(document).trigger('{$data_source}',
							{
								'id' : '{$data_source}', 'event' : 'load_list_data_source',
								'triggerer' : $('#{$id}'),
								'options' : JSON.parse('[]')
							}
						);
						return false; 
					});";
			}
		);
	}
}