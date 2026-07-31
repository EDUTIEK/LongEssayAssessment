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

use Edutiek\AssessmentService\Assessment\TaskInterfaces\GradingPosition;
use Edutiek\AssessmentService\Task\Data\CorrectorAssignment;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class CorrectorAssignmentRepo implements \Edutiek\AssessmentService\Task\Data\CorrectorAssignmentRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): CorrectorAssignment
    {
        return $this->repo->new();
    }

    public function countByWriterIds(array $writer_ids, array $positions): int
    {
        return $this->repo->countBy(['writer_id' => $writer_ids, 'position' =>
            array_map(fn($position) => $position->value, $positions)]);
    }

    public function hasByIds(int $writer_id, int $corrector_id, int $task_id): bool
    {
        return $this->repo->hasBy(['writer_id' => $writer_id, 'corrector_id' => $corrector_id, 'task_id' => $task_id]);
    }

    public function oneByIds(int $writer_id, int $corrector_id, int $task_id): ?CorrectorAssignment
    {
        return $this->repo->queryOneBy(['writer_id' => $writer_id, 'corrector_id' => $corrector_id, 'task_id' => $task_id]);
    }

    public function oneByPosition(int $task_id, int $writer_id, GradingPosition $position): ?CorrectorAssignment
    {
        return $this->repo->queryOneBy(['task_id' => $task_id, 'writer_id' => $writer_id, 'position' => $position->value]);
    }

    public function allByAssId(int $ass_id): array
    {
        $query = "SELECT assignments.*, settings.ass_id FROM xlas_ta_corr_assign AS assignments"
            . " JOIN xlas_ta_settings AS settings ON (settings.task_id = assignments.task_id)"
            . " WHERE " . $this->repo->where(['ass_id' => $ass_id]);

        return $this->repo->queryAll($query);
    }

    public function allByTaskId(int $task_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id]);
    }

    public function allByWriterId(int $writer_id): array
    {
        return $this->repo->queryAllBy(['writer_id' => $writer_id]);
    }

    public function allByTaskIdAndWriterId(int $task_id, int $writer_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id, 'writer_id' => $writer_id]);
    }

    public function allByCorrectorId(int $corrector_id): array
    {
        return $this->repo->queryAllBy(['corrector_id' => $corrector_id]);
    }

    public function save(CorrectorAssignment $entity): void
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

    public function deleteByCorrectorId(int $corrector_id): void
    {
        $this->repo->deleteAllBy(['corrector_id' => $corrector_id]);
    }

    public function deleteByWriterIdAndCorrectorId(int $writer_id, int $corrector_id): void
    {
        $this->repo->deleteAllBy(['writer_id' => $writer_id, 'corrector_id' => $corrector_id]);
    }

    public function oneById(int $id): ?CorrectorAssignment
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function deleteByTaskIdAndWriterId(int $task_id, mixed $writer_id)
    {
        $this->repo->deleteAllBy(['writer_id' => $writer_id, 'task_id' => $task_id]);
    }

}
