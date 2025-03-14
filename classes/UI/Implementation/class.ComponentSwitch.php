<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Component\Component;

class ComponentSwitch implements \ILIAS\UI\Component\JavaScriptBindable
{
    use JavaScriptBindable;

    /**
     * @var SignalGeneratorInterface
     */
    protected SignalGeneratorInterface $signal_generator;

    protected Signal $switch;

    /**
     * @var Component[]|Component
     */
    protected array|Component $components_a;

    /**
     * @var Component[]|Component
     */
    protected array|Component $components_b;

    protected string $switch_label;

    public function __construct(array|Component $components_a, array|Component $components_b, string $switch_label, SignalGeneratorInterface $signal_generator)
    {
        $this->signal_generator = $signal_generator;
        $this->components_a = $components_a;
        $this->components_b = $components_b;
        $this->switch_label = $switch_label;
        $this->initSignals();
    }

    public function getSwitchSignal(): Signal
    {
        return $this->switch;
    }

    public function getComponentsA(): array|Component
    {
        return $this->components_a;
    }

    public function getComponentsB(): array|Component
    {
        return $this->components_b;
    }

    public function getSwitchLabel(): string
    {
        return $this->switch_label;
    }

    public function initSignals()
    {
        $this->switch = $this->signal_generator->create();
    }

    public function getCanonicalName() : string
    {
        return "Component Switcher";
    }
}
