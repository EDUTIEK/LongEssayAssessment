<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

class UserDisplay extends \Edutiek\AssessmentService\System\Data\UserDisplay
{
    private ?string $image_url = null;
    private ?string $profile_url = null;

    public function __construct(
        private int $id,
    ) {
    }

    public function setValues(?string $image_url = null, ?string $profile_url = null):  self
    {
        $this->image_url = $image_url;
        $this->profile_url = $profile_url;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function getProfileUrl(string $return_url): ?string
    {
        return $this->profile_url;
    }
}
