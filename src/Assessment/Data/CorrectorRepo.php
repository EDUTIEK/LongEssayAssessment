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

use Edutiek\AssessmentService\Assessment\Data\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class CorrectorRepo implements \Edutiek\AssessmentService\Assessment\Data\CorrectorRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): Corrector
    {
        return $this->repo->new();
    }

    public function has(int $id): bool
    {
        return $this->repo->hasBy(['id' => $id]);
    }

    public function hasReports(): bool
    {
        $sql = "SELECT id FROM " . $this->repo->table() . " WHERE correction_report IS NOT NULL";
        return $this->repo->queryOne($sql) !== null;
    }

    public function hasByAssId(int $ass_id): bool
    {
        return $this->repo->hasBy(['ass_id' => $ass_id]);
    }

    public function hasByCorrectorIdAndAssId(int $corrector_id, int $ass_id): bool
    {
        return $this->repo->hasBy(['id' => $corrector_id, 'ass_id' => $ass_id]);
    }

    public function one(int $id): ?Corrector
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function oneByUserIdAndAssId(int $user_id, int $ass_id): ?Corrector
    {
        return $this->repo->queryOneBy(['user_id' => $user_id, 'ass_id' => $ass_id]);
    }

    public function some(array $ids): array
    {
        return $this->repo->queryAllBy(['id' => $ids]);
    }

    public function allByAssId(int $ass_id): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id]);
    }

    public function allByUserId(int $user_id): array
    {
        return $this->repo->queryAllBy(['user_id' => $user_id]);
    }

    public function idsByAssId(int $ass_id): array
    {
        return $this->repo->queryIntegersBy(['ass_id' => $ass_id], 'id');
    }

    public function save(Corrector $entity): void
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
}
