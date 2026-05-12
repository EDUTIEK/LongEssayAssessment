<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\LiveStatusPanel;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\ComponentHelper;

class Panel implements Component
{
    use ComponentHelper;

    private int $interval = 5000;

    /**
     * @param string $title
     * @param string $live_data_url
     * @param Property[]  $properties
     */
    public function __construct(
        private readonly string $title,
        private readonly string $live_data_url,
        private array $properties = []
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLiveDataUrl(): string
    {
        return $this->live_data_url;
    }

    /**
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param Property[] $properties
     * @return $this
     */
    public function withAdditionalProperties(array $properties): Panel
    {
        $clone = clone $this;
        $clone->properties += $properties;
        return $clone;
    }

    /**
     * @param int $interval in ms
     * @return $this
     */
    public function withInterval(int $interval): Panel
    {
        $clone = clone $this;
        $clone->interval = $interval;
        return $clone;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getCanonicalName(): string
    {
        return "Live Status Panel";
    }
}
