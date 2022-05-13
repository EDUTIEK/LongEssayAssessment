<?php

namespace ILIAS\Plugin\LongEssayTask\Writer;

use Edutiek\LongEssayService\Data\ApiToken;
use Edutiek\LongEssayService\Data\WritingResource;
use Edutiek\LongEssayService\Data\WritingSettings;
use Edutiek\LongEssayService\Data\WritingStep;
use Edutiek\LongEssayService\Data\WritingTask;
use Edutiek\LongEssayService\Writer\Context;
use Edutiek\LongEssayService\Writer\Service;
use Edutiek\LongEssayService\Data\WrittenEssay;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\Resource;
use ILIAS\Plugin\LongEssayTask\Data\WriterHistory;
use ILIAS\Plugin\LongEssayTask\ServiceContext;
use ILIAS\Plugin\LongEssayTask\Task\ResourceAdmin;

class WriterContext extends ServiceContext implements Context
{
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
     */
    public function getWritingSettings(): WritingSettings
    {
        $repo = $this->di->getTaskRepo();
        $settings = $repo->getEditorSettingsById($this->object->getId());

        return new WritingSettings(
          $settings->getHeadlineScheme(),
          $settings->getFormattingOptions(),
          $settings->getNoticeBoards(),
          $settings->isCopyAllowed()
        );
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


    /**
     * @inheritDoc
     */
    public function getWrittenEssay(): WrittenEssay
    {
        $repo = $this->di->getEssayRepo();
        $essay = $repo->getEssayByWriterIdAndTaskId($this->user->getId(), $this->object->getId());

        if (isset($essay)) {
            return new WrittenEssay(
                $essay->getWrittenText(),
                $essay->getRawTextHash(),
                $essay->getProcessedText(),
                $this->plugin->dbTimeToUnix($essay->getEditStarted()),
                $this->plugin->dbTimeToUnix($essay->getEditEnded()),
                (bool) $essay->isIsAuthorized()
            );
        }
        else {
            return new WrittenEssay(
                null,
                null,
                null,
                null,
                null,
                false
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function setWrittenEssay(WrittenEssay $written_essay): void
    {
        $repo = $this->di->getEssayRepo();
        $essay = $repo->getEssayByWriterIdAndTaskId($this->user->getId(), $this->object->getId());

        if (!isset($essay)) {
            $essay = new Essay();
            $essay->setWriterId((string) $this->user->getId());
            $essay->setTaskId((string) $this->object->getId());
            $essay->setUuid($essay->generateUUID4());
            $essay->setRawTextHash('');
            $repo->createEssay($essay);
        }

        $repo->updateEssay($essay
            ->setWrittenText($written_essay->getWrittenText())
            ->setRawTextHash($written_essay->getWrittenHash())
            ->setProcessedText($written_essay->getProcessedText())
            ->setEditStarted($this->plugin->unixTimeToDb($written_essay->getEditStarted()))
            ->setEditEnded($this->plugin->unixTimeToDb($written_essay->getEditEnded()))
            ->setIsAuthorized($written_essay->isAuthorized())
        );
    }

    /**
     * @inheritDoc
     */
    public function getWritingSteps(?int $maximum): array
    {
        $repo = $this->di->getEssayRepo();
        $essay = $repo->getEssayByWriterIdAndTaskId($this->user->getId(), $this->object->getId());
        $entries = $repo->getWriterHistoryStepsByEssayId($essay->getId(), $maximum);

        $steps = [];
        foreach ($entries as $entry) {
            $steps[] = new WritingStep(
                (int) ($this->plugin->dbTimeToUnix($entry->getTimestamp())),
                (string) $entry->getContent(),
                (bool) $entry->isIsDelta(),
                (string) $entry->getHashBefore(),
                (string) $entry->getHashAfter()
            );
        }
        return $steps;
    }

    /**
     * @inheritDoc
     */
    public function addWritingSteps(array $steps)
    {
        $repo = $this->di->getEssayRepo();
        $essay = $repo->getEssayByWriterIdAndTaskId($this->user->getId(), $this->object->getId());

        foreach ($steps as $step) {
            $entry = new WriterHistory();
            $entry->setEssayId($essay->getId());
            $entry->setContent($step->getContent());
            $entry->setIsDelta($step->isDelta());
            $entry->setTimestamp($this->plugin->unixTimeToDb($step->getTimestamp()));
            $entry->setHashBefore($step->getHashBefore());
            $entry->setHashAfter($step->getHashAfter());
            $repo->createWriterHistory($entry);
        }
    }

    /**
     * @inheritDoc
     */
    public function hasWritingStepByHashAfter(string $hash_after): bool
    {
        $repo = $this->di->getEssayRepo();
        $essay = $repo->getEssayByWriterIdAndTaskId($this->user->getId(), $this->object->getId());
        return $repo->ifWriterHistoryExistByEssayIdAndHashAfter($essay->getId(), $hash_after);
    }

    /**
     * @inheritDoc
     */
    public function getWritingResources(): array
    {
		global $DIC;

        $repo = $this->di->getTaskRepo();
        $writing_resources = [];

        /** @var Resource $resource */
        foreach ($repo->getResourceByTaskId($this->object->getId()) as $resource) {
            if ($resource->getAvailability() == Resource::RESOURCE_AVAILABILITY_BEFORE ||
                $resource->getAvailability() == Resource::RESOURCE_AVAILABILITY_DURING) {

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

				$writing_resources[] = new WritingResource(
                    (string) $resource->getId(),
                    $resource->getTitle(),
                    $resource->getType(),
                    $source,
                    $mimetype,
                    $size
                );
            }
        }

        return $writing_resources;
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

				if ($resource->getType() == Resource::RESOURCE_TYPE_FILE && is_string($resource->getFileId())) {
					$identifier = $resource->getFileId();
				}else {
					throw new Exception("No FIle!");
				}

				$resource_file = $DIC->resourceStorage()->manage()->find($identifier);
				if ($resource_file !== null) {
					$DIC->resourceStorage()->consume()->inline($resource_file)->run();
				}else{
					// TODO: Error resource not in Storage
					throw new Exception("Panik!");
				}
            }
        }
    }
}