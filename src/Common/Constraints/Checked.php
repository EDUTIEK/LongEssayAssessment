<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Common\Constraints;

use ILIAS\Data;
use ILIAS\Refinery\Constraint;
use ILIAS\Refinery\Custom\Constraint as CustomConstraint;
use ilLanguage;

class Checked extends CustomConstraint implements Constraint
{
    protected int $min;

    public function __construct(Data\Factory $data_factory, ilLanguage $lng)
    {
        parent::__construct(
            function ($value) {
                return (bool) $value == true;
            },
            function ($txt, $value) {
                return $txt("rep_robj_xlas_constraint_checked_required");
            },
            $data_factory,
            $lng
        );
    }
}
