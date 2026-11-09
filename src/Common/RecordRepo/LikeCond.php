<?php

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo;

readonly class LikeCond
{
    public function __construct(
        private string $pattern,
        private bool $not = false
    ) {
    }

    /** @return string */
    public function condition($field, callable $quote): string
    {
        return $field . ($this->not ? ' NOT' : '') . ' LIKE ' . $quote($this->pattern);
    }
}
