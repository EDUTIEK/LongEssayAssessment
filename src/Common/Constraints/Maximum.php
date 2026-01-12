<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Common\Constraints;

use ILIAS\Data;
use ILIAS\Refinery\Constraint;
use ILIAS\Refinery\Custom\Constraint as CustomConstraint;
use ilLanguage;

class Maximum extends CustomConstraint implements Constraint
{
    protected int $max;

    public function __construct(int $max, Data\Factory $data_factory, ilLanguage $lng)
    {
        $this->max = $max;
        parent::__construct(
            function ($value) {
                return $value <= $this->max;
            },
            function ($txt, $value) {
                return $txt("rep_robj_xlas_constraint_error_not_maximum", $this->max);
            },
            $data_factory,
            $lng
        );
    }
}
