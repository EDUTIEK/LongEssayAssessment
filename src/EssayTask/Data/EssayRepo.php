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

use Edutiek\AssessmentService\EssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class EssayRepo implements \Edutiek\AssessmentService\EssayTask\Data\EssayRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function some(array $ids): array
    {
        return $this->repo->queryAllBy(['id' => $ids]);
    }

    public function new(): Essay
    {
        return $this->repo->new();
    }

    public function one(int $id): ?Essay
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function oneByWriterIdAndTaskId(int $writer_id, int $task_id): ?Essay
    {
        return $this->repo->queryOneBy(['writer_id' => $writer_id, 'task_id' => $task_id]);
    }

    public function allByAssId(int $ass_id): array
    {
        $query = "
            SELECT essay.*, settings.ass_id AS ass_id
            FROM xlas_et_essay AS essay
            JOIN xlas_et_task_settings AS settings ON essay.task_id = settings.task_id
            WHERE "
            . $this->repo->where(['ass_id' => $ass_id]);

        $essay = [];
        foreach ($this->repo->queryAllRaw($query) as $row) {
            $essay[] = $this->repo->fromRow($row);
        }
        return $essay;
    }

    public function allByTaskId(int $task_id): array
    {
        return $this->repo->queryallBy(['task_id' => $task_id]);
    }

    public function allByWriterId(int $writer_id): array
    {
        return $this->repo->queryAllBy(['writer_id' => $writer_id]);
    }

    public function save(Essay $entity): void
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

    public function deleteByWriterId(int $writer_id): void
    {
        $this->repo->deleteAllBy(['writer_id' => $writer_id]);
    }
}
