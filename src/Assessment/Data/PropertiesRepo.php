<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use Edutiek\AssessmentService\Assessment\Data\Properties;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Properties as PropertiesModel;
use ilAccessHandler;
use ilObject;
use ilObjectDataCache;
use ilObjectFactory;

class PropertiesRepo implements \Edutiek\AssessmentService\Assessment\Data\PropertiesRepo
{
    private array $instances = [];

    public function __construct(
        private readonly ilObjectDataCache $data_cache,
        private readonly ilObjectFactory $factory
    ) {
    }

    public function exists(int $ass_id, int $context_id): bool
    {
        return ilObject::_exists($context_id, true, 'xlas')
            && !ilObject::_isInTrash($context_id);
    }

    public function one(int $ass_id): Properties
    {
        return (new PropertiesModel($ass_id))
            ->setTitle($this->data_cache->lookupTitle($ass_id))
            ->setDescription($this->data_cache->lookupDescription($ass_id));
    }

    public function save(Properties $entity): void
    {
        $object = $this->factory::getInstanceByObjId($entity->getAssId());
        $object->setTitle($entity->getTitle());
        $object->setDescription($entity->getDescription());
        $object->update();

        $this->data_cache->deleteCachedEntry($entity->getAssId());
    }
}
