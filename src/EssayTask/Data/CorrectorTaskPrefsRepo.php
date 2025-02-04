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

use Edutiek\AssessmentService\EssayTask\Data\CorrectorTaskPrefs;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class CorrectorTaskPrefsRepo implements \Edutiek\AssessmentService\EssayTask\Data\CorrectorTaskPrefsRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): CorrectorTaskPrefs
    {
        return $this->repo->new();
    }

    public function oneByCorrectorIdAndTaskId(int $corrector_id, int $task_id): ?CorrectorTaskPrefs
    {
        return $this->repo->queryOneBy(['corrector_id' => $corrector_id, 'task_id' => $task_id]);
    }

    public function save(CorrectorTaskPrefs $entity): void
    {
        $this->repo->replace($entity);
    }

    public function deleteByCorrectorIdAndTaskId(int $corrector_id, int $task_id): void
    {
        $this->repo->deleteAllBy(['corrector_id' => $corrector_id, 'task_id' => $task_id]);
    }
}
