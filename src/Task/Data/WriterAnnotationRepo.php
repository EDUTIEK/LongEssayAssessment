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

use Edutiek\AssessmentService\Task\Data\WriterAnnotation;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class WriterAnnotationRepo implements \Edutiek\AssessmentService\Task\Data\WriterAnnotationRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): WriterAnnotation
    {
        return $this->repo->new();
    }

    public function one(int $id): ?WriterAnnotation
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function oneByResourceIdAndMarkKey(int $resource_id, string $mark_key): ?WriterAnnotation
    {
        return $this->repo->queryOneBy(['resource_id' => $resource_id, 'mark_key' => $mark_key]);
    }

    public function allByTaskId(int $task_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id]);
    }

    public function allByWriterId(int $writer_id): array
    {
        return $this->repo->queryAllBy(['writer_id' => $writer_id]);
    }

    public function save(WriterAnnotation $entity): void
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
