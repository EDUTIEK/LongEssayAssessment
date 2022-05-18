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
        $repo = $this->di->getTaskRepo();
        $task = $repo->getTaskSettingsById($this->object->getId());

        return new CorrectionTask(
            $this->object->getTitle(),
            $task->getInstructions(),
            $this->plugin->dbTimeToUnix($task->getCorrectionEnd()));
    }

    /**
     * @inheritDoc
     */
    public function getGradeLevels(): array
    {
        // TODO: get the configured grade levels
        return [
            new CorrectionGradeLevel('key1', "bestanden", 5),
            new CorrectionGradeLevel('key2', 'nicht bestanden', 0)
        ];
    }

    /**
     * @inheritDoc
     */
    public function getCorrectionItems(): array
    {
        // TODO: Implement getCorrectionItems() method.
    }

    /**
     * @inheritDoc
     */
    public function getCurrentItem(): ?CorrectionItem
    {
        // TODO: Implement getCurrentItem() method.
    }

    /**
     * @inheritDoc
     */
    public function getEssayOfItem(string $item_key): WrittenEssay
    {
        // TODO: Implement getEssayOfItem() method.
    }

    /**
     * @inheritDoc
     */
    public function getCorrectorsOfItem(string $item_key): array
    {
        // TODO: Implement getCorrectorsOfItem() method.
    }

    /**
     * @inheritDoc
     */
    public function getCorrectionSummary(string $item_key, string $corrector_key): CorrectionSummary
    {
        // TODO: Implement getCorrectionSummary() method.
    }

    /**
     * @inheritDoc
     */
    public function setCorrectionSummary(string $item_key, string $corrector_key, CorrectionSummary $summary)
    {
        // TODO: Implement setCorrectionSummary() method.
    }
}