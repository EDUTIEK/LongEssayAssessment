<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\DI\Container;

class AssessmentDic implements \Edutiek\AssessmentService\Assessment\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {

    }

}