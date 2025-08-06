<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Column;

use ILIAS\Language\Language;
use ILIAS\Data\DateFormat\DateFormat;

class Factory
{
    public function __construct(private Language $language) {}

    public function interval(string $title, string $format) : Interval
    {
        return new Interval($this->language, $title, $format);
    }

    public function unboundTimeSpan(string $title, DateFormat $format) : UnboundTimeSpan
    {
        return new UnboundTimeSpan($this->language, $title, $format);
    }

    public function nullableDate(string $title, DateFormat $format) : NullableDate
    {
        return new NullableDate($this->language, $title, $format);
    }

    public function nullableNumber(string $title) : NullableNumber
    {
        return new NullableNumber($this->language, $title);
    }

    public function image(string $title) : Image
    {
        return new Image($this->language, $title);
    }
}