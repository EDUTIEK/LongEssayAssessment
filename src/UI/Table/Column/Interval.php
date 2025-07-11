<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Column;

use ILIAS\UI\Implementation\Component\Table\Column\Column;
use ILIAS\Language\Language;
use ILIAS\Data\DateFormat\DateFormat;

class Interval extends Column
{
    public function __construct(
        Language $lng,
        string $title,
        protected string $format
    ) {
        parent::__construct($lng, $title);
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function format($value): string
    {
        if($value === "" || $value === null){
            return "";
        }

        if (is_numeric($value)) {
            $value = new \DateInterval("$value s");
        } else if(is_string($value)) {
            $value = new \DateInterval($value);
        }

        $this->checkArgInstanceOf('value', $value, \DateInterval::class);
        return $value[0]->format($this->getFormat());

    }


    /**
     * @return string[]
     */
    public function getOrderingLabels(): array
    {
        return [
            $this->asc_label ?? $this->getTitle() . self::SEPERATOR . $this->lng->txt('order_option_numerical_ascending'),
            $this->desc_label ?? $this->getTitle() . self::SEPERATOR . $this->lng->txt('order_option_numerical_descending')
        ];
    }
}