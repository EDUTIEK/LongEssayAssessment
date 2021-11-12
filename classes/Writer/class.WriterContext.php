<?php


namespace ILIAS\Plugin\LongEssayTask\Writer;
use Edutiek\LongEssayService\Data\ApiToken;
use Edutiek\LongEssayService\Writer\Context;
use Edutiek\LongEssayService\Writer\Service;
use ILIAS\Plugin\LongEssayTask\Data\AccessTokenDatabaseRepository;
use Symfony\Component\Yaml\Tests\A;

class WriterContext implements Context
{
    /** @var \ilLongEssayTaskPlugin */
    protected $plugin;

    protected int $user_id;
    protected int $task_id;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->plugin = \ilLongEssayTaskPlugin::getInstance();

    }

    /**
     * @inheritDoc
     */
    public function init(string $user_key, string $task_key): \Edutiek\LongEssayService\Base\Context
    {
        $this->user_id = (int) $user_key;
        $this->task_id = (int) $task_key;
        return $this;
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
            . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask/writer_service.php";
    }


    /**
     * @inheritDoc
     */
    public function getUserKey(): string
    {
        return (string) $this->user_id;
    }

    /**
     * @inheritDoc
     */
    public function getTaskKey(): string
    {
        return (string) $this->task_id;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultTokenLifetime(): int
    {
       return 3600;
    }


    /**
     * @inheritDoc
     */
    public function getApiToken(): ?ApiToken
    {
        $repo = new AccessTokenDatabaseRepository();
        $token = $repo->getTokenByUserAndTask($this->user_id, $this->task_id);
        if (isset($token)) {
            try {
                $valid = new \ilDateTime($token->getValidUntil(), IL_CAL_DATETIME);
            }
            catch (\ilDateTimeException $e) {
                $valid = 0;
            }
            return new ApiToken($token->getToken(), $token->getIp(), $valid->get(IL_CAL_UNIX));
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function setApiToken(ApiToken $api_token)
    {
        // delete an existing token
        $repo = new AccessTokenDatabaseRepository();
        $token = $repo->getTokenByUserAndTask($this->user_id, $this->task_id);
        if (isset($token)) {
            $repo->deleteToken($token->getId());
        }

        // save the new token
        $token = $repo->createToken($this->user_id, $this->task_id);
        try {
            $valid = new \ilDateTime($api_token->getExpires(), IL_CAL_UNIX);
        }
        catch (\ilDateTimeException $e) {
            $valid = '';
        }
        $token->setToken($api_token->getValue());
        $token->setIp($api_token->getIpAddress());
        $token->setValidUntil($valid->get(IL_CAL_DATETIME));
        $repo->saveToken($token);
    }
}