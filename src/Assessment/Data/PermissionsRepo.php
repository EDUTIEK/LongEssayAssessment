<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use Edutiek\AssessmentService\Assessment\Data\Permissions;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Permissions as PermissionsModel;
use ilAccessHandler;

class PermissionsRepo implements \Edutiek\AssessmentService\Assessment\Data\PermissionsRepo
{
    private array $instances = [];

    public function __construct(
        private readonly ilAccessHandler $access
    ) {
    }

    public function one(int $ass_id, int $context_id, int $user_id): ?Permissions
    {
        return $this->instances[Permissions::class][$ass_id][$context_id][$user_id] ??= new PermissionsModel(
            $ass_id,
            $context_id,
            $user_id,
            $this->access->checkAccessOfUser($user_id, 'visible', '', $context_id, 'xlas', $ass_id),
            $this->access->checkAccessOfUser($user_id, 'read', '', $context_id, 'xlas', $ass_id),
            $this->access->checkAccessOfUser($user_id, 'write', '', $context_id, 'xlas', $ass_id),
            $this->access->checkAccessOfUser($user_id, 'maintain_task', '', $context_id, 'xlas', $ass_id),
            $this->access->checkAccessOfUser($user_id, 'maintain_writers', '', $context_id, 'xlas', $ass_id),
            $this->access->checkAccessOfUser($user_id, 'maintain_correctors', '', $context_id, 'xlas', $ass_id),
        );
    }
}
