<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\System\Data\Config;
use ILIAS\Plugin\LongEssayAssessment\System\Data\ConfigRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\SetupRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDataRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDisplayRepo;
use ILIAS\Plugin\LongEssayAssessment\System\File\DeliveryAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\File\StorageAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\File\Stakeholder;
use ILIAS\DI\Container;
use ILIAS\ResourceStorage\Resource\ResourceBuilder;
use ilLongEssayAssessmentPlugin;
use InitResourceStorage;
use ilUserQuery;
use ilUserUtil;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\CacheRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\DatabaseRepository;

/**
 *  Dependencies of the assessment services component "System"
 */
class SystemDic implements \Edutiek\AssessmentService\System\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {
    }

    public function configRepo(): ConfigRepo
    {
        return new ConfigRepo(
            new CacheRepository(
                new DatabaseRepository(
                    $this->dic->database(),
                    $this->dic[Generate::class]->readModel(Config::class)
                )
            )
        );
    }

    public function setupRepo(): SetupRepo
    {
        return new SetupRepo(
            $this->dic->clientIni(),
            $this->dic->filesystem()->web(),
            $this->dic->language()
        );
    }

    public function fileStorage(): StorageAdapter
    {
        return new StorageAdapter(
            $this->dic->resourceStorage()->manage(),
            $this->dic->resourceStorage()->consume(),
            new ResourceBuilder(
                $this->dic[InitResourceStorage::D_STORAGE_HANDLERS],
                $this->dic[InitResourceStorage::D_REPOSITORIES],
                $this->dic[InitResourceStorage::D_LOCK_HANDLER],
                $this->dic[InitResourceStorage::D_STREAM_ACCESS],
                $this->dic[InitResourceStorage::D_FILENAME_POLICY],
            ),
            new Stakeholder(SYSTEM_USER_ID)
        );
    }

    public function fileDelivery(): DeliveryAdapter
    {
        return new DeliveryAdapter(
            $this->dic->resourceStorage()->manage(),
            $this->dic[InitResourceStorage::D_STORAGE_HANDLERS],
            $this->dic->http()
        );
    }

    public function userDataRepo(): UserDataRepo
    {
        return new UserDataRepo(
            $this->dic->database(),
            $this->dic->language(),
            $this->dic->user(),
            new ilUserQuery()
        );
    }

    public function userDisplayRepo(): UserDisplayRepo
    {
        return new UserDisplayRepo(new ilUserUtil());
    }
}
