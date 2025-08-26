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

class DisabledGroupRepo implements \Edutiek\AssessmentService\Assessment\Data\DisabledGroupRepo
{
    /**
     * @param RepositoryInterface<DisabledGroup> $repo
     */
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function get(int $ass_id): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id]);
    }

    public function save(int $ass_id, array $groups): void
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id]);
        foreach ($groups as $group) {
            if (is_string($group)) {
                $group = $this->repo->new()->setName($group);
                $group->setAssId($ass_id);
            } elseif ($group->getAssId() !== $ass_id) {
                throw new Exception(sprintf(
                    'DisabledGroup item has wrong assessment id (expected: %d, actual %d)',
                    $ass_id,
                    $group->getAssId()
                ));
            }
            
            $this->repo->insert($group);
        }
    }
}
