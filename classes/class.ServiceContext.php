<?php

namespace ILIAS\Plugin\LongEssayAssessment;

use Edutiek\LongEssayAssessmentService\Base\BaseContext;
use Edutiek\LongEssayAssessmentService\Data\ApiToken;
use Edutiek\LongEssayAssessmentService\Data\EnvResource;
use Edutiek\LongEssayAssessmentService\Data\WritingTask;
use Edutiek\LongEssayAssessmentService\Exceptions\ContextException;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\AccessToken;
use ILIAS\Plugin\LongEssayAssessment\Data\DataService;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ilContext;
use \ilObjUser;
use \ilObject;
use \ilObjLongEssayAssessment;
use ilSession;
use Edutiek\LongEssayAssessmentService\Data\PageImage;
use Edutiek\LongEssayAssessmentService\Data\WritingSettings;
use Edutiek\LongEssayAssessmentService\Data\PdfSettings;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common\FileHelper;

abstract class ServiceContext implements BaseContext
{
    /**
     * List the availabilities for which resources should be provided in the app
     * @see \ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource
     */
    const RESOURCES_AVAILABILITIES = [
        // override this for writer and corrector context
    ];

    /** @var \ilLanguage */
    protected $lng;

    /** @var \ilLongEssayAssessmentPlugin */
    protected $plugin;

    /** @var LongEssayAssessmentDI */
    protected $localDI;

    /** @var ilObjLongEssayAssessment */
    protected $object;

    /** @var ilObjUser */
    protected $user;

    /** @var TaskSettings */
    protected $task;

    /** @var DataService */
    protected $data;

    protected FileHelper $file_helper;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        global $DIC;

        $this->lng = $DIC->language();
        $this->plugin = \ilLongEssayAssessmentPlugin::getInstance();
        $this->localDI = LongEssayAssessmentDI::getInstance();
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
        if (!ilObject::_exists($ref_id, true, 'xlas')) {
            throw new ContextException('Object does not exist', ContextException::ENVIRONMENT_NOT_VALID);
        }
        if (ilObject::_isInTrash($ref_id)) {
            throw new ContextException('Object is deleted', ContextException::ENVIRONMENT_NOT_VALID);
        }

        $this->user = new ilObjUser($user_id);

        // in REST calls the init() function is called from the long-essay-service
        if (ilContext::getType() == ilContext::CONTEXT_REST) {
            if ($this->plugin->getConfig()->getSimulateOffline()) {
                throw new ContextException('Network Problem Simulation', ContextException::SERVICE_UNAVAILABLE);
            }
            \ilLongEssayAssessmentRestInit::initRestUser($this->user);
        }

        // user must be initiated here
        $this->object = new ilObjLongEssayAssessment($ref_id);

        if (ilContext::getType() == ilContext::CONTEXT_REST &&
            !($this->object->isOnline() || $this->object->canEditOrgaSettings())) {
            throw new ContextException('Object is offline', ContextException::ENVIRONMENT_NOT_VALID);
        }

