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

use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskType;
use Edutiek\AssessmentService\EssayTask\Api\ForTask as EssayTaskTypeApi;
use Edutiek\AssessmentService\Task\TypeInterfaces\Api as TypeApi;
use Edutiek\AssessmentService\Task\TypeInterfaces\ApiFactory as TypeApiFactory;

class TypeApis implements TypeApiFactory
{
    public function __construct(
        private EssayTaskTypeApi $essay_task
    ) {
    }

    public function api(TaskType $type): TypeApi
    {
        switch ($type) {
            case TaskType::ESSAY:
                return $this->essay_task;
        }

        throw new \Exception("Unknown task type");
    }
}
