<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Component;

interface StatisticItem extends Component
{
    public function withTitle(string $title): Statistic;
    public function getTitle(): string;
}