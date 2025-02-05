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

namespace ILIAS\Plugin\LongEssayAssessment\Task\Data;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use Edutiek\AssessmentService\Task\Data\Resource;

class ResourceRepo implements \Edutiek\AssessmentService\Task\Data\ResourceRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): Resource
    {
        return $this->repo->new();
    }

    public function one(int $id): ?Resource
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function oneByTaskIdAndType(int $task_id, string $type): ?Resource
    {
        return $this->repo->queryOneBy(['task_id' => $task_id, 'type' => $type]);
    }

    public function oneByFileId(string $file_id): ?Resource
    {
        return $this->repo->queryOneBy(['file_id' => $file_id]);
    }

    public function allByTaskId(int $task_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id]);
    }

    public function save(Resource $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $id): void
    {
        $this->repo->deleteAllBy(['id' => $id]);
    }

    public function deleteByTaskId(int $task_id): void
    {
        $this->repo->deleteAllBy(['task_id' => $task_id]);
    }
}
