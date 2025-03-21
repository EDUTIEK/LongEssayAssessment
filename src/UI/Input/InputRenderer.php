<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Input;

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
use ILIAS\UI\Implementation\Component\Input\Input;
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
use ILIAS\UI\Renderer as RendererInterface;

class InputRenderer extends \ILIAS\UI\Implementation\Component\Input\Field\Renderer
{
    private \ilGlobalPageTemplate $tpl; // if this is not defined and lead to arrow use $this->setGlobalTemplate after init
    private bool $tiny_mce_js_included = false;

    /**
     * @var array
     */
    protected $files_cache;

    public function render(Component $component, RendererInterface $default_renderer): string
    {
        if ($component instanceof Input) {
            $component = $this->setSignals($component);
        }

        switch (true) {
            case ($component instanceof Numeric):
                return $this->renderCustomNumericField($component);
            case ($component instanceof ItemListInput):
                return $this->renderItemListInput($component, $default_renderer);
            case ($component instanceof BlankForm):
                return $this->renderBlankForm($component, $default_renderer);
            case ($component instanceof TinyMCE):
                return $this->renderTinyMCE($component, $default_renderer);
            default:
                throw new \LogicException("Cannot render '" . get_class($component) . "'");
        }
    }

    protected function applyStep(Numeric $component, Template $tpl) : ?string
    {
        $step = $component->getStep();
        if ($step != 1.) {
            $tpl->setVariable("STEP", $step);
        }

        return $step;
    }

    protected function renderCustomNumericField(Numeric $component) : string
    {
        $tpl = $this->getTemplate("tpl.numeric.html", true, true);
        $this->applyName($component, $tpl);
        $this->applyValue($component, $tpl, $this->escapeSpecialChars());
        $this->applyStep($component, $tpl);
        # $this->maybeDisable($component, $tpl);
        $id = $this->bindJSandApplyId($component, $tpl);
        return $this->wrapInFormContext($component, $tpl->get(), $id);
    }

