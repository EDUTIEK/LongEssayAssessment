<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

abstract class ViewRepo
{
    public function where(array $filter): ?string
    {
        $filter = array_filter($filter);
        $parts = array_filter(array_map([$this, 'filter'], array_keys($filter), $filter));

        return !empty($parts) ? implode(' AND ', $parts) : null;
    }

    abstract public function filter(string $key, mixed $value): ?string;
}