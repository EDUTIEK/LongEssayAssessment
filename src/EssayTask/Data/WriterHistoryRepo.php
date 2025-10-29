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

use Edutiek\AssessmentService\EssayTask\Data\WritingStep;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class WritingStepRepo implements \Edutiek\AssessmentService\EssayTask\Data\WritingStepRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): WritingStep
    {
        return $this->repo->new();
    }

    public function one(int $id): ?WritingStep
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function hasByEssayIdAndHashAfter(int $essay_id, string $hash_after): bool
    {
        return null !== $this->repo->queryOneBy(['essay_id' => $essay_id, 'hash_after' => $hash_after]);
    }

    public function allByEssayId(int $essay_id): array
    {
        return $this->repo->queryAllBy(['essay_id' => $essay_id]);
    }

    public function create(WritingStep $entity): void
    {
        $this->repo->insert($entity);
    }

    public function deleteByEssayId(int $essay_id): void
    {
        $this->repo->deleteAllBy(['essay_id' => $essay_id]);
    }
}
