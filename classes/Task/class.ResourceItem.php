<?php

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;

class ResourceItem extends Item
{
    public function __construct(
        int $id,
        protected string $title,
        protected string $type,
        protected ?string $description = null,
        protected ?string $available = null,
        protected ?string $url = null,
        protected ?string $identifier = null
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

    public function getAvailable() : ?string
    {
        return $this->available;
    }

    public function getUrl() : ?string
    {
        return $this->url;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getIdentifier() : ?string
    {
        return $this->identifier;
    }
}