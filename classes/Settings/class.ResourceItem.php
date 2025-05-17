<?php

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\Task\Data\ResourceAvailability;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;

class ResourceItem extends Item
{
    public function __construct(
        int $id,
        protected string $title,
        protected ResourceType $type,
        protected ?string $description = null,
        protected ResourceAvailability $available,
        protected ?string $url = null,
        protected ?string $identifier = null,
        protected bool $is_embedded = false,
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

    public function getAvailable() : ResourceAvailability
    {
        return $this->available;
    }

    public function getUrl() : ?string
    {
        return $this->url;
    }

    public function getType() : ResourceType
    {
        return $this->type;
    }

    public function getIdentifier() : ?string
    {
        return $this->identifier;
    }

    public function isEmbedded() : bool
    {
        return $this->is_embedded;
    }
}