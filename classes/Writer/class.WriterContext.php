<?php


namespace ILIAS\Plugin\LongEssayTask\Writer;
use Edutiek\LongEssayService\Writer\Context;
use Edutiek\LongEssayService\Writer\Service;

class WriterContext implements Context
{
    /** @var \ilLongEssayTaskPlugin */
    protected $plugin;

    public function __construct()
    {
        $this->$this->plugin = \ilLongEssayTaskPlugin::getInstance();
    }


    /**
     * @inheritDoc
     */
    public function getFrontendUrl(): string
    {
        $config = $this->plugin->getConfig();

        if (!empty($config->getWriterUrl())) {
            return $config->getWriterUrl();
        }
        else {
            return  ILIAS_HTTP_PATH
                . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask"
                . "/vendor/edutiek/long-essay-service"
                . "/" . Service::FRONTEND_RELATIVE_PATH;
        }
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