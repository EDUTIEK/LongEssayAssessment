<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action\Action;

interface TableParent
{
    /**
     * @return Action[]
     */
    public function getTableActions() : array;

    /**
     * @param int[]|null $ids
     * @param array|null $filter_data
     * @return Item[]
     */
    public function getTableItems(?array $ids = null, ?array $filter_data = null) : array;
    public function getTableItem(int $id) : Item;
}