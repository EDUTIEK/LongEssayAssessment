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

use Edutiek\AssessmentService\Task\Api\Factory as TaskFactory;
use Edutiek\AssessmentService\Task\Api\ForAssessment as TaskApi;
use ILIAS\DI\Container;
use Edutiek\AssessmentService\System\Api\Factory as SystemFactory;
use Edutiek\AssessmentService\System\Api\ForServices as SystemApi;
use Edutiek\AssessmentService\System\Api\ForEvents as EventApi;
use ilObjectFactory;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\RepositoryFactory;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Rest\RestContext;
use Edutiek\AssessmentService\Assessment\EventHandling\Observer;
use Edutiek\AssessmentService\System\EventHandling\Dispatcher;

/**
 * Dependencies of the assessment service component "Assessment"
 */
class AssessmentDic implements \Edutiek\AssessmentService\Assessment\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {
        $dic[TaskApi::class] = function (Container $dic) {
            return $dic[TaskFactory::class]->forAssessment();
        };

        $dic[RepositoryFactory::class] = function (Container $dic) {
            return new RepositoryFactory(
                $dic[Generate::class],
                $dic->database(),
                $dic->access(),
                $dic["ilObjDataCache"],
                new ilObjectFactory()
            );
        };

        $dic[RestContext::class] = function (Container $dic) {
            return new RestContext(
                $dic["ilClientIniFile"],
                $dic->database(),
                $dic->http()
            );
        };
    }

    public function systemApi(): SystemApi
    {
        return $this->dic[SystemApi::class];
    }

    public function taskApi(): TaskApi
    {
        return $this->dic[TaskApi::class];
    }

    public function repositories(): RepositoryFactory
    {
        return $this->dic[RepositoryFactory::class];
    }

    public function restContext(): RestContext
    {
        return $this->dic[RestContext::class];
    }

    public function eventDispatcher(int $ass_id, int $user_id): Dispatcher
    {
        return $this->dic[EventApi::class]->dispatcher($ass_id, $user_id);
    }
}
