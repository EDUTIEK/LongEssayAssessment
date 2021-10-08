<?php


namespace ILIAS\Plugin\LongEssayTask\Writer;
use Edutiek\LongEssayService\Writer\Context;
use Edutiek\LongEssayService\Writer\Service;

class WriterContext implements Context
{

    /**
     * @inheritDoc
     */
    public function getFrontendUrl(): string
    {
        return  ILIAS_HTTP_PATH
            . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask"
            . "/vendor/edutiek/long-essay-service"
            . "/" . Service::FRONTEND_RELATIVE_PATH;
    }

    /**
     * @inheritDoc
     */
    public function getBackendUrl(): string
    {
        return  ILIAS_HTTP_PATH
            . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask/service.php";
    }


    public function storeText() {

    }
}