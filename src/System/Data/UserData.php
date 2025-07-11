<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use DateTimeZone;

readonly class UserData extends \Edutiek\AssessmentService\System\Data\UserData
{
    public function __construct(
        private int $id,
        private string $login,
        private ?string $title,
        private string $firstname,
        private string $lastname,
        private string $language,
        private DateTimeZone $timezone
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }
}
