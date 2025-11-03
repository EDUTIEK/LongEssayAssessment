<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

class DataTableRowBuilder implements \Iterator
{
    private int $position = 0;
    private array $items = [];
    private array $columns = [];

    /**
     * @param DataRowBuilder  $builder
     * @param \Iterator       $items
     * @param DataTableParent $parent
     * @param Range           $range
     * @param Order           $order
     * @param Action\Action[]           $actions
     * @param array           $additional_parameters
     */
    public function __construct(
        private DataRowBuilder $builder,
        \Iterator $items,
        TableParent $parent,
        private array $visible_column_ids,
        private Range $range,
        private Order $order,
        private array $actions,
        ?array $additional_parameters = null,
    ) {
        $this->position = $this->range->getStart();
        /**
         * @var Item $item
         */
        foreach ($items as $item) {
            $this->items[] = $item;
            $this->columns[$item->getId()] = $parent->getColumnMapping($item, $additional_parameters);
        }
        $this->uasort();
    }

    private function uasort()
    {
        list($order_field, $order_direction) = $this->order->join([], fn ($ret, $key, $value) => [$key, $value]);
        usort($this->items, fn (Item $a, Item $b) => $this->columns[$a->getId()][$order_field] <=> $this->columns[$b->getId()][$order_field]);
        if ($order_direction === 'DESC') {
            $this->items = array_reverse($this->items);
        }
    }

    private function column(int $id): array
    {
        $array = [];
        $column = $this->columns[$id];

        foreach($this->visible_column_ids as $key) { //reduce to visible fields and convert to array (for ArrayAccess)
            $array[$key] = $column[$key] ?? null;
        }
        return $array;
    }

    public function current(): mixed
    {
        /**
         * @var Item $item
         */
        $item = $this->items[$this->position];
        $row = $this->builder->buildDataRow((string) $item->getId(), $this->column($item->getId()));
        foreach (array_filter($this->actions, fn(Action\Action $x) => in_array($x->type(), [Action\Type::Standard, Action\Type::Single])) as $action) {
            $row = $row->withDisabledAction($action->name(), !$action->enabled($item));
        }
        return  $row;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]) && $this->position < $this->range->getEnd();
    }

    public function rewind(): void
    {
        $this->position = $this->range->getStart();
    }
}
