<?php

namespace ILIAS\Plugin\LongEssayTask;

use Edutiek\LongEssayService\Base\BaseContext;
use Edutiek\LongEssayService\Data\ApiToken;
use Edutiek\LongEssayService\Data\WritingTask;
use Edutiek\LongEssayService\Exceptions\ContextException;
use ilContext;
use ILIAS\Plugin\LongEssayTask\Data\AccessToken;
use \ilObjUser;
use \ilObject;
use \ilObjLongEssayTask;

abstract class ServiceContext implements BaseContext
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
    public function init(string $user_key, string $environment_key): void
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
     */
    public function getSystemName(): string
    {
        global $DIC;
        return (string) $DIC->clientIni()->readVariable('client', 'name');
    }

    /**
     * @inheritDoc
     */
    public function getLanguage(): string
    {
        return $this->user->getLanguage();
    }

    /**
     * @inheritDoc
     */
    public function getTimezone(): string
    {
        return (string) $this->user->getTimeZone();
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
    public function getApiToken(string $purpose): ?ApiToken
    {
        $repo = $this->di->getEssayRepo();
        $token = $repo->getAccessTokenByUserIdAndTaskId($this->user->getId(), $this->object->getId(), $purpose);
        if (isset($token)) {
            try {
                $expires = (new \ilDateTime($token->getValidUntil(), IL_CAL_DATETIME))->get(IL_CAL_UNIX);
            }
            catch (\ilDateTimeException $e) {
                $expires = 0;
            }
            return new ApiToken($token->getToken(), $token->getIp(), (int) $expires);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function setApiToken(ApiToken $api_token, string $purpose)
    {
        // delete an existing token
        $repo = $this->di->getEssayRepo();
        $token = $repo->getAccessTokenByUserIdAndTaskId($this->user->getId(), $this->object->getId(), $purpose);
        if (isset($token)) {
            $repo->deleteAccessToken($token->getId());
        }

        // save the new token
        $token = new AccessToken();
        $token->setUserId($this->user->getId());
        $token->setTaskId($this->object->getId());
        $token->setPurpose($purpose);
        if ($api_token->getExpires()) {
            try {
                $valid = (new \ilDateTime($api_token->getExpires(), IL_CAL_UNIX))->get(IL_CAL_DATETIME);
            }
            catch (\ilDateTimeException $e) {
                $valid = null;
            }
        }
        else {
            $valid = null;
        }
        $token->setToken($api_token->getValue());
        $token->setIp($api_token->getIpAddress());
        $token->setValidUntil($valid);
        $repo->createAccessToken($token);
    }

    /**
     *  @inheritDoc
     */
    public function getWritingTask(): WritingTask
    {
        $repo = $this->di->getTaskRepo();
        $task = $repo->getTaskSettingsById($this->object->getId());

        // todo: get time extension of the user and add it
        return new WritingTask(
            $this->object->getTitle(),
            $task->getInstructions(),
            $this->user->getFullname(),
            $this->plugin->dbTimeToUnix($task->getWritingEnd()));
    }
}