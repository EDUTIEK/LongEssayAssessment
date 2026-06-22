<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Container;

use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\ComponentHelper;

class Bindable implements \ILIAS\UI\Component\JavaScriptBindable
{
    use JavaScriptBindable, ComponentHelper;

    /**
     * @var Component[]|Component
     */
    protected array|Component $components;

    public function __construct(array|Component $components)
    {
        $this->components = $components;
    }

    public function getComponents(): array|Component
    {
        return $this->components;
    }

    public function getCanonicalName(): string
    {
        return "Bindable";
    }
}
