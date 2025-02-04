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

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use Edutiek\AssessmentService\Assessment\Data\Location;

class LocationRepo implements \Edutiek\AssessmentService\Assessment\Data\LocationRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): Location
    {
        return $this->repo->new();
    }

    public function one(int $id): ?Location
    {
        return $this->queryOneBy(['id' => $id]);
    }

    public function allByAssId(int $ass_id): array
    {
        return $this->queryAllBy(['ass_id' => $ass_id]);
    }

    public function save(Location $entity): void
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

    public function examples(): array
    {
        // @TODO: Not sure if this is what is intended here.

        return $this->repo->queryAll('SELECT *, DISTINCT(title) FROM ' . $this->repo->table() . ' ORDER BY title LIMIT 0, 100');
    }
}
