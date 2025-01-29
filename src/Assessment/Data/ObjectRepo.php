<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use Edutiek\AssessmentService\Assessment\Data\ObjectPermissions;
use Edutiek\AssessmentService\Assessment\Data\ObjectProperties;

use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\ObjectPermissions as ObjectPermissionsModel;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\ObjectProperties as ObjectPropertiesModel;

use ilAccessHandler;
use ilObjectDataCache;
use ilObjectFactory;

class ObjectRepo implements \Edutiek\AssessmentService\Assessment\Data\ObjectRepo
{
    private array $instances = [];

    public function __construct(
        private readonly ilAccessHandler $access,
        private readonly ilObjectDataCache $data_cache,
        private readonly ilObjectFactory $factory
    ) {
    }

    public function getObjectPermissions(int $ass_id, int $context_id, int $user_id): ObjectPermissions
    {
        return $this->instances[ObjectPermissions::class][$ass_id][$context_id][$user_id] ??= new ObjectPermissionsModel(
            $ass_id,
            $context_id,
            $user_id,
            $this->access->checkAccessOfUser($user_id, 'visible', '', $context_id, 'xlas', $ass_id ),
            $this->access->checkAccessOfUser($user_id, 'read', '', $context_id, 'xlas', $ass_id ),
            $this->access->checkAccessOfUser($user_id, 'write', '', $context_id, 'xlas', $ass_id ),
            $this->access->checkAccessOfUser($user_id, 'maintain_task', '', $context_id, 'xlas', $ass_id ),
            $this->access->checkAccessOfUser($user_id, 'maintain_writers', '', $context_id, 'xlas', $ass_id ),
            $this->access->checkAccessOfUser($user_id, 'maintain_correctors', '', $context_id, 'xlas', $ass_id ),
        );
    }

    public function getObjectProperties(int $ass_id): ObjectProperties
    {
        return (new ObjectPropertiesModel($ass_id))
            ->setTitle($this->data_cache->lookupTitle($ass_id))
            ->setDescription($this->data_cache->lookupDescription($ass_id));
    }

    public function saveObjectProperties(ObjectProperties $properties): void
    {
        $object = $this->factory::getInstanceByObjId($properties->getAssessmentId());
        $object->setTitle($properties->getTitle());
        $object->setDescription($properties->getDescription());
        $object->update();

        $this->data_cache->deleteCachedEntry($properties->getAssessmentId());
    }
}