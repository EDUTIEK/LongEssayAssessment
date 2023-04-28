<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer as RendererInterface;

class Renderer extends AbstractComponentRenderer
{

	/**
	 * @inheritDoc
	 */
	protected function getComponentInterfaceName()
	{
		return [FormGroup::class, FormItem::class];
	}

	/**
	 * @inheritDoc
	 */
	public function render(Component $component, \ILIAS\UI\Renderer $default_renderer)
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
				return "";
		}
	}

	/**
	 * @param $name
	 * @return mixed|string
	 */
	protected function getTemplatePath($name)
	{
		return "./Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment/templates/" . $name;
	}

	protected function renderFormGroup(FormGroup $component, RendererInterface $default_renderer)
	{
		$tpl = $this->getTemplate("tpl.form_group.html", true, true);
		$title = $component->getTitle();
		$items = $component->getItems();
		$checkbox_enabled = $component->isCheckboxEnabled();
		$form_action = $component->getFormAction();
		$action_buttons = $component->getActionButtons();

		// items
		foreach ($items as $key =>  $item) {

			$tpl->setCurrentBlock("item");
			$tpl->setVariable("ITEM", $default_renderer->render($item));

			if($checkbox_enabled){
				$tpl->setVariable("CHECKBOX", $key);
			}

			$tpl->parseCurrentBlock();
		}

		if ($title != "") {
			$tpl->setCurrentBlock("title");
			$tpl->setVariable("TITLE", $title);
			$tpl->parseCurrentBlock();
		}
		$tpl->setVariable("FORM_ACTION", $form_action);

		$tpl->setVariable("IMG_ARROW", "./templates/default/images/arrow_downright.svg");
		$tpl->setVariable("ALT_ARROW", "Aktion");

		if(count($action_buttons) > 1){
			$tpl->setCurrentBlock("multiple_cmd");
			foreach($action_buttons as $cmd => $lang){
				$tpl->setCurrentBlock("cmd_option");
				$tpl->setVariable("CMD_VAL", $cmd);
				$tpl->setVariable("CMD_NAME", $lang);
				$tpl->parseCurrentBlock();
			}
			$tpl->setVariable("TXT_EXECUTE", $this->txt("execute"));
			$tpl->parseCurrentBlock();

		}else if(count($action_buttons) == 1){
			$tpl->setCurrentBlock("single_cmd");
			foreach($action_buttons as $cmd => $lang){
				$tpl->setVariable("CMD_VAL", $cmd);
				$tpl->setVariable("CMD_NAME", $lang);
			}
			$tpl->parseCurrentBlock();
		}

		// actions
		$actions = $component->getActions();
		if ($actions !== null) {
			$tpl->setVariable("ACTIONS", $default_renderer->render($actions));
		} else {
			$tpl->setVariable("ACTIONS", "");
		}



		return $tpl->get();
	}

	protected function renderFormItem(FormItem $component, RendererInterface $default_renderer)
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
		$tpl->setVariable("CHECKBOX", "ghndfgcv");
		$tpl->parseCurrentBlock();

		// lead
		$lead = $component->getLead();
		if ($lead != null) {
			if (is_string($lead)) {
				$tpl->setCurrentBlock("lead_text");
				$tpl->setVariable("LEAD_TEXT", $lead);
				$tpl->parseCurrentBlock();
			}
			if ($lead instanceof Component\Image\Image) {
				$tpl->setCurrentBlock("lead_image");
				$tpl->setVariable("LEAD_IMAGE", $default_renderer->render($lead));
				$tpl->parseCurrentBlock();
			}
			if ($lead instanceof Component\Symbol\Icon\Icon) {
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

	protected function renderTitle(
		FormItem $component,
		RendererInterface $default_renderer,
		\ILIAS\UI\Implementation\Render\Template $tpl
	) {
		$title = $component->getTitle();
		if ($title instanceof \ILIAS\UI\Component\Button\Shy || $title instanceof \ILIAS\UI\Component\Link\Link) {
			$title = $default_renderer->render($title);
		}
		$tpl->setVariable("TITLE", $title);
	}

	protected function renderDescription(
		FormItem $component,
		RendererInterface $default_renderer,
		\ILIAS\UI\Implementation\Render\Template $tpl
	) {
		// description
		$desc = $component->getDescription();
		if (trim($desc) != "") {
			$tpl->setCurrentBlock("desc");
			$tpl->setVariable("DESC", $desc);
			$tpl->parseCurrentBlock();
		}
	}

	protected function renderProperties(
		FormItem $component,
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

}