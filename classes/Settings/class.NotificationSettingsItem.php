<?php

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use Edutiek\AssessmentService\Assessment\Data\NotificationType;

class NotificationSettingsItem extends Item
{
    public function __construct(
        protected int $id,
        protected NotificationType $type,
        protected bool $active,
        protected string $subject,
        protected ?string $body,
        protected int $position,
    ) {
        parent::__construct($id);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
