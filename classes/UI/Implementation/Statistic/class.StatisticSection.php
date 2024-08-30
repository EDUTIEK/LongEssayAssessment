<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

class StatisticSection implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\StatisticSection
{
    private string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function withTitle(string $title): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCanonicalName()
    {
        return "Statistic Section";
    }
}