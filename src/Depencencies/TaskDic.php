<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use Edutiek\AssessmentService\Assessment\Api\Factory as AssessmentFactory;
use Edutiek\AssessmentService\Assessment\Api\ForTasks as AssessmentApi;
use Edutiek\AssessmentService\EssayTask\Api\Factory as EssayTaskFactory;
use Edutiek\AssessmentService\System\Api\ForConstraints as ConstraintApi;
use Edutiek\AssessmentService\System\Api\ForEvents as EventApi;
use Edutiek\AssessmentService\System\Api\ForServices as SystemApi;
use Edutiek\AssessmentService\System\ConstraintHandling\Collector;
use Edutiek\AssessmentService\System\EventHandling\Dispatcher;
use Edutiek\AssessmentService\Task\TypeInterfaces\ApiFactory as TypeApiFactory;
use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Task\Data\RepositoryFactory;

/**
 * Dependencies of the assessment services component "Task"
 */
class TaskDic implements \Edutiek\AssessmentService\Task\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {

        $dic[TypeApis::class] = function (Container $dic) {
            return new TypeApis(
                $dic[EssayTaskFactory::class]->forTask()
            );
        };

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

    public function typeApis(): TypeApiFactory
    {
        return $this->dic[TypeApis::class];
    }

    public function repositories(): RepositoryFactory
    {
        return $this->dic[RepositoryFactory::class];
    }

    public function assessmentApi(int $ass_id, int $user_id): AssessmentApi
    {
        return $this->dic[AssessmentFactory::class]->forTasks($ass_id, $user_id);
    }

    public function eventDispatcher(int $ass_id, int $user_id): Dispatcher
    {
        return $this->dic[EventApi::class]->dispatcher($ass_id, $user_id);
    }

    public function constraintCollector(int $ass_id, int $user_id): Collector
    {
        return $this->dic[ConstraintApi::class]->collector($ass_id, $user_id);
    }
}
