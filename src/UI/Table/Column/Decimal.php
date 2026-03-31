<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Column;

use Closure;

class Decimal extends NullableNumber
{
    private ?Closure $formatter = null;
    private ?int $formatter_decimals = null;

    /**
     * Creates and returns a new instance of the current object with the specified formatter.
     *
     * @param Closure $formatter The formatter to be used in the cloned instance.
     *
     * @return self A cloned instance with the specified formatter applied.
     */
    public function withFormatter(Closure $formatter, ?int $decimals): self
    {
        $clone = clone $this;
        $clone->formatter = $formatter;
        $clone->formatter_decimals = $decimals;
        return $clone;
    }

    public function format($value): string
    {
        if ($this->formatter !== null) {
            return ($this->formatter)($value, $this->formatter_decimals);
        }
        return parent::format($value);
    }
}
