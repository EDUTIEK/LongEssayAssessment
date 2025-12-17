<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper;

use ILIAS\UI\Component\Table\Column\Column;

trait OptionalColumns
{
    private ?array $optional = null;
    abstract protected function optionalColumns() : array;

    /**
     * @param string[] $columns
     * @return self
     */
    public function setOptionalColumns(array $columns) : self
    {
        $this->optional = $columns;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getOptionalColumns(): ?array
    {
        return $this->optional ?? $this->optionalColumns();
    }

    private function setColumnOptional(string $key, Column $column) : Column
    {
        return $column->withIsOptional(in_array($key, $this->optional ??= $this->optionalColumns()), $column->isInitiallyVisible());
    }

    /**
     * @param Column[] $columns
     * @return Column[]
     */
    protected function setOptional(array $columns) : array
    {
        foreach($columns as $key => $column) {
            $columns[$key] = $this->setColumnOptional($key, $column);
        }
        return $columns;
    }
}