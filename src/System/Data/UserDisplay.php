<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

readonly class UserDisplay extends \Edutiek\AssessmentService\System\Data\UserDisplay
{
    public function __construct(
        private int $id,
        private ?string $image_url = null,
        private ?string $embedded_profile_url = null,
        private ?string $linked_profile_url = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function getEmbeddedProfileUrl(): ?string
    {
        return $this->embedded_profile_url;
    }

    public function getLinkedProfileUrl(string $return_url): ?string
    {
        return $this->linked_profile_url;
    }
}