    protected function renderBlankForm(BlankForm $form, RendererInterface $default_renderer)
    {
        $tpl = $this->getTemplate("tpl.blank_form.html", true, true);
        $form = $this->registerBlankFormSignals($form);

        if ($form->isAsyncOnEnter()) {
            $async_submit = $form->getSubmitAsyncSignal();
            $form = $form->withAdditionalOnLoadCode(
                function ($id) use ($async_submit) {
                    return
                    "$('#{$id}').submit(function(e) {console.log('NoNONONONO');e.preventDefault();});
				$('#{$id}').on('keyup keypress', function(e) {
					  var keyCode = e.keyCode || e.which;
					  if (keyCode === 13) {
						e.preventDefault();
						$(document).trigger('{$async_submit}',
							{
								'id' : '{$async_submit}', 'event' : 'keypress',
								'triggerer' : $('#{$id}'),
								'options' : JSON.parse('[]')
							}
						);
						return false;
					  }
				});";
                }
            );
        }

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

    protected function renderTinyMCE(TinyMCE $component, RendererInterface $default_renderer)
    {
        /** @var TinyMCE $component */
        $component = $component->withAdditionalOnLoadCode(
            static function ($id): string {
                return "
                    taId = document.querySelector('#$id .c-input__field textarea')?.id;
                    il.UI.Input.textarea.init(taId);
                ";
            }
        );

        $tpl = $this->getPreparedTextareaTemplate($component);

        $label_id = $this->createId();
        $tpl->setVariable('ID', $label_id);
        $component = $this->initTinyMCE($component, $label_id);
        return $this->wrapInFormContext($component, $component->getLabel(), $tpl->get(), $label_id);
    }

    protected function renderItemListInput(ItemListInput $component, RendererInterface $default_renderer): string
    {
        $tpl = $this->getTemplate("tpl.item_list_input.html", true, true);
        $component = $this->registerItemListInputSignals($component);

        $id = $this->bindJavaScript($component);
        $tpl->setVariable('ID', $id);
        $this->applyName($component, $tpl);
        $this->applyValue($component, $tpl, $this->escapeSpecialChars());
        # $this->maybeDisable($component, $tpl);
        $id = $this->bindJSandApplyId($component, $tpl);
        return $this->wrapInFormContext($component, $tpl->get(), $id);
    }

    protected function getComponentInterfaceName(): array
    {
        return [
            ItemListInput::class,
            Numeric::class,
            BlankForm::class,
            TinyMCE::class,
        ];
    }

    /**
     * @param $name
     * @return mixed|string
     */
    protected function getTemplatePath($name) : string
    {
        if (in_array($name, $this->getPluginTemplateFiles())) {
            return "Input/$name";
        }

        return "components/ILIAS/UI/src/templates/default/Input/$name";
    }

    protected function getPluginTemplateFiles(): array
    {
        if ($this->files_cache === null) {

            $this->files_cache =  array_filter(scandir(dirname(__FILE__). "/../../../templates/Input"), function ($item) {
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
        $submit_async = $form->getSubmitAsyncSignal();

        return $form->withAdditionalOnLoadCode(function ($id) use ($submit, $submit_async) {
            return "
			$(document).on('{$submit}', function() { document.forms['{$id}'].submit(); return false; });
			$(document).on('{$submit_async}', function() { 
				var form = $('#{$id}');
    			var actionUrl = form.attr('action');
    
				$.ajax({
					type: 'POST',
					url: actionUrl,
					dataType: 'html',
					data: form.serialize()
				}).done(function(html) {
					if(html.length == 0){
						location.reload();
					}else
					{
						var \$new_content = $('<div>' + html + '</div>');
						$('#{$id}').html(\$new_content.html());
					}
				});

				return false; 
			});
			";
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
        /** @var ItemListInput $input */
        $input = $input->withAdditionalOnLoadCode(
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
        return $input;
    }

    public function setGlobalTemplate(\ilGlobalPageTemplate $template) : InputRenderer
    {
        $this->tpl = $template;
        return $this;
    }

    protected function initTinyMCE(TinyMCE $component, string $form_id) : TinyMCE
    {
        if (!$this->tiny_mce_js_included) {
            $this->tpl->addJavaScript('node_modules/tinymce/tinymce.min.js');
        }

        $tiny = new \ilTinyMCE();

        $tpl = $this->getTemplate("tpl.tiny_mce.js", true, true);
        $tpl->setVariable("STYLESHEET_LOCATION", \ilUtil::getNewContentStyleSheetLocation() . ',' . \ilUtil::getStyleSheetLocation('output', 'delos.css'));
        $tpl->setVariable("ADDITIONAL_PLUGINS", implode(' ', $component->getPlugins()));
        $tpl->setVariable("LANG", is_file("./node_modules/tinymce/langs/de.js") ? "de" : "en"); // as we only have de right now this is sufficient
        $buttons_1 = $tiny->_buildAdvancedButtonsFromHTMLTags(1, $component->getElements());
        $buttons_2 = $tiny->_buildAdvancedButtonsFromHTMLTags(2, $component->getElements())
            . ',' . $tiny->_buildAdvancedTableButtonsFromHTMLTags($component->getElements())
            . ($tiny->getStyleSelect() ? ',styleselect' : '');
        $buttons_3 = $tiny->_buildAdvancedButtonsFromHTMLTags(3, $component->getElements());
        $tpl->setVariable('BUTTONS_1', $tiny->removeRedundantSeparators($buttons_1));
        $tpl->setVariable('BUTTONS_2', $tiny->removeRedundantSeparators($buttons_2));
        $tpl->setVariable('BUTTONS_3', $tiny->removeRedundantSeparators($buttons_3));
        $tpl->setVariable("VALID_ELEMENTS", $tiny->_getValidElementsFromHTMLTags($component->getElements()));
        $tpl->setVariable('BLOCKFORMATS', $tiny->_buildAdvancedBlockformatsFromHTMLTags($component->getElements()));

        $tpl->setVariable("CONTEXT_MENU_ITEMS", "");

        /**
         * @var TinyMCE $component
         */
        $component = $component->withAdditionalOnLoadCode(
            function ($id) use ($component, $tpl, $form_id) {
                $tpl->setVariable("ID", $form_id);
                return $tpl->get();
            }
        );
        return $component;
    }
}
