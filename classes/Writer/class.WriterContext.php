<?php

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use Edutiek\LongEssayAssessmentService\Data\Alert;
use Edutiek\LongEssayAssessmentService\Data\WritingSettings;
use Edutiek\LongEssayAssessmentService\Data\WritingStep;
use Edutiek\LongEssayAssessmentService\Data\WritingTask;
use Edutiek\LongEssayAssessmentService\Writer\Context;
use Edutiek\LongEssayAssessmentService\Writer\Service;
use Edutiek\LongEssayAssessmentService\Data\WrittenEssay;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\WriterHistory;
use ILIAS\Plugin\LongEssayAssessment\ServiceContext;
use Edutiek\LongEssayAssessmentService\Data\WrittenNote;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\WriterNotice;
use Edutiek\LongEssayAssessmentService\Data\WritingPreferences;

class WriterContext extends ServiceContext implements Context
{
    /**
     * List the availabilities for which resources should be provided in the app
     * @see \ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource
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
                . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment"
                . "/vendor/edutiek/long-essay-assessment-service"
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
            . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment/writer_service.php"
            . "?client_id=" . CLIENT_ID;
    }

    /**
     * @inheritDoc
     * here: just get the link to the repo object, the tab will be shown depending on the user permissions
     * The ILIAS session still has to exist, otherwise the user has to log in again
     */
    public function getReturnUrl(): string
    {
        return \ilLink::_getStaticLink($this->object->getRefId(), 'xlas', true, '_writer');
    }
    


    /**
     *  @inheritDoc
     */
    public function getWritingTask(): WritingTask
    {
        return $this->getWritingTaskByWriterId($this->getRepoWriter()->getId());
    }