        $this->task = $this->localDI->getTaskRepo()->getTaskSettingsById($this->object->getId());
        $this->data = $this->localDI->getDataService($this->object->getId());
        $this->file_helper = $this->localDI->services()->common()->fileHelper();
    }

    /**
     * Get the HTTP path to the plugin
     * Helper function for child classes to handle a different ILIAS_HTTP_PATH
     * when being called from ilias.php or from the rest end points
     *
     * @return string
     */
    public function getPluginHttpPath(): string
    {

        $plugin_path = $this->plugin->getPluginPath();
        $pos = strpos(ILIAS_HTTP_PATH, $plugin_path);

        if ($pos !== false) {
            return substr(ILIAS_HTTP_PATH, 0, $pos + strlen($plugin_path));
        }
        else {
            return ILIAS_HTTP_PATH . '/' . $plugin_path;
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
    public function getRelativeTempPath(): string
    {
        $this->createTempWebDir();
        return ILIAS_WEB_DIR. '/' . CLIENT_ID . '/temp';
    }

    /**
     * @inheritDoc
     */
    public function getAbsoluteTempPath(): string
    {
        $this->createTempWebDir();
        return ILIAS_ABSOLUTE_PATH . '/' . ILIAS_WEB_DIR . '/' . CLIENT_ID . '/temp';
    }

    /**
     * Create the directory 'temp' in the web data directory
     */
    protected function createTempWebDir()
    {
        global $DIC;
        $fs = $DIC->filesystem()->web();
        if (!$fs->hasDir('temp')) {
            $fs->createDir('temp');
        }
    }

    /**
     * @inheritDoc
     */
    public function getLanguage(): string
    {
        return $this->user->getLanguage() ?? $this->lng->getDefaultLanguage();
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
        $repo = $this->localDI->getEssayRepo();
        $token = $repo->getAccessTokenByUserIdAndTaskId($this->user->getId(), $this->object->getId(), $purpose);
        if (isset($token)) {
            try {
                $expires = (new \ilDateTime($token->getValidUntil(), IL_CAL_DATETIME))->get(IL_CAL_UNIX);
            } catch (\ilDateTimeException $e) {
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
        $repo = $this->localDI->getEssayRepo();
        $repo->deleteAccessTokenByUserIdAndTaskId($this->user->getId(), $this->task->getTaskId(), $purpose);

        // save the new token
        $token = new AccessToken();
        $token->setUserId($this->user->getId());
        $token->setTaskId($this->object->getId());
        $token->setPurpose($purpose);
        if ($api_token->getExpires()) {
            try {
                $valid = (new \ilDateTime($api_token->getExpires(), IL_CAL_UNIX))->get(IL_CAL_DATETIME);
            } catch (\ilDateTimeException $e) {
                $valid = null;
            }
        } else {
            $valid = null;
        }
        $token->setToken($api_token->getValue());
        $token->setIp($api_token->getIpAddress());
        $token->setValidUntil($valid);
        $repo->save($token);
    }


    /**
     * Get the resources that should be available in the app
     * @return EnvResource[]
     */
    public function getResources(): array
    {
        global $DIC;


        $repo = $this->localDI->getTaskRepo();
        $env_resources = [];
        $resources = $repo->getResourceByTaskId($this->object->getId());

        /** @var \ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource $resource */
        foreach ($resources as $resource) {

            // late static binding - use constant definition in the extended class
            if (in_array($resource->getAvailability(), static::RESOURCES_AVAILABILITIES)) {

                if ($resource->getType() == Resource::RESOURCE_TYPE_URL) {
                    $mimetype = null;
                    $size = null;
                    $source = $resource->getUrl();
                } else {
                    $resource_file = $DIC->resourceStorage()->manage()->find($resource->getFileId());
                    if($resource_file === null) {
                        continue;
                    }

                    $revision = $DIC->resourceStorage()->manage()->getCurrentRevision($resource_file);
                    if($revision === null) {
                        continue;
                    }

                    $source = $revision->getInformation()->getTitle();
                    $mimetype = $revision->getInformation()->getMimeType();
                    $size = $revision->getInformation()->getSize();
                }
                
                $title = $resource->getTitle();
                if ($resource->getType() == Resource::RESOURCE_TYPE_INSTRUCTION) {
                    $title = $this->plugin->txt('task_instructions');
                }
                if ($resource->getType() == Resource::RESOURCE_TYPE_SOLUTION) {
                    $title = $this->plugin->txt('task_solution');
                }

                $env_resources[] = new EnvResource(
                    (string) $resource->getId(),
                    $title,
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
        $repo = $this->localDI->getTaskRepo();
        $resources = $repo->getResourceByTaskId($this->object->getId(), [Resource::RESOURCE_TYPE_FILE, Resource::RESOURCE_TYPE_INSTRUCTION, Resource::RESOURCE_TYPE_SOLUTION]);
        foreach ($resources as $resource) {
            if ($resource->getId() == (int) $key) {
                $this->file_helper->deliverResource($resource->getFileId());
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function sendPageThumb(string $key): void
    {
        $repo = $this->localDI->getEssayRepo();
        $image = $repo->getEssayImageByID((int) $key);
        if (!empty($image)) {
            $this->file_helper->deliverResource($image->getThumbId());
        }
    }

    /**
     * @inheritDoc
     */
    public function sendPageImage(string $key): void
    {
        $repo = $this->localDI->getEssayRepo();
        $image = $repo->getEssayImageByID((int) $key);
        if (!empty($image)) {
            $this->file_helper->deliverResource($image->getFileId());
        }
    }

    /**
     * Get the page image with loaded resources by its key
     * @param string $key
     * @return PageImage|null
     */
    public function getPageImage(string $key): ?PageImage
    {
        global $DIC;

        $repo = $this->localDI->getEssayRepo();
        $repoImage = $repo->getEssayImageByID((int) $key);

        if (!empty($repoImage) && !empty($repoImage->getFileId())) {
            $identification = $DIC->resourceStorage()->manage()->find($repoImage->getFileId());
            if (!empty($identification)) {
                $stream = $DIC->resourceStorage()->consume()->stream($identification)->getStream()->detach();
                $thumb_stream = null;
                if (!empty($repoImage->getThumbId())) {
                    $thumb_identification = $DIC->resourceStorage()->manage()->find($repoImage->getThumbId());
                    $thumb_stream = $DIC->resourceStorage()->consume()->stream($thumb_identification)->getStream()->detach();
                }
                return new PageImage(
                    $stream,
                    $repoImage->getMime(),
                    $repoImage->getWidth(),
                    $repoImage->getHeight(),
                    $thumb_stream,
                    $repoImage->getThumbMime(),
                    $repoImage->getThumbWidth(),
                    $repoImage->getThumbHeight()
                );
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getWritingSettings(): WritingSettings
    {
        $repoSettings = $this->localDI->getTaskRepo()->getEditorSettingsById($this->task->getTaskId());

        return new WritingSettings(
            $repoSettings->getHeadlineScheme(),
            $repoSettings->getFormattingOptions(),
            $repoSettings->getNoticeBoards(),
            $repoSettings->isCopyAllowed(),
            $this->plugin->getConfig()->getPrimaryColor(),
            $this->plugin->getConfig()->getPrimaryTextColor(),
            $repoSettings->getAddParagraphNumbers(),
            $repoSettings->getAddCorrectionMargin(),
            $repoSettings->getLeftCorrectionMargin(),
            $repoSettings->getRightCorrectionMargin(),
            $repoSettings->getAllowSpellcheck()
        );
    }

    /**
     * @inheritDoc
     */
    public function getPdfSettings(): PdfSettings
    {
        $repoSettings = $this->localDI->getTaskRepo()->getPdfSettingsById($this->task->getTaskId());

        return new PdfSettings(
            $repoSettings->getAddHeader(),
            $repoSettings->getAddFooter(),
            $repoSettings->getTopMargin(),
            $repoSettings->getBottomMargin(),
            $repoSettings->getLeftMargin(),
            $repoSettings->getRightMargin()
        );
    }


    /**
     * Get the writing task of a certain writer
     * This is specific for a writer because of his/her writing end and writing exclusion
     * (not needed by interface, but public because needed by WriterAdminService and CorrectorAdminService)
     */
    public function getWritingTaskByWriterId(int $writer_id) : WritingTask
    {
        $repoWriter = $this->localDI->getWriterRepo()->getWriterById($writer_id);
        $repoEssay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($writer_id, $this->task->getTaskId());
        $userDataHelper = $this->localDI->services()->common()->userDataHelper();

        $writing_end = $this->data->dbTimeToUnix($this->task->getWritingEnd());
        if (!empty($writing_end)
            && !empty($timeExtension = $this->localDI->getWriterRepo()->getTimeExtensionByWriterId($writer_id, $this->task->getTaskId()))
        ) {
            $writing_end += $timeExtension->getMinutes() * 60;
        }

        return new WritingTask(
            (string) $this->object->getTitle(),
            (string) $this->task->getInstructions(),
            isset($repoWriter) ? $userDataHelper->getFullname($repoWriter->getUserId()) : '',
            $writing_end,
            $this->data->dbTimeToUnix($repoEssay->getWritingExcluded())
        );
    }

    /**
     * Extend the session of an authenticated user
     *
     * Here:
     * A user may have parallel active sessions
     * We cannot determine the session because the service does not provide its session_id
     * So continue all active sessions, and try to filter them by i if the ip is stored in ilias
     */
    public function setAlive() : void
    {
        global $DIC;

        $client_ini = $DIC->clientIni();
        $system_repo = $this->localDI->getSystemRepo();

        if ($client_ini->readVariable("session", "save_ip")) {
            $session_ids = $system_repo->getActiveSessionIds((int) $this->user->getId(), (string) $_SERVER["REMOTE_ADDR"]);
        } else {
            $session_ids = $system_repo->getActiveSessionIds($this->user->getId());
        }

        foreach ($session_ids as $id) {
            $system_repo->setSessionExpires($id, ilSession::getExpireValue());
        }
    }
}
