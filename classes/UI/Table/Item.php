<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

class Item
{
    public function __construct(
        protected int $id
    ) {
    }

    public function getId()
    {
        return $this->id;
    }
}
