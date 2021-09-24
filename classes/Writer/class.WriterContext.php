<?php


namespace ILIAS\Plugin\LongEssayTask\Writer;
use Edutiek\LongEssayService\Writer\Context;

class WriterContext implements Context
{

    /**
     * @inheritDoc
     */
    public function getFrontendUrl(): string
    {
        return  ILIAS_HTTP_PATH . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask/vendor/edutiek/node_modules/long-essay-writer/index.html";
    }

    /**
     * @inheritDoc
     */
    public function getBackendUrl(): string
    {
        return  ILIAS_HTTP_PATH . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask/service.php";
    }


    public function storeText() {

    }
}