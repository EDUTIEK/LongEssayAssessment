<?php

namespace ILIAS\Plugin\LongEssayTask\Writer;


use Edutiek\LongEssayService\Data\ApiToken;

use Edutiek\LongEssayService\Writer\Context;
use Edutiek\LongEssayService\Writer\Service;
use ilContext;
use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayTask\Data\AccessToken;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;
use \ilObjUser;
use \ilObject;
use \ilObjLongEssayTask;

class WriterContext
{
    /** @var \ilLongEssayTaskPlugin */
    protected $plugin;

    /** @var LongEssayTaskDI */
    protected $di;

    /** @var ilObjLongEssayTask */
    protected $object;

    /** @var ilObjUser */
    protected $user;

    /**
     * @inheritDoc
     */
    function __construct()
    {
        $this->plugin = \ilLongEssayTaskPlugin::getInstance();
        $this->di = LongEssayTaskDI::getInstance();
    }

    /**
     * @inheritDoc
     * here: use string versions of the user id and ref_id of the repository object
     */
    public function init(string $user_key, string $environment_key)
    {
        $user_id = (int) $user_key;
        $ref_id = (int) $environment_key;

        if (!ilObject::_exists($user_id, false, 'usr')) {
            throw new ContextException('User does not exist', ContextException::USER_NOT_VALID);
        }
        if (!ilObject::_exists($ref_id, true, 'xlet')) {
            throw new ContextException('Object does not exist', ContextException::ENVIRONMENT_NOT_VALID);
        }
        if (ilObject::_isInTrash($ref_id)) {
            throw new ContextException('Object is deleted', ContextException::ENVIRONMENT_NOT_VALID);
        }

        $this->user = new ilObjUser($user_id);

        if (ilContext::getType() == ilContext::CONTEXT_REST) {
            \ilLongEssayTaskRestInit::initRestUser($this->user);
        }

        $this->object = new ilObjLongEssayTask($ref_id);

        if (!$this->object->isOnline()) {
            throw new ContextException('Object is offline', ContextException::ENVIRONMENT_NOT_VALID);
        }
        if (!$this->object->canViewWriterScreen()) {
            throw new ContextException('Writer not permitted', ContextException::PERMISSION_DENIED);
        }
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
            . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask/writer_service.php"
            . "?client_id=" . CLIENT_ID;
    }

    /**
     * @inheritDoc
     * here: just get the link to the repo object, the tab will be shown depending on the user permissions
     * The ILIAS session still has to exist, otherwise the user has to log in again
     */
    public function getReturnUrl(): string
    {
        return \ilLink::_getStaticLink($this->object->getRefId());
    }

    /**
     * @inheritDoc
     * here: get the string version of the user id
     */
    public function getUserKey(): string
    {
        return (string) $this->user->getId();
    }

    /**
     * @inheritDoc
     * here: get the string version of the ref_id of the repository object
     */
    public function getEnvironmentKey(): string
    {
        return (string) $this->object->getRefId();
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
        $token = $repo->getAccessTokenByUserIdAndTaskId($this->user->getId(), $this->object->getId());
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
        $token = $repo->getAccessTokenByUserIdAndTaskId($this->user->getId(), $this->object->getId());
        if (isset($token)) {
            $repo->deleteAccessToken($token->getId());
        }

        // save the new token
        $token = new AccessToken();
        $token->setUserId($this->user->getId());
        $token->setTaskId($this->object->getId());
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

    /**
     * Get the Task that should be done in the editor
     * The instructions of this task will be shown to the student when the writer is opened
     * The writing end will limit the time for writing
     */
    public function getWritingTask(): WritingTask
    {
        $repo = $this->di->getTaskRepo();
        $task = $repo->getTaskSettingsById($this->object->getId());

        $writing_end = null;
        if (!empty($task->getWritingEnd())) {
            $writing_end = (new \ilDateTime($task->getWritingEnd(), IL_CAL_DATETIME))->get(IL_CAL_UNIX);
        }

        // todo: get time extension of the user and add it

        return new writingTask($this->object->getTitle(), $task->getInstructions(), $this->user->getFullname(), $writing_end);
    }
}