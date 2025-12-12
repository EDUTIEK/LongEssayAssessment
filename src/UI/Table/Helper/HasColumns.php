<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper;

trait HasColumns
{
    private ?array $has_columns = null;

    abstract protected function hasColumns() : array;

    /**
     * @param string[] $columns
     * @return self
     */
    public function setHasColumns(array $columns) : self
    {
        $this->has_columns = $columns;
        return $this;
    }

    public function getHasColumns() : ?array
    {
        return $this->has_columns ??= $this->hasColumns();
    }

    protected function filterColumns(array $columns) : array
    {
        return array_filter($columns, fn ($key) => in_array($key, $this->has_columns ??= $this->hasColumns()), ARRAY_FILTER_USE_KEY);
    }
}