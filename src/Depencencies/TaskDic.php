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

use Edutiek\AssessmentService\Task\TypeInterfaces\ApiFactory as TypeApiFactory;
use ILIAS\DI\Container;
use Edutiek\AssessmentService\System\Api\Factory as SystemFactory;
use Edutiek\AssessmentService\System\Api\ForServices as SystemApi;
use ILIAS\Plugin\LongEssayAssessment\Task\Data\RepositoryFactory;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use Edutiek\AssessmentService\EssayTask\Api\Factory as EssayTaskFactory;

class TaskDic implements \Edutiek\AssessmentService\Task\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {
        $dic[SystemApi::class] = function (Container $dic) {
            return $dic[SystemFactory::class]->forServices();
        };

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

}
