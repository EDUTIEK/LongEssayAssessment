<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Column;

use ILIAS\UI\Implementation\Component\Table\Column\Date;
use ILIAS\Data\DateFormat\DateFormat;

class NullableDate extends Date
{
    public function format($value): string
    {
        if($value === null) {
            return '';
        }

        return '<time>' . parent::format($value) . '</time>';
    }

}