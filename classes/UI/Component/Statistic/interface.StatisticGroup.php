<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Component;

interface StatisticGroup extends Component
{
    public function withTitle(string $title): StatisticGroup;
    /**
     * @param StatisticItem[] $items
     * @return StatisticGroup
     */
    public function withStatistics(array $items): StatisticGroup;
    public function getTitle(): string;
    public function getStatistics(): array;
}