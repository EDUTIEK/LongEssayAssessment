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

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use Edutiek\AssessmentService\System\Data\UserAuth;

readonly class UserAuthRepo implements \Edutiek\AssessmentService\System\Data\UserAuthRepo
{
    public function __construct(private RepositoryInterface $repo)
    {
    }

    public function get(int $user_id): UserAuth
    {
        return $this->repo->queryOneBy(['user_id' => $user_id])
            ?? ($this->repo->new())->setUserId($user_id);
    }

    public function save(UserAuth $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $user_id): void
    {
        $this->repo->deleteAllBy(['user_id' => $user_id]);
    }
}
