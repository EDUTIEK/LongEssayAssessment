<?php

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;

class CriteriaItem extends Item
{
    public function __construct(
        int $id,
        protected string $title,
        protected ?string $description,
        protected int $max_points
    ) {
        parent::__construct($id);
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function getMaxPoints() : int
    {
        return $this->max_points;
    }
}