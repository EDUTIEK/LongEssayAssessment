<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Input\Container\Filter\FilterInput;

/**
 * This describes numeric inputs.
 */
interface Numeric extends FilterInput
{

    /**
     * Get the step width of the input.
     *
     * @return    float
     */
    public function getStep(): float;

    /**
     * Get an input like this, but with a replaced step width.
     *
     * @param    float $step
     *
     * @return    static
     */
    public function withStep(float $step);
}
