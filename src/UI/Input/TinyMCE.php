<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Input;

use ILIAS\UI\Implementation\Component\Input\Field\Textarea;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\UI\Implementation\Component\Input\Field\FormInput;
use ILIAS\Refinery\Transformation;
use ILIAS\Refinery\DeriveApplyToFromTransform;
use ILIAS\Refinery\DeriveInvokeFromTransform;

class TinyMCE extends Textarea
{
    private array $elements = ['strong', 'em', 'u', 'ol', 'li', 'ul', 'p', 'div',
                               'i', 'b', 'code', 'sup', 'sub', 'pre', 'strike', 'gap'];
    private array $plugins = ['link', 'emoticons', 'table', 'save', 'insertdatetime', 'preview', 'searchreplace',
                              'directionality', 'fullscreen', 'nonbreaking', 'anchor', 'lists', 'code', 'charmap'];

    private array $context_menu = ['cut', 'copy', 'paste', 'link', 'unlink', 'imagetools', 'table'];

    private array $disabled_buttons = [];

    const MODE_MINI = 1;
    const MODE_STANDARD = 2;
    const MODE_EXTENDED = 3;
    const MODE_EXTENDED_TABLE = 4;
    const MODE_FULL = 5;

    public function __construct(
        DataFactory $data_factory,
        \ILIAS\Refinery\Factory $refinery,
        string $label,
        ?string $byline
    ) {
        FormInput::__construct($data_factory, $refinery, $label, $byline);
        $this->initTransformation();
    }

    private function initTransformation()
    {
        $this->operations = [];

        $elements = $this->elements;
        $this->setAdditionalTransformation($this->refinery->custom()->transformation(
            fn ($x) => strip_tags($x, $elements)
        ));
    }


    public function withMode(int $mode) : TinyMCE
    {
        $clone = clone $this;

        if ($mode === "mini") {
            $clone->plugins = ["paste", "lists", "lists", "link", "code"];
            $clone->context_menu = [];
            $clone->disabled_buttons = ['anchor', 'alignleft', 'aligncenter', 'alignright', 'alignjustify',
                                        'formatselect', 'removeformat', 'cut', 'copy', 'paste', 'pastetext'];
        }

        switch ($mode) {
            case self::MODE_MINI:
                $clone->elements = ["strong", "em", "u", "ol", "li", "ul", "blockquote", "a", "p", "span", "br"];
                break;
            case self::MODE_STANDARD:
                $clone->elements = ["strong", "em", "u", "ol", "li", "ul", "p", "div",
                                    "i", "b", "code", "sup", "sub", "pre", "strike", "gap"];
                break;
            case self::MODE_EXTENDED:
                $clone->elements = ["a","blockquote","br","cite","code","div","em","h1","h2","h3",
                                    "h4","h5","h6","hr","li","ol","p",
                                    "pre","span","strike","strong","sub","sup","u","ul",
                                    "i", "b", "gap"];
                break;
            case self::MODE_EXTENDED_TABLE:
                $clone->elements = ["a","blockquote","br","cite","code","div","em","h1","h2","h3",
                                    "h4","h5","h6","hr","li","ol","p",
                                    "pre","span","strike","strong","sub","sup","table","td",
                                    "tr","u","ul", "i", "b", "gap"];
                break;
            case self::MODE_FULL:
                $clone->elements = ["a","blockquote","br","cite","code","div","em","h1","h2","h3",
                                    "h4","h5","h6","hr","li","ol","p",
                                    "pre","span","strike","strong","sub","sup","table","td",
                                    "tr","u","ul","ruby","rbc","rtc","rb","rt","rp", "i", "b", "gap"];
                break;
        }
        $clone->initTransformation();

        return $clone;
    }

    public function withAdditionalElements(array $elements) : TinyMCE
    {
        $clone = clone $this;

        $clone->elements = array_merge($clone->elements, $elements);
        $clone->initTransformation();
        return $clone;
    }

    public function withAdditionalPlugins(array $plugins) : TinyMCE
    {
        $clone = clone $this;

        $clone->plugins = array_merge($clone->plugins, $plugins);

        return $clone;
    }

    public function withAdditionalContextMenu(array $context_menu) : TinyMCE
    {
        $clone = clone $this;

        $clone->context_menu = array_merge($clone->context_menu, $context_menu);

        return $clone;
    }

    public function withAddtionalDisabledButtons(array $disabled_buttons) : TinyMCE
    {
        $clone = clone $this;

        $clone->disabled_buttons = array_merge($clone->disabled_buttons, $disabled_buttons);

        return $clone;
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function getContextMenu(): array
    {
        return $this->context_menu;
    }

    public function getDisabledButtons(): array
    {
        return $this->disabled_buttons;
    }

}
