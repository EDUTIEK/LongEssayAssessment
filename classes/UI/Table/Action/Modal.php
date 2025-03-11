<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\UI\Implementation\Component as UI;

abstract class Modal extends Action
{
    private bool $update_button = true;

    /**
     * @param Item[] $items
     * @return \ILIAS\UI\Implementation\Component\Modal\Modal
     */
    abstract public function modal(array $items) : UI\Modal\Modal;

    public function withUpdateButton(bool $update_button) : Modal
    {
        $clone = clone $this;
        $clone->update_button = $update_button;
        return $clone;
    }

    /**
     * @return bool
     */
    public function hasUpdateButton() : bool
    {
        return $this->update_button;
    }
}