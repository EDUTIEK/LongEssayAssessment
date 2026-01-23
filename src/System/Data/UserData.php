<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use DateTimeZone;

class UserData extends \Edutiek\AssessmentService\System\Data\UserData
{
    private string $login = '';
    private ?string $title = null;
    private string $firstname = '';
    private string $lastname = '';
    private ?string $matriculation = null;
    private string $language = '';
    private ?DateTimeZone $timezone;

    public function __construct(
        private int $id,
    ) {
    }

    public function setValues(
        string $login,
        ?string $title,
        string $firstname,
        string $lastname,
        ?string $matriculation,
        string $language,
        DateTimeZone $timezone
    ): self {
        $this->login = $login;
        $this->title = $title;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->matriculation = $matriculation;
        $this->language = $language;
        $this->timezone = $timezone;

        return $this;
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

    public function getMatriculation(): ?string
    {
        return $this->matriculation;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone ?? new DateTimeZone('Europe/Berlin');;
    }
}
