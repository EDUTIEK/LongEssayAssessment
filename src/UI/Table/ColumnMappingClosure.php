<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\ColumnMappingArray;

class ColumnMappingClosure extends ColumnMappingArray
{
    private \Closure $closure;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function map(string $key): mixed
    {
        return ($this->closure)($key);
    }
}