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

use Edutiek\AssessmentService\EssayTask\Data\EssayImage;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class EssayImageRepo implements \Edutiek\AssessmentService\EssayTask\Data\EssayImageRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): EssayImage
    {
        return $this->repo->new();
    }

    public function one(int $id): ?EssayImage
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function allByEssayId(int $essay_id): array
    {
        return $this->repo->queryAllBy(['essay_id' => $essay_id]);
    }

    public function save(EssayImage $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $id): void
    {
        $this->repo->deleteAllBy(['id' => $id]);
    }

    public function deleteByEssayId(int $essay_id): void
    {
        $this->repo->deleteAllBy(['essay_id' => $essay_id]);
    }
}
