<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Statistic;

class StatisticFactory
{
    public function statistic(string $title, int $count, string $count_label): Statistic
    {
        return new Statistic($title, $count, $count_label);
    }

    public function statisticSection(string $title): StatisticSection
    {
        return new StatisticSection($title);
    }

    public function extendableStatisticGroup(string $title, array $items): ExtendableStatisticGroup
    {
        return new ExtendableStatisticGroup($title, $items);
    }

    public function graphStatisticGroup(string $title, array $items): GraphStatisticGroup
    {
        return new GraphStatisticGroup($title, $items);
    }
}
