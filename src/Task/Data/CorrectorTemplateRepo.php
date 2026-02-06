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

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use Edutiek\AssessmentService\Task\Data\CorrectorTemplate;

readonly class CorrectorTemplateRepo implements \Edutiek\AssessmentService\Task\Data\CorrectorTemplateRepo
{
    public function __construct(private RepositoryInterface $repo)
    {
    }

    public function one(int $id): ?CorrectorTemplate
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function new(int $task_id, int $corrector_id): CorrectorTemplate
    {
        return $this->repo->new()->setTaskId($task_id)->setCorrectorId($corrector_id);
    }

    public function oneByTaskIdAndCorrectorId(int $task_id, int $corrector_id): ?CorrectorTemplate
    {
        return $this->repo->queryOneBy(['task_id' => $task_id, 'corrector_id' => $corrector_id]);
    }

    public function allByCorrectorId(int $corrector_id): array
    {
        return $this->repo->queryAllBy(['corrector_id' => $corrector_id]);
    }

    public function allByTaskId(int $task_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id]);
    }

    public function sharableCorrectorIds(int $task_id): array
    {
        return $this->repo->queryIntegersBy(['task_id' => $task_id, 'shared' => 1], 'corrector_id');
    }

    public function save(CorrectorTemplate $entity): void
    {
        $this->repo->replace($entity);
    }

    public function deleteByTaskId(int $task_id): void
    {
        $this->repo->deleteAllBy(['task_id' => $task_id]);
    }

    public function deleteByCorrectorId(int $corrector_id): void
    {
        $this->repo->deleteAllBy(['corrector_id' => $corrector_id]);
    }
}
