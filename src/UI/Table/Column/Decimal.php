<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Column;

class Decimal extends NullableNumber
{
    public function withDelimiter(string $decimal, string $thousands): self
    {
        $clone = clone $this;
        $clone->delim_decimal = $decimal;
        $clone->delim_thousands = $thousands;
        return $clone;
    }

    public function getDelimiterDecimal(): string
    {
        return $this->delim_decimal;
    }

    public function getDelimiterThousands(): string
    {
        return $this->delim_thousands;
    }
}
