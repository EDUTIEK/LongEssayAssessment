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

use Edutiek\AssessmentService\Task\Data\CorrectorSnippet;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

readonly class CorrectorSnippetRepo implements \Edutiek\AssessmentService\Task\Data\CorrectorSnippetRepo
{
    public function __construct(private RepositoryInterface $repo)
    {
    }

    public function new(): CorrectorSnippet
    {
        return $this->repo->new();
    }

    public function oneByKey(int $ass_id, int $corrector_id, string $key): ?CorrectorSnippet
    {
        return $this->repo->queryOneBy(['ass_id' => $ass_id, 'corrector_id' => $corrector_id, 'key' => $key]);
    }

    public function allByCorrectorId(int $ass_id, int $corrector_id): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id, 'corrector_id' => $corrector_id]);
    }

    public function save(CorrectorSnippet $entity): void
    {
        $this->repo->replace($entity);
    }

    public function deleteByKey(int $ass_id, int $corrector_id, string $key): void
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id, 'corrector_id' => $corrector_id, 'key' => $key]);
    }

    public function deleteByCorrectorId(int $corrector_id): void
    {
        $this->repo->deleteAllBy(['corrector_id' => $corrector_id]);
    }
}
