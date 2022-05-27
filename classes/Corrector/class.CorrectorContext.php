<?php

namespace ILIAS\Plugin\LongEssayTask\Corrector;

use Edutiek\LongEssayService\Corrector\Context;
use Edutiek\LongEssayService\Corrector\Service;
use Edutiek\LongEssayService\Data\CorrectionGradeLevel;
use Edutiek\LongEssayService\Data\CorrectionItem;
use Edutiek\LongEssayService\Data\CorrectionSettings;
use Edutiek\LongEssayService\Data\CorrectionSummary;
use Edutiek\LongEssayService\Data\CorrectionTask;
use Edutiek\LongEssayService\Data\Corrector;
use Edutiek\LongEssayService\Data\WrittenEssay;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorSummary;
use ILIAS\Plugin\LongEssayTask\Data\Resource;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\ServiceContext;

class CorrectorContext extends ServiceContext implements Context
{
    /**
     * List the availabilities for which resources should be provided in the app
     * @see Resource
     */
    const RESOURCES_AVAILABILITIES = [
        Resource::RESOURCE_AVAILABILITY_BEFORE,
        Resource::RESOURCE_AVAILABILITY_DURING,
        Resource::RESOURCE_AVAILABILITY_AFTER
    ];

    /**
     * id of the selected writer
     * @var ?int
     */
    protected $selected_writer_id;

    /**
     * Select id of the writer for being corrected
     * The corrector app will load the correction item of this writer
     * @see Writer::getId()
     */
    public function selectWriterId(int $id) : void {
        $this->selected_writer_id = $id;
    }

