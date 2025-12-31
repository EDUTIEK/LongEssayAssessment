<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

abstract class ViewRepo
{
    /**
     * Build an AND combined string of conditions for an SQL WHERE clause
     * @param array<string, mixed> $filter array of key/value pairs from a filter setting
     */
    public function where(array $filter): ?string
    {
        $filter = array_filter($filter);
        $parts = array_filter(array_map([$this, 'whereCondition'], array_keys($filter), $filter));

        return !empty($parts) ? implode(' AND ', $parts) : null;
    }

    /**
     * Build an AND combined string of conditions for an SQL HAVING clause
     * @param array<string, mixed> $filter array of key/value pairs from a filter setting
     */
    public function having(array $filter): ?string
    {
        $filter = array_filter($filter);
        $parts = array_filter(array_map([$this, 'havingCondition'], array_keys($filter), $filter));

        return !empty($parts) ? implode(' AND ', $parts) : null;
    }

    /**
     * Get the condition for an SQL WHERE clause from a key/value pair
     * @return string|null  condition of null if the condition can't be be used in WHERE
     */
    abstract public function whereCondition(string $key, mixed $value): ?string;

    /**
     * Get the condition for an SQL HAVING clause from a key/value pair
     * @return string|null  condition of null if the condition can't be be used in HAVING
     */
    abstract public function havingCondition(string $key, mixed $value): ?string;
}
