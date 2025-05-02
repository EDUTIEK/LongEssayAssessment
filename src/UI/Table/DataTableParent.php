<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\UI\Component\Table\Column\Column;

interface DataTableParent extends TableParent
{
    public function getColumnMapping(Item $item, ?array $additional_parameters): array;

    /**
     * @return Column[]
     */
    public function getColumns(?array $additional_parameters): array;
    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int;
}