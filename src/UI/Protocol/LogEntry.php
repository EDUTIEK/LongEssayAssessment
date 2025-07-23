<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Protocol;

use Edutiek\AssessmentService\Assessment\LogEntry\Category as LogEntryCategory;
use DateTimeImmutable;

class LogEntry implements Item
{
    public function __construct(
        private LogEntryCategory $category,
        private string $entry,
        private DateTimeImmutable $timestamp,
    ){
    }

    public function getCategory(): LogEntryCategory
    {
        return $this->category;
    }

    public function getEntry(): string
    {
        return $this->entry;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function sortBy(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function type(): EntryType
    {
        return EntryType::fromCategory($this->category);
    }
}