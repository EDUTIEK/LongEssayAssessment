<?php

namespace ILIAS\Plugin\LongEssayAssessment\Criteria;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;

class CriteriaItem extends Item
{
    public function __construct(
        protected int $id,
        protected string $title,
        protected ?string $description,
        protected bool $is_general,
        protected int $max_points
    ) {}

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

    public function isGeneral() : bool
    {
        return $this->is_general;
    }
}