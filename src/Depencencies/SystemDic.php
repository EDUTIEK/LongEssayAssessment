<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\System\Data\SystemRepo;
use ILIAS\DI\Container;

/**
 * Dependency Container for the System Api
 */
class SystemDic implements \Edutiek\AssessmentService\System\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    )
    {
        $dic[SystemRepo::class] = function (Container $dic) {
            return new SystemRepo($dic->database(), $dic[Generate::class]);
        };
    }

    public function configRepo() : SystemRepo
    {
        return $this->dic[SystemRepo::class];
    }
}
