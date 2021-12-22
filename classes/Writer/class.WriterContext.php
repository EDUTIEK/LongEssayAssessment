<?php


namespace ILIAS\Plugin\LongEssayTask\Writer;
use Edutiek\LongEssayService\Data\ApiToken;
use Edutiek\LongEssayService\Writer\Context;
use Edutiek\LongEssayService\Writer\Service;
use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayTask\Data\AccessToken;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;

class WriterContext implements Context
{
    /** @var \ilLongEssayTaskPlugin */
    protected $plugin;

    protected $user_id;
    protected $ref_id;
    protected $task_id;

    /** @var LongEssayTaskDI */
    protected $di;

    /** @var Essay */
    protected $essay;

    /**
     * @inheritDoc
     */
    function __construct()
    {
        $this->plugin = \ilLongEssayTaskPlugin::getInstance();
    }

    /**
     * @inheritDoc
     * here: use string versions of the user id and ref_id of the repository object
     */
    public function init(string $user_key, string $environment_key): bool
    {
        /** @var Container */
        global $DIC;


        // fix for missing ilUser in REST calls
        if (!$DIC->offsetExists('ilUser')) {
            $ilUser = new \ilObjUser(ANONYMOUS_USER_ID);
            $DIC['ilUser'] = function ($c) use($ilUser) {
                return $ilUser;
            };
        }

        $this->user_id = (int) $user_key;
        $this->ref_id = (int) $environment_key;
        $this->di = LongEssayTaskDI::getInstance();

        $task_id = \ilObject::_lookupObjectId($this->ref_id);
        $this->essay = $this->di->getEssayRepo()->getEssayByWriterIdAndTaskId($this->user_id, $task_id);

        return isset($this->essay);
    }

    /**
     * @inheritDoc
     * here: support a separate url from the plugin config (for development purposes)
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
     * here: URL of the writer_service script
     */
    public function getBackendUrl(): string
    {
        return  ILIAS_HTTP_PATH
            . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask/writer_service.php";
    }

    /**
     * @inheritDoc
     * here: just get the link to the repo object, the tab will be shown depending on the user permissions
     * The ILIAS session still has to exist, otherwise the user has to log in again
     */
    public function getReturnUrl(): string
    {
        return \ilLink::_getStaticLink($this->ref_id);
    }

    /**
     * @inheritDoc
     * here: get the string version of the user id
     */
    public function getUserKey(): string
    {
        return (string) $this->user_id;
    }

    /**
     * @inheritDoc
     * here: get the string version of the ref_id of the repository object
     */
    public function getEnvironmentKey(): string
    {
        return (string) $this->ref_id;
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
        $repo = $this->di->getEssayRepo();
        $token = $repo->getAccessTokenByUserIdAndEssayId($this->user_id, $this->essay->getId());
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
        $repo = $this->di->getEssayRepo();
        $token = $repo->getAccessTokenByUserIdAndEssayId($this->user_id, $this->essay->getId());
        if (isset($token)) {
            $repo->deleteAccessToken($token->getId());
        }

        // save the new token
        $token = new AccessToken();
        $token->setUserId($this->user_id);
        $token->setEssayId($this->essay->getId());
        try {
            $valid = new \ilDateTime($api_token->getExpires(), IL_CAL_UNIX);
        }
        catch (\ilDateTimeException $e) {
            $valid = '';
        }
        $token->setToken($api_token->getValue());
        $token->setIp($api_token->getIpAddress());
        $token->setValidUntil($valid->get(IL_CAL_DATETIME));
        $repo->createAccessToken($token);
    }
}