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

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use Edutiek\AssessmentService\Assessment\Data\CorrectionStatus;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Value;
use Edutiek\AssessmentService\Assessment\Data\Writer;

class WriterRepo implements \Edutiek\AssessmentService\Assessment\Data\WriterRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): Writer
    {
        return $this->repo->new();
    }

    public function one(int $id): ?Writer
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function hasByWriterIdAndAssId(int $writer_id, int $ass_id): bool
    {
        return $this->repo->hasBy(['id' => $writer_id, 'ass_id' => $ass_id]);
    }

    public function oneByUserIdAndAssId(int $user_id, int $ass_id): ?Writer
    {
        return $this->repo->queryOneBy(['user_id' => $user_id, 'ass_id' => $ass_id]);
    }

    public function idsByAssId(int $ass_id): array
    {
        return $this->repo->queryIntegersBy(['ass_id' => $ass_id], 'id');
    }

    public function allByUserId(int $user_id): array
    {
        return $this->repo->queryAllBy(['user_id' => $user_id]);
    }

    public function allByUserIdsAndAssId(array $user_ids, int $ass_id): array
    {
        return $this->repo->queryAllBy(['user_id' => $user_ids, 'ass_id' => $ass_id]);
    }

    public function allByWriterIdsAndAssId(array $writer_ids, int $ass_id): array
    {
        return $this->repo->queryAllBy(['id' => $writer_ids, 'ass_id' => $ass_id]);
    }

    public function allByAssId(int $ass_id): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id]);
    }

    public function correctableIds(int $ass_id): array
    {
        return $this->repo->queryIntegersBy([
            'ass_id' => $ass_id,
            'writing_authorized' => Value::NOT_NULL,
            'writing_excluded_by' => Value::NULL,
        ], 'id');
    }

    public function save(Writer $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $id): void
    {
        $this->repo->deleteAllBy(['id' => $id]);
    }

    public function deleteByAssId(int $ass_id): void
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id]);
    }

    public function hasStitchDecisions(int $ass_id): bool
    {
        return $this->repo->hasBy(['ass_id' => $ass_id, "correction_status" => CorrectionStatus::STITCH->value]);
    }
}
