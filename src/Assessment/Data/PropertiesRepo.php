<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use Edutiek\AssessmentService\Assessment\Data\Properties;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Properties as PropertiesModel;
use ilAccessHandler;
use ilObject;
use ilObjectDataCache;
use ilObjectFactory;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\HydrationInterface;

class PropertiesRepo implements \Edutiek\AssessmentService\Assessment\Data\PropertiesRepo, HydrationInterface
{
    private array $instances = [];
    private array $dehydrated = [];

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

    public function dehydratedInstance(mixed $key_value): ?object
    {
        if ($key_value === null) {
            return null;
        }

        return $this->dehydrated[$key_value] ??= (new PropertiesModel($key_value));
    }

    public function hydrate(): void
    {
        $this->data_cache->preloadObjectCache(array_keys($this->dehydrated ?? []));

        $ret = [];
        foreach ($this->dehydrated as $id => $object) {
            unset($this->dehydrated[$id]);
            $object->setTitle($this->data_cache->lookupTitle($id))
                   ->setDescription($this->data_cache->lookupDescription($id));
        }
    }
}
