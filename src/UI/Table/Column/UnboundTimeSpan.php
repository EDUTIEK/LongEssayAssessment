<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Column;

use ILIAS\UI\Implementation\Component\Table\Column\TimeSpan;

class UnboundTimeSpan extends TimeSpan
{
    public function format($value): string
    {
        if (is_null($value) || (is_null($value[0]) && is_null($value[1]))) {
            return '';
        }
        $this->checkArgList(
            'value',
            $value,
            function ($k, $v) {
                return ($v === null || $v instanceof \DateTimeImmutable) && in_array($k, [0,1]);
            },
            function ($k, $v) {
                return "Two values of type " . \DateTimeImmutable::class . " or null, got ($k => $v)";
            }
        );

        return
            ($value[0] !== null ? $value[0]->format($this->getFormat()->toString()) : '*')
            . ' - ' .
            ($value[1] !== null ? $value[1]->format($this->getFormat()->toString()) : '*');
    }
}