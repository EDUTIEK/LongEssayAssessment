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

use Edutiek\AssessmentService\Task\Data\CorrectorComment;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class CorrectorCommentRepo implements \Edutiek\AssessmentService\Task\Data\CorrectorCommentRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): CorrectorComment
    {
        return $this->repo->new();
    }

    public function one(int $id): ?CorrectorComment
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }
    public function oneByTaskIdAndWriterIdAndKey(int $task_id, int $writer_id, string $key): ?CorrectorComment
    {
        return $this->repo->queryOneBy(['task_id' => $task_id, 'writer_id' => $writer_id, 'key' => $key]);
    }
    public function hasByAssId(int $ass_id): bool
    {
        $sql = "
            SELECT c.id FROM xlas_ta_corr_comm c
            JOIN xlas_ta_settings t ON t.task_id = c.task_id
            WHERE t.ass_id = $ass_id
            LIMIT 1
        ";

        return null != $this->repo->queryIntegers($sql, 'id');
    }

    public function save(CorrectorComment $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $id): void
    {
        $this->repo->deleteAllBy(['id' => $id]);
    }

    public function deleteByCorrectorId(int $corrector_id): void
    {
        $this->repo->deleteAllBy(['corrector_id' => $corrector_id]);
    }

    public function hasByTaskIdAndWriterId(int $task_id, int $writer_id): bool
    {
        return null !== $this->repo->queryOneBy(['task_id' => $task_id, 'writer_id' => $writer_id]);
    }

    public function allByTaskIdAndWriterIdAndCorrectorId(int $task_id, int $writer_id, int $corrector_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id, 'writer_id' => $writer_id, 'corrector_id' => $corrector_id]);
    }

    public function deleteByTaskId(int $task_id): void
    {
        $this->repo->deleteAllBy(['task_id' => $task_id]);
    }

    public function deleteByTaskIdAndWriterId(int $task_id, int $writer_id): void
    {
        $this->repo->deleteAllBy(['task_id' => $task_id, 'writer_id' => $writer_id]);
    }

    public function deleteByTaskIdAndWriterIdAndCorrectorId(int $task_id, int $writer_id, int $corrector_id): void
    {
        $this->repo->deleteAllBy(['task_id' => $task_id, 'writer_id' => $writer_id, 'corrector_id' => $corrector_id]);
    }

    public function moveCorrectorByTaskIdAndWriterId(int $task_id, int $writer_id, int $from_corrector, int $to_corrector): void
    {
        foreach ($this->allByTaskIdAndWriterIdAndCorrectorId($task_id, $writer_id, $from_corrector) as $object) {
            $object->setCorrectorId($to_corrector);
            $this->repo->update($object);
        }
    }
}
