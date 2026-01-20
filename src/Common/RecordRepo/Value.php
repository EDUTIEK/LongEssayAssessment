<?php

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo;

enum Value
{
    case NULL;
    case NOT_NULL;

    public function condition(string $field): string
    {
        return match ($this) {
            self::NULL => $field . ' IS NULL',
            self::NOT_NULL => $field . ' IS NOT NULL',
        };
    }
}
