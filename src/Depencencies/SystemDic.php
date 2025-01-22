<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\System\Data\ConfigRepo;
use ILIAS\DI\Container;
use ilLongEssayAssessmentPlugin;
use Edutiek\AssessmentService\System\File\Storage;
use Edutiek\AssessmentService\System\File\Delivery;
use ILIAS\Plugin\LongEssayAssessment\Common\DeliveryWrapper;
use ILIAS\ResourceStorage\StorageHandler\StorageHandlerFactory;
use ILIAS\ResourceStorage\StorageHandler\FileSystemBased\MaxNestingFileSystemStorageHandler;
use ILIAS\FileUpload\Location;
use ILIAS\ResourceStorage\StorageHandler\FileSystemBased\FileSystemStorageHandler;

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
                $dic->filesystem()->web()
            );
        };

        $dic[DeliveryWrapper::class] = function(Container $dic) {
            return new DeliveryWrapper(
                $dic->resourceStorage()->manage(),
                new StorageHandlerFactory(
                    [
                        new MaxNestingFileSystemStorageHandler($dic->filesystem()->storage(), Location::STORAGE),
                        new FileSystemStorageHandler($dic->filesystem()->storage(), Location::STORAGE)
                    ],
                    (defined('ILIAS_DATA_DIR') && defined('CLIENT_ID'))
                        ? rtrim(ILIAS_DATA_DIR, "/") . "/" . CLIENT_ID
                        : '-'
                ),
                $dic->http()
            );
        };
    }

    public function configRepo(): ConfigRepo
    {
        return $this->dic[ConfigRepo::class];
    }

    public function fileStorage(): Storage
    {
        // TODO: Implement fileStorage() method.
    }

    public function fileDelivery(): Delivery
    {
        return $this->dic[DeliveryWrapper::class];
    }
}
