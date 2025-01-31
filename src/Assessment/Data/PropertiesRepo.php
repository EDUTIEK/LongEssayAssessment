<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use Edutiek\AssessmentService\Assessment\Data\Properties;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Properties as PropertiesModel;
use ilAccessHandler;
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

    public function one(int $ass_id): Properties
    {
        return (new PropertiesModel($ass_id))
            ->setTitle($this->data_cache->lookupTitle($ass_id))
            ->setDescription($this->data_cache->lookupDescription($ass_id));
    }

    public function save(Properties $properties): void
    {
        $object = $this->factory::getInstanceByObjId($properties->getAssId());
        $object->setTitle($properties->getTitle());
        $object->setDescription($properties->getDescription());
        $object->update();

        $this->data_cache->deleteCachedEntry($properties->getAssId());
    }
}
