<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Common\Constraints;

use ILIAS\Data;
use ILIAS\Refinery\Constraint;
use ILIAS\Refinery\Custom\Constraint as CustomConstraint;
use ilLanguage;

class Minimum extends CustomConstraint implements Constraint
{
    protected int $min;

    public function __construct(int $min, Data\Factory $data_factory, ilLanguage $lng)
    {
        $this->min = $min;
        parent::__construct(
            function ($value) {
                return $value >= $this->min;
            },
            function ($txt, $value) {
                return $txt("rep_robj_xlas_constraint_error_not_minimum", $this->min);
            },
            $data_factory,
            $lng
        );
    }
}
