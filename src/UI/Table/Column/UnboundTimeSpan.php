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

        $start = ($value[0] !== null
            ? ('<span class="sr-only">' . $this->lng->txt('from') . ' </span><time>' . $value[0]->format($this->getFormat()->toString()) . '</time>')
            : '<span aria-hidden="true">*</span>');

        $end = ($value[1] !== null
            ? ('<span class="sr-only">' . $this->lng->txt('to') . ' </span><time>' . $value[1]->format($this->getFormat()->toString()) . '</time>')
            : '<span aria-hidden="true">*</span>');

        return "$start<span aria-hidden=\"true\"> - </span>$end";
    }
}