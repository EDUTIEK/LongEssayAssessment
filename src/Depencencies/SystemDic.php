<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\System\Data\ConfigRepo;
use ILIAS\DI\Container;
use ilLongEssayAssessmentPlugin;

/**
 * Dependency Container for the System Api
 */
class SystemDic implements \Edutiek\AssessmentService\System\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {
        $dic[ConfigRepo::class] = function (Container $dic) {
            return new ConfigRepo(
                $dic->database(),
                $dic[Generate::class],
                $dic->clientIni(),
                $dic[ilLongEssayAssessmentPlugin::class],
                $dic->filesystem()->web()
            );
        };
    }

    public function configRepo(): ConfigRepo
    {
        return $this->dic[ConfigRepo::class];
    }
}
