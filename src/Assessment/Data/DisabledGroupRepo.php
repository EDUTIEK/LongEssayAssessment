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
use Edutiek\AssessmentService\Assessment\Data\DisabledGroup;
use Exception;

readonly class DisabledGroupRepo implements \Edutiek\AssessmentService\Assessment\Data\DisabledGroupRepo
{
    /**
     * @param RepositoryInterface<DisabledGroup> $repo
     */
    public function __construct(private RepositoryInterface $repo)
    {
    }

    public function new(): DisabledGroup
    {
        return $this->repo->new();
    }

    public function allByAssId(int $ass_id): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id]);
    }

    public function deleteByAssId(int $ass_id): void
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id]);
    }

    public function save(DisabledGroup $group): void
    {
        $this->repo->insert($group);
    }
}
