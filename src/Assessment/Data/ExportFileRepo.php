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
use Edutiek\AssessmentService\Assessment\Data\ExportFile;

class ExportFileRepo implements \Edutiek\AssessmentService\Assessment\Data\ExportFileRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): ExportFile
    {
        return $this->repo->new();
    }

    public function one(int $id): ?ExportFile
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function allByAssId(int $ass_id): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id]);
    }

    public function hasByAssIdAndFileId(int $ass_id, string $file_id): bool
    {
        return $this->repo->hasBy(['ass_id' => $ass_id, 'file_id' => $file_id]);
    }

    public function allByAssIdAndFileIds(int $ass_id, array $file_ids): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id, 'file_id' => $file_ids ]);
    }

    public function save(ExportFile $entity): void
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
