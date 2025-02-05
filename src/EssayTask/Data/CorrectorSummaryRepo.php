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

use Edutiek\AssessmentService\EssayTask\Data\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class CorrectorSummaryRepo implements \Edutiek\AssessmentService\EssayTask\Data\CorrectorSummaryRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): CorrectorSummary
    {
        return $this->repo->new();
    }

    public function one(int $id): ?CorrectorSummary
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function hasByEssayId(int $essay_id): bool
    {
        return null !== $this->repo->queryOneBy(['essay_id' => $essay_id]);
    }

    public function allByTaskId(int $task_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id]);
    }

    /**
     * Get the corrector summaries for a task and selected writer ids
     * Result is indexed by writer_id and corrector_id
     * @return CorrectorSummary[][]
     */
    public function allByTaskIdAndWriterIds(int $task_id, array $writer_ids): array
    {
        $query = "
            SELECT summary.*, essay.writer_id AS writer_id
            FROM xlas_et_corr_summary AS summary
            JOIN xlas_et_essay AS essay ON summary.essay_id = essay.id 
            WHERE "
            . $this->repo->where(['essay.task_id' => $task_id, 'essay.writer_id' => $writer_ids]);

        $summaries = [];
        foreach ($this->repo->queryAllRaw($query) as $row) {
            $summaries[$row['writer_id']][$row['corrector_id']] = $this->repo->fromRow($row);
        }
        return $summaries;
    }

    public function allByTaskIdAndCorrectorId(int $task_id, int $corrector_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id, 'corrector_id' => $corrector_id]);
    }

    public function allByEssayIdAndCorrectorId(int $essay_id, int $corrector_id): array
    {
        return $this->repo->queryAllBy(['essay_id' => $essay_id, 'corrector_id' => $corrector_id]);
    }

    public function save(CorrectorSummary $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $id): void
    {
        $this->repo->deleteAllBy(['id' => $id]);
    }

    public function deleteByEssayId(int $essay_id): void
    {
        $this->repo->deleteAllBy(['esay_id' => $essay_id]);
    }

    public function deleteByCorrectorId(int $corrector_id): void
    {
        $this->repo->deleteAllBy(['corrector_id' => $corrector_id]);
    }

    public function deleteByEssayIdAndCorrectorId(int $essay_id, int $corrector_id): void
    {
        $this->repo->deleteAllBy(['essay_id' => $essay_id, 'corrector_id' => $corrector_id]);
    }
}
