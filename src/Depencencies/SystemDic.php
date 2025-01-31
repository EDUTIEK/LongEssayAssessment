<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\System\Data\ConfigRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\SetupRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDataRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDisplayRepo;
use ILIAS\Plugin\LongEssayAssessment\System\File\DeliveryAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\File\StorageAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\File\Stakeholder;
use ILIAS\DI\Container;
use ilLongEssayAssessmentPlugin;
use InitResourceStorage;
use ilUserQuery;
use ilUserUtil;

/**
 * Dependency Container for the System Api
 */
class SystemDic implements \Edutiek\AssessmentService\System\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {
        $dic[ConfigRepo::class] = function (Container $dic) {
            return new ConfigRepo(
                $dic->database(),
                $dic[Generate::class],
            );
        };

        $dic[SetupRepo::class] = function (Container $dic) {
            return new SetupRepo(
                $dic->clientIni(),
                $dic[ilLongEssayAssessmentPlugin::class],
                $dic->filesystem()->web(),
                $dic->language()
            );
        };

        $dic[UserDataRepo::class] = function (Container $dic) {
            return new UserDataRepo(
                $dic->database(),
                $dic->language(),
                $dic->user(),
                new ilUserQuery()
            );
        };

        $dic[UserDisplayRepo::class] = function (Container $dic) {
            return new UserDisplayRepo(
                new ilUserUtil()
            );
        };

        $dic[DeliveryAdapter::class] = function (Container $dic) {
            return new DeliveryAdapter(
                $dic->resourceStorage()->manage(),
                $dic[InitResourceStorage::D_STORAGE_HANDLERS],
                $dic->http()
            );
        };

        $dic[StorageAdapter::class] = function (Container $dic) {
            return new StorageAdapter(
                $dic->resourceStorage()->manage(),
                $dic->resourceStorage()->consume(),
                $dic[InitResourceStorage::D_RESOURCE_BUILDER],
                new Stakeholder(SYSTEM_USER_ID)
            );
        };
    }

    public function configRepo(): ConfigRepo
    {
        return $this->dic[ConfigRepo::class];
    }

    public function setupRepo(): SetupRepo
    {
        return $this->dic[SetupRepo::class];
    }

    public function fileStorage(): StorageAdapter
    {
        return $this->dic[StorageAdapter::class];
    }

    public function fileDelivery(): DeliveryAdapter
    {
        return $this->dic[DeliveryAdapter::class];
    }

    public function userDataRepo(): UserDataRepo
    {
        return $this->dic[UserDataRepo::class];
    }

    public function userDisplayRepo(): UserDisplayRepo
    {
        return $this->dic[UserDisplayRepo::class];
    }
}
