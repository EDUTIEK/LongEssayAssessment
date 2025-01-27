<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\System\Data\ConfigRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserRepo;
use ILIAS\DI\Container;
use ilLongEssayAssessmentPlugin;
use Edutiek\AssessmentService\System\File\Storage;
use Edutiek\AssessmentService\System\File\Delivery;
use ILIAS\Plugin\LongEssayAssessment\System\File\DeliveryAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\File\StorageAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\File\Stakeholder;
use InitResourceStorage;
use ilUserQuery;

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
                $dic->clientIni(),
                $dic[ilLongEssayAssessmentPlugin::class],
                $dic->filesystem()->web(),
                $dic->language()
            );
        };

        $dic[UserRepo::class] = function (Container $dic) {
            return new UserRepo(
                $dic->database(),
                $dic->language(),
                $dic->user(),
                new ilUserQuery()
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

    public function fileStorage(): Storage
    {
        return $this->dic[StorageAdapter::class];
    }

    public function fileDelivery(): Delivery
    {
        return $this->dic[DeliveryAdapter::class];
    }

    public function userRepo(): UserRepo
    {
        return $this->dic[UserRepo::class];
    }
}
