<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Protocol;

use DateTimeImmutable;
class Alert implements Item
{
    public function __construct(
        private string $recipient,
        private string $message,
        private DateTimeImmutable $send_date
    ){

    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public  function getSendDate(): DateTimeImmutable
    {
        return $this->send_date;
    }

    public function sortBy(): \DateTimeImmutable
    {
        return $this->send_date;
    }

    public function type(): EntryType
    {
        return EntryType::ALERT;
    }
}