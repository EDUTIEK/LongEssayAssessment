<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Statistic;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\ComponentHelper;

abstract class StatisticGroup implements Component
{
    use ComponentHelper;
    private string $title;
    /**
     * @var Statistic[]
     */
    private array $items;

    /**
     * @param string $title
     * @param Statistic[]  $items
     */
    public function __construct(string $title, array $items)
    {
        $this->title = $title;
        $this->items = $items;
    }



    /**
     * @inheritDoc
     */
    public function getCanonicalName(): string
    {
        return "StatisticGroup";
    }

    public function withTitle(string $title): StatisticGroup
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withStatistics(array $items): StatisticGroup
    {
        $clone = clone $this;
        $clone->items = $items;

        return $clone;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatistics(): array
    {
        return $this->items;
    }
}
