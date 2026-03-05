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

use Edutiek\AssessmentService\Task\Data\CorrectorSummary;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class CorrectorSummaryRepo implements \Edutiek\AssessmentService\Task\Data\CorrectorSummaryRepo
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

    public function hasAuthorizedByAssId(int $ass_id, ?int $corrector_id = null): bool
    {
        $where = ['ass_id' => $ass_id];

        if ($corrector_id !== null) {
            $where['corrector_id'] = $corrector_id;
        }

        $query = "
            SELECT summary.*, settings.ass_id AS ass_id
            FROM xlas_ta_corr_summary AS summary
            JOIN xlas_ta_settings settings ON settings.task_id = summary.task_id
            WHERE summary.corection_authorized IS NOT NULL AND" . $this->repo->where($where);

        return $this->repo->queryOne($query) !== null;
    }

    /**
     * @param int $ass_id
     * @return CorrectorSummary[]
     */
    public function allByAssId(int $ass_id): array
    {
        #TODO rewrite

        $query = "
            SELECT summary.*, settings.ass_id AS ass_id
            FROM xlas_ta_corr_summary AS summary
            JOIN xlas_ta_settings settings ON settings.task_id = summary.task_id
            WHERE " . $this->repo->where(['ass_id' => $ass_id]);

        $summaries = [];
        foreach ($this->repo->queryAllRaw($query) as $row) {
            $summaries[] = $this->repo->fromRow($row);
        }
        return $summaries;
    }

    /**
     * @return CorrectorSummary[]
     */
    public function allByWriterId(int $writer_id): array
    {
        return $this->repo->queryAllBy(['writer_id' => $writer_id]);
    }

    /**
     * @return CorrectorSummary[]
     */
    public function allByTaskId(int $task_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id]);
    }

    /**
     * @return CorrectorSummary[]
     */
    public function allByTaskIdAndWriterIds(int $task_id, array $writer_ids): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id, 'writer_id' => $writer_ids]);
    }

    /**
     * @return CorrectorSummary[]
     */
    public function allByTaskIdAndWriterId(int $task_id, int $writer_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id, 'writer_id' => $writer_id]);
    }

    /**
     * @return CorrectorSummary[]
     */
    public function allByTaskIdAndCorrectorId(int $task_id, int $corrector_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id, 'corrector_id' => $corrector_id]);
    }

    /**
     * @return CorrectorSummary[]
     */
    public function allByCorrectorId(int $corrector_id): array
    {
        return $this->repo->queryAllBy(['corrector_id' => $corrector_id]);
    }

    /**
     * @return CorrectorSummary[]
     */
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

    public function deleteByCorrectorId(int $corrector_id): void
    {
        $this->repo->deleteAllBy(['corrector_id' => $corrector_id]);
    }

    public function hasByTaskIdAndWriterId(int $task_id, int $writer_id): bool
    {
        return null !== $this->repo->queryOneBy(['task_id' => $task_id, 'writer_id' => $writer_id]);
    }

    public function oneByTaskIdAndWriterIdAndCorrectorId(int $task_id, int $writer_id, int $corrector_id): ?CorrectorSummary
    {
        return $this->repo->queryOneBy(['task_id' => $task_id, 'writer_id' => $writer_id, 'corrector_id' => $corrector_id]);
    }

    public function oneByPdf(string $file_id): ?CorrectorSummary
    {
        return $this->repo->queryOneBy(['summary_pdf' => $file_id]);
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
        $summary = $this->oneByTaskIdAndWriterIdAndCorrectorId($task_id, $writer_id, $from_corrector);
        if ($summary) {
            $summary->setCorrectorId($to_corrector);
            $this->repo->update($summary);
        }
    }
}
