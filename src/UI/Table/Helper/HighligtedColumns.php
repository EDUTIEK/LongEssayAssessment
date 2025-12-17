<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper;

use ILIAS\UI\Component\Table\Column\Column;

trait HighligtedColumns
{
    private ?array $highlight = null;
    abstract protected function highlightedColumns() : array;

    /**
     * @param string[] $columns
     * @return self
     */
    public function setHighlightedColumns(array $columns) : self
    {
        $this->highlight = $columns;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getHighlightedColumns() : ?array
    {
        return $this->highlight ?? $this->highlightedColumns();
    }

    private function setColumnHighlight(string $key, Column $column) : Column
    {
        return $column->withHighlight(in_array($key, $this->optional ??= $this->highlightedColumns()));
    }

    /**
     * @param Column[] $columns
     * @return Column[]
     */
    protected function setHighlighted(array $columns) : array
    {
        foreach ($columns as $key => $column) {
            $columns[$key] = $this->setColumnHighlight($key, $column);
        }
        return $columns;
    }
}
