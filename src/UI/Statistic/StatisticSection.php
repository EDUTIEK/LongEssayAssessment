<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Statistic;

class StatisticSection
{
    private string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function withTitle(string $title): StatisticSection
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCanonicalName(): string
    {
        return "Statistic Section";
    }
}