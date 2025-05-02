<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;

abstract class Direct extends Action
{
    abstract public function action(array $items): void;
}