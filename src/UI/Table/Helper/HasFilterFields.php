<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper;

trait HasFilterFields
{
    private ?array $has_filter = null;

    abstract protected function hasFilterFields() : array;

    /**
     * @param string[] $columns
     * @return self
     */
    public function setHasFilterFields(array $columns) : self
    {
        $this->has_filter = $columns;
        return $this;
    }

    public function getHasFilterFields() : ?array
    {
        return $this->has_filter ?? $this->hasFilterFields();
    }

    protected function filterFilterFields(array $filter_fields) : array
    {
        return array_filter($filter_fields, fn ($key) => in_array($key, $this->has_filter ??= $this->hasFilterFields()), ARRAY_FILTER_USE_KEY);
    }
}