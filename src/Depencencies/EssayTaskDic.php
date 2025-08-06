<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\DI\Container;
use Edutiek\AssessmentService\System\Api\Factory as SystemFactory;
use Edutiek\AssessmentService\System\Api\ForServices as SystemApi;
use Edutiek\AssessmentService\Assessment\Api\Factory as AssessmentFactory;
use Edutiek\AssessmentService\Assessment\Api\ForTasks as AssessmentApi;
use Edutiek\AssessmentService\Task\Api\Factory as TaskFactory;
use Edutiek\AssessmentService\Task\Api\ForTypes as TaskApi;
use ILIAS\Plugin\LongEssayAssessment\EssayTask\Data\RepositoryFactory;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;


/**
 * Dependencies of the assessment services component "EssayTask"
 */
class EssayTaskDic implements \Edutiek\AssessmentService\EssayTask\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {
        $dic[RepositoryFactory::class] = function (Container $dic) {
            return new RepositoryFactory(
                $dic[Generate::class],
                $dic->database()
            );
        };
    }

    public function systemApi(): SystemApi
    {
        return $this->dic[SystemApi::class];
    }

    public function assessmentApi(int $ass_id, int $user_id): AssessmentApi
    {
        return $this->dic[AssessmentFactory::class]->forTasks($ass_id, $user_id);
    }

    public function taskApi(int $ass_id, int $user_id): TaskApi
    {
        return $this->dic[TaskFactory::class]->forTypes($ass_id, $user_id);
    }

    public function repositories(): RepositoryFactory
    {
        return $this->dic[RepositoryFactory::class];
    }
}
