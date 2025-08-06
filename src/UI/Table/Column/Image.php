<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Column;

use ILIAS\UI\Implementation\Component\Table\Column\Column;
use ILIAS\UI\Component\Symbol;
use ILIAS\UI\Component\Component;

class Image extends Column
{
    public function format($value): string|Component
    {
        return $value instanceof Symbol\Symbol ? $value: "";
    }

}