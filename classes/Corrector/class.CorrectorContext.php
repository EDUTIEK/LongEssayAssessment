<?php

namespace ILIAS\Plugin\LongEssayTask\Corrector;

use Edutiek\LongEssayService\Corrector\Context;
use Edutiek\LongEssayService\Corrector\Service;
use Edutiek\LongEssayService\Data\CorrectionGradeLevel;
use Edutiek\LongEssayService\Data\CorrectionItem;
use Edutiek\LongEssayService\Data\CorrectionSummary;
use Edutiek\LongEssayService\Data\CorrectionTask;
use Edutiek\LongEssayService\Data\Corrector;
use Edutiek\LongEssayService\Data\WrittenEssay;
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
    public function selectedWriterId(int $id) : void {
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
    public function getCorrectionTask(): CorrectionTask
    {
        return new CorrectionTask(
            $this->object->getTitle(),
            $this->task->getInstructions(),
            $this->plugin->dbTimeToUnix($this->task->getCorrectionEnd()));
    }

    /**
     * @inheritDoc
     */
    public function getGradeLevels(): array
    {
        // TODO: get the configured grade levels

        // fake grade levels
        return [
            new CorrectionGradeLevel('key1', "bestanden", 5),
            new CorrectionGradeLevel('key2', 'nicht bestanden', 0)
        ];
    }

    /**
     * @inheritDoc
     * here:    the item keys are strings of the writer ids
     *          the item titles are the pseudonymous writer names
     */
    public function getCorrectionItems(): array
    {
        $items = [];

        $corep = $this->di->getCorrectorRepo();
        $wirep = $this->di->getWriterRepo();

        if (!empty($corrector = $corep->getCorrectorByUserId($this->user->getId(), $this->task->getTaskId()))) {
            foreach ($corep->getAssignmentsByCorrectorId($corrector->getId()) as $assignment) {
                if (!empty($writer = $wirep->getWriterById($assignment->getWriterId()))) {
                    $items[] = new CorrectionItem(
                        (string) $writer->getId(),
                        $writer->getPseudonym()
                    );
                }
            }
        }
        return $items;
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the item title is the pseudonymous writer name
     */
    public function getCurrentItem(): ?CorrectionItem
    {
        $corep = $this->di->getCorrectorRepo();
        $wirep = $this->di->getWriterRepo();

        if (isset($this->selected_writer_id)) {
            if (!empty($corrector = $corep->getCorrectorByUserId($this->user->getId(), $this->task->getTaskId())) &&
                !empty($writer = $wirep->getWriterById((int) $this->selected_writer_id)))
            {
                foreach ($corep->getAssignmentsByWriterId($this->selected_writer_id) as $assignment) {
                    if ($assignment->getCorrectorId() == $corrector->getId()) {
                        return new CorrectionItem(
                            (string) $writer->getId(),
                            $writer->getPseudonym()
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
        if (!empty($essay = $this->di->getEssayRepo()->getEssayByWriterIdAndTaskId(
                (int) $item_key, $this->task->getTaskId()))) {
            return new WrittenEssay(
                $essay->getWrittenText(),
                $essay->getRawTextHash(),
                $essay->getProcessedText(),
                $this->plugin->dbTimeToUnix($essay->getEditStarted()),
                $this->plugin->dbTimeToUnix($essay->getEditEnded()),
                (bool) $essay->isIsAuthorized()
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
        // TODO: Implement getCorrectorsOfItem() method.
        return [];
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector key is a string of the corrector id
     */
    public function getCorrectionSummary(string $item_key, string $corrector_key): ?CorrectionSummary
    {
        // TODO: Implement getCorrectionSummary() method.
        return null;
    }

    /**
     * @inheritDoc
     * here:    the item key is a string of the writer id
     *          the corrector key is a string of the corrector id
     */
    public function setCorrectionSummary(string $item_key, string $corrector_key, CorrectionSummary $summary) : void
    {
        // TODO: Implement setCorrectionSummary() method.
    }
}