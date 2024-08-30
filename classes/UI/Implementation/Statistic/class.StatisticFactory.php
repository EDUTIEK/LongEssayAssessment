<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

class StatisticFactory
{
    public function statistic(string $title, int $count, string $count_label, int $final, string $final_label): Statistic {
        return new Statistic($title, $count, $count_label, $final, $final_label);
    }

    public function statisticSection(string $title): StatisticSection {
        return new StatisticSection($title);
    }

    public function extendableStatisticGroup(string $title, array $items): ExtendableStatisticGroup {
        return new ExtendableStatisticGroup($title, $items);
    }

    public function graphStatisticGroup(string $title, array $items): GraphStatisticGroup {
        return new GraphStatisticGroup($title, $items);
    }
}