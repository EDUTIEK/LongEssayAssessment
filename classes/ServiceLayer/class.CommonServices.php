<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer;

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Object\IliasContext;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common\FileHelper;
use ILIAS\ResourceStorage\StorageHandler\StorageHandlerFactory;
use ILIAS\ResourceStorage\StorageHandler\FileSystemBased\MaxNestingFileSystemStorageHandler;
use ILIAS\FileUpload\Location;
use ILIAS\ResourceStorage\StorageHandler\FileSystemBased\FileSystemStorageHandler;
use ILIAS\FileDelivery\Delivery;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common\UserDataHelper;

/**
 * Container for common services
 */
class CommonServices
{
    protected \ILIAS\DI\Container $global_dic;
    protected LongEssayAssessmentDI $local_dic;
    protected \Pimple\Container $service_dic;

    /**
     * Constructor
     */
    public function __construct(
        \ILIAS\DI\Container $global_dic,
        LongEssayAssessmentDI $local_dic,
        \Pimple\Container $service_dic
    ) {
        $this->global_dic = $global_dic;
        $this->local_dic = $local_dic;
        $this->service_dic = $service_dic;

        // fill the container

        $service_dic['file_helper'] = function () {
            return new FileHelper(
                $this->global_dic->resourceStorage()->manage(),
                new StorageHandlerFactory(
                    [
                        new MaxNestingFileSystemStorageHandler($this->global_dic->filesystem()->storage(), Location::STORAGE),
                        new FileSystemStorageHandler($this->global_dic->filesystem()->storage(), Location::STORAGE)
                    ],
                    (defined('ILIAS_DATA_DIR') && defined('CLIENT_ID'))
                        ? rtrim(ILIAS_DATA_DIR, "/") . "/" . CLIENT_ID
                        : '-'
                ),
                $this->global_dic->http()
            );
        };

        $service_dic['user_data_helper'] = function () {
            return new UserDataHelper($this->global_dic->language(), $this->global_dic->ui()->factory());
        };
    }

    public function fileHelper() : FileHelper
    {
        return $this->service_dic['file_helper'];
    }

    public function userDataHelper() : UserDataHelper
    {
        return $this->service_dic['user_data_helper'];
    }
}
