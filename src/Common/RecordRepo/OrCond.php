<?php

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo;

readonly class OrCond
{
    public function __construct(
        private array $conditions
    ) {
    }

    /** @return array<string, mixed> */
    public function conditions(): array
    {
        return $this->conditions;
    }
}
