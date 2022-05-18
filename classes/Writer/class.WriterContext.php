<?php

namespace ILIAS\Plugin\LongEssayTask\Writer;

use Edutiek\LongEssayService\Data\WritingSettings;
use Edutiek\LongEssayService\Data\WritingStep;
use Edutiek\LongEssayService\Data\WritingTask;
use Edutiek\LongEssayService\Writer\Context;
use Edutiek\LongEssayService\Writer\Service;
use Edutiek\LongEssayService\Data\WrittenEssay;
use ILIAS\Plugin\LongEssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayTask\Data\Resource;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\Data\WriterHistory;
use ILIAS\Plugin\LongEssayTask\ServiceContext;

class WriterContext extends ServiceContext implements Context
{
    /**
     * List the availabilities for which resources should be provided in the app
     * @see Resource
     */
    const RESOURCES_AVAILABILITIES = [
        Resource::RESOURCE_AVAILABILITY_BEFORE,
        Resource::RESOURCE_AVAILABILITY_DURING
    ];


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
        $repoSettings = $this->di->getTaskRepo()->getEditorSettingsById($this->task->getTaskId());
        return new WritingSettings(
            $repoSettings->getHeadlineScheme(),
            $repoSettings->getFormattingOptions(),
            $repoSettings->getNoticeBoards(),
            $repoSettings->isCopyAllowed()
        );
    }


    /**
     *  @inheritDoc
     */
    public function getWritingTask(): WritingTask
    {
        // todo: get time extension of the user and add it
        return new WritingTask(
            $this->object->getTitle(),
            $this->task->getInstructions(),
            $this->user->getFullname(),
            $this->plugin->dbTimeToUnix($this->task->getWritingEnd()));
    }


    /**
     * @inheritDoc
     */
    public function getWrittenEssay(): WrittenEssay
    {
        $repoEssay = $this->getRepoEssay();
        return new WrittenEssay(
            $repoEssay->getWrittenText(),
            $repoEssay->getRawTextHash(),
            $repoEssay->getProcessedText(),
            $this->plugin->dbTimeToUnix($repoEssay->getEditStarted()),
            $this->plugin->dbTimeToUnix($repoEssay->getEditEnded()),
            (bool) $repoEssay->isIsAuthorized()
        );
    }

    /**
     * @inheritDoc
     */
    public function setWrittenEssay(WrittenEssay $writtenEssay): void
    {
        $this->di->getEssayRepo()->updateEssay($this->getRepoEssay()
            ->setWrittenText($writtenEssay->getWrittenText())
            ->setRawTextHash($writtenEssay->getWrittenHash())
            ->setProcessedText($writtenEssay->getProcessedText())
            ->setEditStarted($this->plugin->unixTimeToDb($writtenEssay->getEditStarted()))
            ->setEditEnded($this->plugin->unixTimeToDb($writtenEssay->getEditEnded()))
            ->setIsAuthorized($writtenEssay->isAuthorized())
        );
    }

    /**
     * @inheritDoc
     */
    public function getWritingSteps(?int $maximum): array
    {
        $entries = $this->di->getEssayRepo()->getWriterHistoryStepsByEssayId(
            $this->getRepoEssay()->getId(),
            $maximum);

        $steps = [];
        foreach ($entries as $entry) {
            $steps[] = new WritingStep(
                (int) ($this->plugin->dbTimeToUnix($entry->getTimestamp())),
                (string) $entry->getContent(),
                $entry->isIsDelta(),
                $entry->getHashBefore(),
                $entry->getHashAfter()
            );
        }
        return $steps;
    }

    /**
     * @inheritDoc
     */
    public function addWritingSteps(array $steps)
    {
        foreach ($steps as $step) {
            $entry = new WriterHistory();
            $entry->setEssayId($this->getRepoEssay()->getId())
                ->setContent($step->getContent())
                ->setIsDelta($step->isDelta())
                ->setTimestamp($this->plugin->unixTimeToDb($step->getTimestamp()))
                ->setHashBefore($step->getHashBefore())
                ->setHashAfter($step->getHashAfter());
            $this->di->getEssayRepo()->createWriterHistory($entry);
        }
    }

    /**
     * @inheritDoc
     */
    public function hasWritingStepByHashAfter(string $hash_after): bool
    {
        return $this->di->getEssayRepo()->ifWriterHistoryExistByEssayIdAndHashAfter(
            $this->getRepoEssay()->getId(),
            $hash_after);
    }

    /**
     * Get or create the essay object from the repository
     * @return Essay
     */
    protected function getRepoEssay() : Essay
    {
        $repo = $this->di->getEssayRepo();
        $writer = $this->getRepoWriter();

        $essay = $repo->getEssayByWriterIdAndTaskId($writer->getId(), $writer->getTaskId());
        if (!isset($essay)) {
            $essay = new Essay();
            $essay->setWriterId($writer->getId())
                ->setTaskId($writer->getTaskId())
                ->setUuid($essay->generateUUID4())
                ->setRawTextHash('');
            $repo->createEssay($essay);
        }
        return $essay;
    }

    /**
     * Get or create the writer object from the repository
     * @return Writer
     */
    protected function getRepoWriter() : Writer
    {
        $repo = $this->di->getWriterRepo();
        $writer = $repo->getWriterByUserId($this->user->getId(), $this->task->getTaskId());
        if (!isset($writer)) {
            $writer = new Writer();
            $writer->setUserId($this->user->getId())
                ->setTaskId($this->task->getTaskId())
                ->setPseudonym($this->plugin->txt('participant') . ' ' . $this->user->getId());
            $repo->createWriter($writer);
        }
        return $writer;
    }
}