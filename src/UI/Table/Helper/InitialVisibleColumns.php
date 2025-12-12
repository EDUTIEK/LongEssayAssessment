<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper;

use ILIAS\UI\Component\Table\Column\Column;

trait InitialVisibleColumns
{
    private ?array $initial_visible = null;
    abstract protected function initialVisibleColumns() : array;

    /**
     * @param string[] $columns
     * @return self
     */
    public function setInitialVisibleColumns(array $columns) : self
    {
        $this->initial_visible = $columns;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getInitialVisibleColumns() : ?array
    {
        return $this->initial_visible ?? $this->initialVisibleColumns();
    }

    private function setColumnsInitialVisible(string $key, Column $column) : Column
    {
        return $column->withIsOptional($column->isOptional(), in_array($key, $this->initial_visible ??= $this->initialVisibleColumns()));
    }

    /**
     * @param Column[] $columns
     * @return Column[]
     */
    protected function setInitialVisible(array $columns) : array
    {
        foreach($columns as $key => $column) {
            $columns[$key] = $this->setColumnsInitialVisible($key, $column);
        }
        return $columns;
    }
}