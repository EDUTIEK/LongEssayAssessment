<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\UI\Implementation\Component as UI;

abstract class Modal extends Action
{
    /**
     * @param Item[] $items
     * @return \ILIAS\UI\Implementation\Component\Modal\Modal
     */
    abstract public function modal(array $items) : UI\Modal\Modal;
}