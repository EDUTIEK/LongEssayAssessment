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

use Edutiek\AssessmentService\Assessment\Data\NotificationType;
use Edutiek\AssessmentService\Assessment\Data\NotificationUser;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

readonly class NotificationUserRepo implements \Edutiek\AssessmentService\Assessment\Data\NotificationUserRepo
{
    public function __construct(
        private RepositoryInterface $repo
    ) {
    }

    public function new(): NotificationUser
    {
        return $this->repo->new();
    }

    public function allByAssIdAndType(int $ass_id, NotificationType $type): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id, 'type' => $type->value]);
    }

    public function save(NotificationUser $entity): void
    {
        $this->repo->update($entity);
    }

    public function deleteByAssIdAndType(int $ass_id, NotificationType $type): void
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id, 'type' => $type->value]);
    }

    public function deleteByAssId(int $ass_id)
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id]);
    }

    public function deleteByUserId(int $user_id)
    {
        $this->repo->deleteAllBy(['user_id' => $user_id]);
    }
}
