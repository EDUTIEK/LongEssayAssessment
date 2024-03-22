<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\Constraints;

use ILIAS\Refinery\Constraint;
use ILIAS\Data;
use ILIAS\Refinery\Custom\Constraint as CustomConstraint;

class MinimumInteger extends CustomConstraint implements Constraint
{
    /**
     * @var int
     */
    protected $min;

    public function __construct(int $min, Data\Factory $data_factory, \ilLanguage $lng)
    {
        $this->min = $min;
        parent::__construct(
            function ($value) {
                return $value >= $this->min;
            },
            function ($txt, $value) {
                return $txt("rep_robj_xlas_constraint_error_not_minimum", $value, $this->min);
            },
            $data_factory,
            $lng
        );
    }
}