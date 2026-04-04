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
use Edutiek\AssessmentService\Assessment\Data\NotificationQueue;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

readonly class NotificationQueueRepo implements \Edutiek\AssessmentService\Assessment\Data\NotificationQueueRepo
{
    public function __construct(
        private RepositoryInterface $repo
    ) {
    }

    public function new(): NotificationQueue
    {
        return $this->repo->new();
    }

    public function allByType(NotificationType $type): array
    {
        return $this->repo->queryAllBy(['type' => $type->value]);
    }

    public function allByAssIdAndType(int $ass_id, NotificationType $type): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id, 'type' => $type->value]);
    }

    public function save(NotificationQueue $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(NotificationQueue $entity): void
    {
        $this->repo->delete($entity);
    }

    public function deleteByAssId(int $ass_id)
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id]);
    }

    public function deleteByUserId(int $user_id)
    {
        $this->repo->deleteAllBy(['user_id' => $user_id]);
    }

    public function deleteByAssIdAndType(int $ass_id, NotificationType $type)
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id, 'type' => $type->value]);
    }
}