    /**
     * @inheritDoc
     * here: support a separate url from the plugin config (for development purposes)
     */
    public function getFrontendUrl(): string
    {
        $config = $this->plugin->getConfig();

        if (!empty($config->getCorrectorUrl())) {
            return $config->getCorrectorUrl();
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
     * here: URL of the corrector_service script
     */
    public function getBackendUrl(): string
    {
        return  ILIAS_HTTP_PATH
            . "/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayTask/corrector_service.php"
            . "?client_id=" . CLIENT_ID;
    }

    /**
     * @inheritDoc
     * here: get the link to the repo object
     * The ILIAS session still has to exist, otherwise the user has to log in again
     */
    public function getReturnUrl(): string
    {
        return \ilLink::_getStaticLink($this->object->getRefId(), 'xlet', true, '_corrector');
    }

    /**
     * @inheritDoc
     */
    public function getCorrectionTask(): CorrectionTask
    {
        return new CorrectionTask(
            $this->object->getTitle(),
            $this->task->getInstructions(),
            $this->data->dbTimeToUnix($this->task->getCorrectionEnd()));
    }

    /**
     * @inheritDoc
     */
    public function getCorrectionSettings(): CorrectionSettings
    {
        if (!empty($repoSettings = $this->di->getTaskRepo()->getCorrectionSettingsById($this->task->getTaskId()))) {
            return new CorrectionSettings(
                (bool) $repoSettings->getMutualVisibility(),
                (bool) $repoSettings->getMultiColorHighlight(),
                (int) $repoSettings->getMaxPoints()
            );
        }
        return new CorrectionSettings(false, false, 0);
    }

    /**
     * @inheritDoc
     */
    public function getGradeLevels(): array
    {
        $objectRepo = $this->di->getObjectRepo();

        $levels = [];
        foreach ($objectRepo->getGradeLevelsByObjectId($this->object->getId()) as $repoLevel) {
            $levels[] = new CorrectionGradeLevel(
                (string) $repoLevel->getId(),
                $repoLevel->getGrade(),
                $repoLevel->getMinPoints()
            );
        }
        return $levels;
    }

    /**
     * @inheritDoc
     * here:    the item keys are strings of the writer ids
     *          the item titles are the pseudonymous writer names
     */
    public function getCorrectionItems(): array
    {
        $correctorRepo = $this->di->getCorrectorRepo();
        $writerRepo = $this->di->getWriterRepo();

        $items = [];
        if (!empty($repoCorrector = $correctorRepo->getCorrectorByUserId($this->user->getId(), $this->task->getTaskId()))) {
            foreach ($correctorRepo->getAssignmentsByCorrectorId($repoCorrector->getId()) as $repoAssignment) {
                if (!empty($repoWriter = $writerRepo->getWriterById($repoAssignment->getWriterId()))) {
                    $items[] = new CorrectionItem(
                        (string) $repoWriter->getId(),
                        $repoWriter->getPseudonym()
                    );
                }
            }
        }
        return $items;
    }

    /**
     * @inheritDoc
     * here:    the corrector key is a string of the corrector id
     */
    public function getCurrentCorrector(): ?Corrector
    {
        $correctorRepo = $this->di->getCorrectorRepo();
        if (!empty($repoCorrector = $correctorRepo->getCorrectorByUserId($this->user->getId(), $this->task->getTaskId()))) {
            return new Corrector(
                (string) $repoCorrector->getId(),
                $this->user->getFullname()
            );
        }
        return null;
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the item title is the pseudonymous writer name
     */
    public function getCurrentItem(): ?CorrectionItem
    {
        $correctorRepo = $this->di->getCorrectorRepo();
        $writerRepo = $this->di->getWriterRepo();

        if (isset($this->selected_writer_id)) {
            if (!empty($repoCorrector = $correctorRepo->getCorrectorByUserId($this->user->getId(), $this->task->getTaskId())) &&
                !empty($repoWriter = $writerRepo->getWriterById((int) $this->selected_writer_id)))
            {
                foreach ($correctorRepo->getAssignmentsByWriterId((int) $this->selected_writer_id) as $assignment) {
                    if ($assignment->getCorrectorId() == $repoCorrector->getId()) {
                        return new CorrectionItem(
                            (string) $repoWriter->getId(),
                            $repoWriter->getPseudonym()
                        );
                    }
                }
            }
        }
        return null;
     }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     */
    public function getEssayOfItem(string $item_key): ?WrittenEssay
    {
        if (!empty($repoEssay = $this->di->getEssayRepo()->getEssayByWriterIdAndTaskId(
                (int) $item_key, $this->task->getTaskId()))) {
            return new WrittenEssay(
                $repoEssay->getWrittenText(),
                $repoEssay->getRawTextHash(),
                $repoEssay->getProcessedText(),
                $this->data->dbTimeToUnix($repoEssay->getEditStarted()),
                $this->data->dbTimeToUnix($repoEssay->getEditEnded()),
                !empty($repoEssay->getWritingAuthorized())
            );
        }
        return null;
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector keys are strings of the corrector ids
     *          the corrector titles are the usernames of the correctors
     */
    public function getCorrectorsOfItem(string $item_key): array
    {
       $correctorRepo = $this->di->getCorrectorRepo();
       $correctors = [];
       foreach ($correctorRepo->getAssignmentsByWriterId((int) $item_key) as $assignment) {
            if (!empty($repoCorrector = $correctorRepo->getCorrectorById($assignment->getCorrectorId()))) {
                $correctors[] = new Corrector(
                    (string) $repoCorrector->getId(),
                    \ilObjUser::_lookupFullname($repoCorrector->getUserId())
                );
            }
        }
        return $correctors;
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector key is a string of the corrector id
     */
    public function getCorrectionSummary(string $item_key, string $corrector_key): ?CorrectionSummary
    {
        $essayRepo = $this->di->getEssayRepo();
        if (!empty($repoEssay = $essayRepo->getEssayByWriterIdAndTaskId((int) $item_key, $this->task->getTaskId()))) {
            if (!empty($repoSummary = $essayRepo->getCorrectorSummaryByEssayIdAndCorrectorId(
                $repoEssay->getId(), (int) $corrector_key)
            )) {
                return new CorrectionSummary(
                    $repoSummary->getSummaryText(),
                    $repoSummary->getPoints(),
                    $repoSummary->getGradeLevelId() ? (string) $repoSummary->getGradeLevelId() : null
                );
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector key is a string of the corrector id
     */
    public function setCorrectionSummary(string $item_key, string $corrector_key, CorrectionSummary $summary) : void
    {
        $essayRepo = $this->di->getEssayRepo();
        if (!empty($repoEssay = $essayRepo->getEssayByWriterIdAndTaskId((int) $item_key, $this->task->getTaskId()))) {
            $repoSummary = $essayRepo->getCorrectorSummaryByEssayIdAndCorrectorId($repoEssay->getId(), (int) $corrector_key);
            if (!isset($repoSummary)) {
                $repoSummary = new CorrectorSummary();
                $repoSummary->setEssayId($repoEssay->getId());
                $repoSummary->setCorrectorId((int) $corrector_key);
                $essayRepo->createCorrectorSummary($repoSummary);
            }
            $repoSummary->setSummaryText($summary->getText());
            $repoSummary->setPoints($summary->getPoints());
            $repoSummary->setGradeLevelId($summary->getGradeKey() ? (int) $summary->getGradeKey() : null);
            $essayRepo->updateCorrectorSummary($repoSummary);
        }
    }
}