<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\DI\Container;
use Edutiek\AssessmentService\System\Api\Factory as SystemFactory;
use Edutiek\AssessmentService\System\Api\ForServices as SystemApi;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\ObjectRepo;
use ilObjectFactory;

class AssessmentDic implements \Edutiek\AssessmentService\Assessment\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {
        $dic[SystemApi::class] = function (Container $dic) {
            return $dic[SystemFactory::class]->forServices();
        };

        $dic[ObjectRepo::class] = function (Container $dic) {
            return new ObjectRepo(
                $dic->access(),
                $dic["ilObjDataCache"],
                new ilObjectFactory()
            );
        };
    }

    public function systemApi(): SystemApi
    {
        return $this->dic[SystemApi::class];
    }

    public function objectRepo(): ObjectRepo
    {
        return $this->dic[ObjectRepo::class];
    }
}