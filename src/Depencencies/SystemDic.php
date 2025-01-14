<?php

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Data\System\Config\Repository;
use ILIAS\DI\Container;

/**
 * Depency Container for the System Api
 */
class SystemDic implements \Edutiek\AssessmentService\System\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    )
    {
        $dic[Repository::class] = function (Container $dic) {
            return new Repository($dic->database(), $dic[Generate::class]);
        };
    }

    public function configRepo() : Repository
    {
        return  $this->dic[Repository::class];
    }
}