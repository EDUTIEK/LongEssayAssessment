<?php

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo;

enum Value
{
    case EMPTY;
    case NULL;
    case NOT_EMPTY;
    case NOT_NULL;


    public function condition(string $field): string
    {
        return match ($this) {
            self::EMPTY => '(' . $field . ' IS NULL OR ' . $field . " = '')",
            self::NULL => $field . ' IS NULL',
            self::NOT_NULL => $field . ' IS NOT NULL',
            self::NOT_EMPTY => $field . ' IS NOT NULL AND ' . $field . " <> ''"
        };
    }
}
