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

namespace ILIAS\Plugin\LongEssayAssessment\EssayTask\Data;

use Edutiek\AssessmentService\EssayTask\Data\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class TaskSettingsRepo implements \Edutiek\AssessmentService\EssayTask\Data\TaskSettingsRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): TaskSettings
    {
        return $this->repo->new();
    }

    public function hasByAssId(int $ass_id): bool
    {
        return $this->repo->queryOneBy(['ass_id' => $ass_id]) !== null;
    }

    public function one(int $task_id): ?TaskSettings
    {
        return $this->repo->queryOneBy(['task_id' => $task_id]);
    }

    public function save(TaskSettings $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $task_id): void
    {
        $this->repo->deleteAllBy(['task_id' => $task_id]);
    }
}
