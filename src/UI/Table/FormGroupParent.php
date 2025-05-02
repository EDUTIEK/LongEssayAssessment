<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\UI\Component\Table\Column\Column;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormItem;

interface FormGroupParent extends TableParent
{
    public function buildItem(Item $item): FormItem;
}