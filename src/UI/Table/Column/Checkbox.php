<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Column;

use ILIAS\UI\Implementation\Component\Table\Column\Date;
use ILIAS\Data\DateFormat\DateFormat;
use ILIAS\Language\Language;
use ILIAS\UI\Implementation\Component\Table\Column\Column;

class Checkbox extends Column
{
    public function __construct(Language $lng, string $title, private string $name)
    {
        parent::__construct($lng, $title);
        $this->name = htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8');
    }

    public function format($value): string
    {
        $val = htmlspecialchars($value[0], ENT_QUOTES, 'UTF-8');
        $checked = (bool) $value[1] ? "checked" : "";

        return '<input type="checkbox" name="' . $this->name . '[]" value="' . $val . '"' . $checked . '/>';
    }

}
