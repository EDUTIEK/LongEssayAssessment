<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper;

use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use Generator;
use ILIAS\UI\Component\Table\DataRetrieval;

trait DataRetrievalFactory
{
    public function getDataRetrival(): DataRetrieval
    {
        return new class($this) implements DataRetrieval
        {
            public function __construct(private mixed $parent){}

            public function getRows(
                DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                ?array $filter_data,
                ?array $additional_parameters
            ): Generator {
                yield from $this->parent->getRows($row_builder, $visible_column_ids, $range, $order, $filter_data, $additional_parameters);
            }

            public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
            {
                return $this->parent->getTotalRowCount($filter_data, $additional_parameters);
            }
        };
    }

    abstract public function getRows(
        DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data, // $DIC->uiService()->filter()->getData();
        ?array $additional_parameters
    ): Generator;

    abstract public function getTotalRowCount(
        ?array $filter_data, // $DIC->uiService()->filter()->getData();
        ?array $additional_parameters
    ): ?int;
}
