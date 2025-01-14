<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Plugin\LongEssayAssessment\UI\Component\StatisticGroup as IStatisticGroup;

abstract class StatisticGroup implements IStatisticGroup
{

    private string $title;
    /**
     * @var array|\ILIAS\Plugin\LongEssayAssessment\UI\Component\StatisticItem[]
     */
    private array $items;

    /**
     * @param string $title
     * @param \ILIAS\Plugin\LongEssayAssessment\UI\Component\StatisticItem[]  $items
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

    public function withTitle(string $title): IStatisticGroup
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withStatistics(array $items): IStatisticGroup
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