    /**
     * @inheritDoc
     */
    public function getAlerts(): array
    {
        $alerts = [];
        foreach ($this->localDI->getTaskRepo()->getAlertsByTaskId($this->task->getTaskId()) as $repoAlert) {
            if (empty($repoAlert->getWriterId()) || $repoAlert->getWriterId() == $this->getRepoWriter()->getId()) {
                if (empty($repoAlert->getShownFrom()) || $this->data->dbTimeToUnix($repoAlert->getShownFrom()) < time()) {
                    $alerts[] = New Alert(
                        (string) $repoAlert->getId(),
                        $repoAlert->getMessage(),
                        $this->data->dbTimeToUnix($repoAlert->getShownFrom())
                    );
                }
            }
        }
        return $alerts;
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
            $repoEssay->getServiceVersion(),
            $this->data->dbTimeToUnix($repoEssay->getEditStarted()),
            $this->data->dbTimeToUnix($repoEssay->getEditEnded()),
            !empty($repoEssay->getWritingAuthorized()),
            $this->data->dbTimeToUnix($repoEssay->getWritingAuthorized()),
            !empty($repoEssay->getWritingAuthorized()) ? \ilObjUser::_lookupFullname($repoEssay->getWritingAuthorizedBy()) : null
        );
    }

    /**
     * @inheritDoc
     */
    public function setWrittenEssay(WrittenEssay $writtenEssay): void
    {
        $essay = $this->getRepoEssay()
            ->setWrittenText($writtenEssay->getWrittenText())
            ->setRawTextHash($writtenEssay->getWrittenHash())
            ->setServiceVersion($writtenEssay->getServiceVersion())
            ->setEditStarted($this->data->unixTimeToDb($writtenEssay->getEditStarted()))
            ->setEditEnded($this->data->unixTimeToDb($writtenEssay->getEditEnded()));

        if ($writtenEssay->isAuthorized()) {
                if (empty($essay->getWritingAuthorized())) {
                    $essay->setWritingAuthorized($this->data->unixTimeToDb(time()));
                }
                if (empty($essay->getWritingAuthorizedBy())) {
                    $essay->setWritingAuthorizedBy($this->user->getId());
                }
        }
        else {
            $essay->setWritingAuthorized(null);
            $essay->setWritingAuthorizedBy(null);
        }

        $this->localDI->getEssayRepo()->save($essay);
    }


    /**
     * @inheritDoc
     */
    public function getWrittenNotes(): array
    {
        $notes = [];
        $repoEssay = $this->getRepoEssay();
        foreach ($this->localDI->getEssayRepo()->getWriterNoticesByEssayID($repoEssay->getId()) as $repoNote) {
            $notes[] = new WrittenNote(
                $repoNote->getNoteNo(), 
                $repoNote->getNoteText(),
                $this->data->dbTimeToUnix($repoNote->getLastChange())
            );
        }
        return $notes;
    }

    /**
     * @inheritDoc
     */
    public function setWrittenNote(WrittenNote $note): void
    {
        $repoEssay = $this->getRepoEssay();
        $repoNote = $this->localDI->getEssayRepo()->getWriterNoticeByEssayAndNo($repoEssay->getId(), $note->getNoteNo());
        if (!isset($repoNote)) {
            $repoNote = (new WriterNotice())
                ->setEssayId($repoEssay->getId())
                ->setNoteNo($note->getNoteNo());
        }
        $repoNote->setNoteText($note->getNoteText());
        $repoNote->setLastChange($note->getLastChange() 
            ? $this->data->unixTimeToDb($note->getLastChange())
            : $this->data->unixTimeToDb(time()));
        
        $this->localDI->getEssayRepo()->save($repoNote);
    }


    /**
     * @inheritDoc
     */
    public function deleteWrittenNotes(): void
    {
        $repoEssay = $this->getRepoEssay();
        $this->localDI->getEssayRepo()->deleteWriterNoticesByEssayID($repoEssay->getId());
    }


    /**
     * @inheritDoc
     */
    public function getWritingPreferences(): WritingPreferences
    {
        $repoPreferences = $this->localDI->getWriterRepo()->getWriterPreferences($this->getRepoWriter()->getId());
        return new WritingPreferences(
            $repoPreferences->getInstructionsZoom(),
            $repoPreferences->getEditorZoom()
        );
    }

    /**
     * @inheritDoc
     */
    public function setWritingPreferences(WritingPreferences $preferences): void
    {
        $repoPreferences = $this->localDI->getWriterRepo()->getWriterPreferences($this->getRepoWriter()->getId());
        $repoPreferences->setInstructionsZoom($preferences->getInstructionsZoom());
        $repoPreferences->setEditorZoom($preferences->getEditorZoom());
        $this->localDI->getWriterRepo()->save($repoPreferences);
    }


    /**
     * @inheritDoc
     */
    public function getWritingSteps(?int $maximum): array
    {
        $entries = $this->localDI->getEssayRepo()->getWriterHistoryStepsByEssayId(
            $this->getRepoEssay()->getId(),
            $maximum);

        $steps = [];
        foreach ($entries as $entry) {
            $steps[] = new WritingStep(
                (int) ($this->data->dbTimeToUnix($entry->getTimestamp())),
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
                ->setTimestamp($this->data->unixTimeToDb($step->getTimestamp()))
                ->setHashBefore($step->getHashBefore())
                ->setHashAfter($step->getHashAfter());
            $this->localDI->getEssayRepo()->save($entry);
        }
    }

    /**
     * @inheritDoc
     */
    public function hasWritingStepByHashAfter(string $hash_after): bool
    {
        return $this->localDI->getEssayRepo()->ifWriterHistoryExistByEssayIdAndHashAfter(
            $this->getRepoEssay()->getId(),
            $hash_after);
    }

    /**
     * Get or create the essay object from the repository
     * @return \ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay
     */
    protected function getRepoEssay() : Essay
    {
        $service = $this->localDI->getWriterAdminService($this->task->getTaskId());
        $writer = $this->getRepoWriter();
        return $service->getOrCreateEssayForWriter($writer);
    }

    /**
     * Get or create the writer object from the repository
     * @return \ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer
     */
    protected function getRepoWriter() : Writer
    {
        return $this->localDI->getWriterAdminService($this->task->getTaskId())
            ->getOrCreateWriterFromUserId($this->user->getId());
    }
}
