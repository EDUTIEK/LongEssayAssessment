<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\UI\Component\Input\Container\Filter;

interface FilterParent
{
    /**
     * @return Filter\FilterInput[]
     */
    public function getFilterInputs(): array;

    /**
     * @return bool[]
     */
    public function getFilterInputActivation(): ?array;

    /**
     * The ctrl action string to the table
     * @return string
     */
    public function getFilterBaseAction(): string;
}