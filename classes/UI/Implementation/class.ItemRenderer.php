<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Image\Image;
use ILIAS\UI\Component\Item\Group;
use ILIAS\UI\Component\Item\Item;
use ILIAS\UI\Component\Symbol\Icon\Icon;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
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
			case ($component instanceof FormGroup):
				return $this->renderFormGroup($component, $default_renderer);
			case ($component instanceof FormItem):
				return $this->renderFormItem($component, $default_renderer);
			default:
				throw new \LogicException("Cannot render '" . get_class($component) . "'");
		}
	}


	protected function getComponentInterfaceName(): array
	{
		return [FormGroup::class, FormItem::class];
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

			$this->files_cache =  array_filter(scandir(dirname(__FILE__). "/../../../templates/Item"), function($item){
				return str_starts_with($item, "tpl.");
			});

		}

		return $this->files_cache;
	}

	protected function renderFormGroup(FormGroup $component, RendererInterface $default_renderer): string
	{
		$tpl = $this->getTemplate("tpl.form_group.html", true, true);

		$component = $this->registerSignals($component);
		$id = $this->bindJavaScript($component);
		$tpl->setVariable("FORM_ID", $id);

		$tpl->setVariable("FORM_ACTION", $component->getFormAction());
		$tpl->setVariable("LIST_STD", $this->renderGroup($component->withoutActions(), $default_renderer));

		if (count($component->getItems()) > 0)
		{
			$tpl->setVariable("IMG_ARROW_TOP", \ilUtil::getImagePath("arrow_upright.svg"));
			$tpl->setVariable("ALT_ARROW_TOP", $this->txt("action"));

			$tpl->setVariable("IMG_ARROW_BOT", \ilUtil::getImagePath("arrow_downright.svg"));
			$tpl->setVariable("ALT_ARROW_BOT", $this->txt("action"));

			$tpl->setVariable("SELECT_ALL_TXT_SELECT_ALL", $this->txt("select_all"));


			$actions = $component->getActions();
			if ($actions === null) {
				$buttons = $this->getUIFactory()->button()->standard($this->txt("execute"), "")
					->withOnClick($component->getSubmitSignal());
			} else {
				$buttons = $component->getActions()->withLabel($this->txt("execute"));
			}
			$tpl->setVariable("CMD_BUTTONS_TOP", $default_renderer->render($buttons));
			$tpl->setVariable("CMD_BUTTONS_BOTTOM", $default_renderer->render($buttons));
		}

		return $tpl->get();
	}

	protected function renderFormItem(FormItem $component, RendererInterface $default_renderer): string
	{
		$tpl = $this->getTemplate("tpl.form_item.html", true, true);

		$this->renderTitle($component, $default_renderer, $tpl);
		$this->renderDescription($component, $default_renderer, $tpl);
		$this->renderProperties($component, $default_renderer, $tpl);
		// color
		$color = $component->getColor();
		if ($color !== null) {
			$tpl->setCurrentBlock("color");
			$tpl->setVariable("COLOR", $color->asHex());
			$tpl->parseCurrentBlock();
		}

		$tpl->setCurrentBlock("checkbox");
		$tpl->setVariable("CB_VALUE", $component->getName());
		$tpl->setVariable("LIST_DATA_SOURCE_NAME", strip_tags($component->getTitle()));
		$tpl->parseCurrentBlock();

		// lead
		$lead = $component->getLead();
		if ($lead != null) {
			if (is_string($lead)) {
				$tpl->setCurrentBlock("lead_text");
				$tpl->setVariable("LEAD_TEXT", $lead);
				$tpl->parseCurrentBlock();
			}
			if ($lead instanceof Image) {
				$tpl->setCurrentBlock("lead_image");
				$tpl->setVariable("LEAD_IMAGE", $default_renderer->render($lead));
				$tpl->parseCurrentBlock();
			}
			if ($lead instanceof Icon) {
				$tpl->setCurrentBlock("lead_icon");
				$tpl->setVariable("LEAD_ICON", $default_renderer->render($lead));
				$tpl->parseCurrentBlock();
				$tpl->setCurrentBlock("lead_start_icon");
				$tpl->parseCurrentBlock();
			} else {
				$tpl->setCurrentBlock("lead_start");
				$tpl->parseCurrentBlock();
			}
			$tpl->touchBlock("lead_end");
		}
		// actions
		$actions = $component->getActions();
		if ($actions !== null) {
			$tpl->setVariable("ACTIONS", $default_renderer->render($actions));
		}
		return $tpl->get();
	}

	protected function renderProperties(
		Item $component,
		RendererInterface $default_renderer,
		\ILIAS\UI\Implementation\Render\Template $tpl
	) {
		// properties
		$props = $component->getProperties();
		if (count($props) > 0) {
			$cnt = 0;
			foreach ($props as $name => $value) {
				if ($value instanceof \ILIAS\UI\Component\Button\Shy || $value instanceof Input) {
					$value = $default_renderer->render($value);
				}
				$cnt++;
				if ($cnt % 2 == 1) {
					$tpl->setCurrentBlock("property_row");
					$tpl->setVariable("PROP_NAME_A", $name);
					$tpl->setVariable("PROP_VAL_A", $value);
				} else {
					$tpl->setVariable("PROP_NAME_B", $name);
					$tpl->setVariable("PROP_VAL_B", $value);
					$tpl->parseCurrentBlock();
				}
			}
			if ($cnt % 2 == 1) {
				$tpl->parseCurrentBlock();
			}
			$tpl->setCurrentBlock("properties");
			$tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param FormGroup $form
	 * @return FormGroup
	 */
	protected function registerSignals(FormGroup $form): FormGroup
	{
		$submit = $form->getSubmitSignal();
		$list_data_source = $form->getListDataSourceSignal();

		return $form->withAdditionalOnLoadCode(function ($id) use ($list_data_source) {
				return "$(document).on('{$list_data_source}', function(event, signalData) {
					var data_list = [];
					$('#{$id}').find('.list_data_source_item:checked').map(function() {
						data_list[$(this).attr( 'value' )] = $(this).attr( 'list_data_source_name' );
					});
					\$triggerer = signalData['triggerer'];
					\$triggerer.trigger('load_list_data_source_callback', {'data_list': data_list});
					return false; 
				});";
			})->withAdditionalOnLoadCode(function ($id) use ($submit) {
			return "$(document).on('{$submit}', function() { document.forms['{$id}'].submit(); return false; });";
		});
	}

}