<?php

namespace ILIAS\Plugin\LongEssayTask;

use Edutiek\LongEssayService\Base\BaseContext;
use Edutiek\LongEssayService\Data\ApiToken;
use Edutiek\LongEssayService\Data\EnvResource;
use Edutiek\LongEssayService\Exceptions\ContextException;
use ILIAS\Plugin\LongEssayTask\Data\AccessToken;
use ILIAS\Plugin\LongEssayTask\Data\DataService;
use ILIAS\Plugin\LongEssayTask\Data\Resource;
use ILIAS\Plugin\LongEssayTask\Data\TaskSettings;
use ilContext;
use \ilObjUser;
use \ilObject;
use \ilObjLongEssayTask;

abstract class ServiceContext implements BaseContext
{
    /**
     * List the availabilities for which resources should be provided in the app
     * @see Resource
     */
    const RESOURCES_AVAILABILITIES = [
        // override this for writer and corrector context
    ];


    /** @var \ilLongEssayTaskPlugin */
    protected $plugin;

    /** @var LongEssayTaskDI */
    protected $di;

    /** @var ilObjLongEssayTask */
    protected $object;

    /** @var ilObjUser */
    protected $user;

    /** @var TaskSettings */
    protected $task;

    /** @var DataService */
    protected $data;

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

       $this->task = $this->di->getTaskRepo()->getTaskSettingsById($this->object->getId());
       $this->data = $this->object->getDataService();
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
        $repo->deleteAccessTokenByUserIdAndTaskId($this->user->getId(), $this->task->getTaskId(), $purpose);

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
     * Get the resources that should be available in the app
     * @return EnvResource[]
     */
    public function getResources(): array {
        global $DIC;


        $repo = $this->di->getTaskRepo();
        $env_resources = [];

        /** @var Resource $resource */
        foreach ($repo->getResourceByTaskId($this->object->getId()) as $resource) {

            // late static binding - use constant definition in the extended class
            if (in_array($resource->getAvailability(), static::RESOURCES_AVAILABILITIES)) {

                if ($resource->getType() == Resource::RESOURCE_TYPE_FILE) {
                    $resource_file = $DIC->resourceStorage()->manage()->find($resource->getFileId());
                    $revision = $DIC->resourceStorage()->manage()->getCurrentRevision($resource_file);

                    if($revision === null){
                        continue;
                    }

                    $source = $revision->getInformation()->getTitle();
                    $mimetype = $revision->getInformation()->getMimeType();
                    $size = $revision->getInformation()->getSize();
                }
                else {
                    $mimetype = null;
                    $size = null;
                    $source = $resource->getUrl();
                }

                $env_resources[] = new EnvResource(
                    (string) $resource->getId(),
                    $resource->getTitle(),
                    $resource->getType(),
                    $source,
                    $mimetype,
                    $size
                );
            }
        }

        return $env_resources;
    }


    /**
     * @inheritDoc
     */
    public function sendFileResource(string $key): void
    {
        global $DIC;

        $repo = $this->di->getTaskRepo();

        /** @var Resource $resource */
        foreach ($repo->getResourceByTaskId($this->object->getId()) as $resource) {
            if ($resource->getId() == (int) $key && $resource->getType() == Resource::RESOURCE_TYPE_FILE) {
                $identifier = "";

                if (is_string($resource->getFileId())) {
                    $identifier = $resource->getFileId();
                }else {
                    // TODO: ERROR Broken Resource
                }

                $resource_file = $DIC->resourceStorage()->manage()->find($identifier);
                if ($resource_file !== null) {
                    $DIC->resourceStorage()->consume()->inline($resource_file)->run();
                }else{
                    // TODO: Error resource not in Storage
                }
            }
        }
    }
}