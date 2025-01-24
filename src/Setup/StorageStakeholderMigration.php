<?php

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup\Migration;
use ILIAS\Setup\Environment;
use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;
use ILIAS\Plugin\LongEssayAssessment\System\File\Stakeholder;
use ILIAS\ResourceStorage\Stakeholder\Repository\StakeholderDBRepository;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;

class StorageStakeholderMigration implements Migration
{
    private const DEFAULT_AMOUNT_OF_STEPS = 10000;
    private array $old_stkh;
    private array $old_stkh_map;

    protected \ilResourceStorageMigrationHelper $helper;


    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return "Migration of LongEssayAssessment files to a new Stakeholder.";
    }

    /**
     * @inheritDoc
     */
    public function getDefaultAmountOfStepsPerRun(): int
    {
        return self::DEFAULT_AMOUNT_OF_STEPS;
    }

    /**
     * @inheritDoc
     */
    public function getPreconditions(Environment $environment): array
    {
        return \ilResourceStorageMigrationHelper::getPreconditions();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Environment $environment): void
    {
        $this->helper = new \ilResourceStorageMigrationHelper(
            new Stakeholder(),
            $environment
        );

        $old_stakeholder = [
            new \ILIAS\Plugin\LongEssayAssessment\WriterAdmin\EssayImageResourceStakeholder(),
            new \ILIAS\Plugin\LongEssayAssessment\WriterAdmin\PDFVersionResourceStakeholder(),
            new \ILIAS\Plugin\LongEssayAssessment\Task\ResourceResourceStakeholder()
        ];

        $this->old_stkh = [];
        $this->old_stkh_map = [];

        foreach ($old_stakeholder as $stakeholder) {
            $this->old_stkh[] = $stakeholder->getId();
            $this->old_stkh_map[$stakeholder->getId()] = $stakeholder;
        }
    }

    /**
     * @inheritDoc
     */
    public function step(Environment $environment): void
    {
        $db = $this->helper->getDatabase();
        $query = $db->query("SELECT " . StakeholderDBRepository::IDENTIFICATION . " AS rid, stakeholder_id FROM " . StakeholderDBRepository::TABLE_NAME
            . " WHERE " . $db->in("stakeholder_id", $this->old_stkh, false, "text")
            . " LIMIT 1");
        $res = $db->fetchObject($query);
        $rid = $res?->rid;
        $stakeholder_id = $res?->stakeholder_id;

        if ($rid !== null) {
            $stakeholder = $this->old_stkh_map[$stakeholder_id];

            $this->helper->moveResourceToNewStakeholderAndOwner(
                new ResourceIdentification($rid),
                $stakeholder,
                $this->helper->getStakeholder()
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getRemainingAmountOfSteps(): int
    {
        $db = $this->helper->getDatabase();
        $query = $db->query("SELECT COUNT(*) AS amount FROM " . StakeholderDBRepository::TABLE_NAME . " WHERE " . $db->in("stakeholder_id", $this->old_stkh, false, "text"));
        $res = $db->fetchObject($query);
        return (int) $res?->amount;
    }
}
