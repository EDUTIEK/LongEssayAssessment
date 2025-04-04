<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup;
use ILIAS\Setup\Config;
use ILIAS\Setup\Agent;
use ILIAS\Setup\Objective;
use ILIAS\Refinery\Transformation;
use ILIAS\Setup\Metrics;
use ILIAS\Setup\ObjectiveConstructor;
use ILIAS\Setup\NullConfig;
use LogicException;

/**
 * New SetupAgent for the plugin LongEssayAssessment
 * Provides new DBUpdateSteps from Version 4 (ILIAS 10) onwards.
 */
class SetupAgent implements Agent
{
    public function __construct()
    {
    }

    /**
     * @inheritdoc
     */
    public function hasConfig(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getArrayToConfigTransformation(): Transformation
    {
        throw new LogicException(self::class . " has no Config.");
    }

    /**
     * @inheritdoc
     */
    public function getInstallObjective(Config $config = null): Objective
    {
        return new Setup\ObjectiveCollection(
            'ILIAS\Plugin\LongEssayAssessment',
            true,
            new DBUpdateObjective9(),
            new \ilDatabaseUpdateStepsExecutedObjective(new DBUpdateSteps10()),
            new \ilComponentInstallPluginObjective("LongEssayAssessment"),
            new \ilComponentUpdatePluginObjective("LongEssayAssessment"),
            new \ilComponentActivatePluginsObjective("LongEssayAssessment")
        );
    }

    /**
     * @inheritdoc
     */
    public function getUpdateObjective(Config $config = null): Objective
    {

        return new Setup\ObjectiveCollection(
            'ILIAS\Plugin\LongEssayAssessment',
            true,
            new DBUpdateObjective9(),
            new \ilDatabaseUpdateStepsExecutedObjective(new DBUpdateSteps10()),
            new \ilComponentInstallPluginObjective("LongEssayAssessment"),
            new \ilComponentUpdatePluginObjective("LongEssayAssessment"),
            new \ilComponentActivatePluginsObjective("LongEssayAssessment")
        );

    }

    /**
     * @inheritdoc
     */
    public function getBuildObjective(): Objective
    {
        return new Setup\ObjectiveCollection(
            'ILIAS\Plugin\LongEssayAssessment',
            true,
            new EndpointsBuildObjective(),
            new ResourcesCopiedObjective(),
            new ModelObjective(),
        );
    }

    /**
     * @inheritdoc
     */
    public function getStatusObjective(Metrics\Storage $storage): Objective
    {
        return new Setup\ObjectiveCollection(
            'ILIAS\Plugin\LongEssayAssessment',
            true,
            new \ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new DBUpdateSteps9()),
            new \ilDatabaseUpdateStepsMetricsCollectedObjective($storage, new DBUpdateSteps10())
        );
    }

    /**
     * @inheritdoc
     */
    public function getMigrations(): array
    {
        return [
            #new StorageStakeholderMigration()
            // This is not the way to migrate to the new Resource Stakeholder at the moment
            // I want to keep this for now to demonstrate the difference between DBUpdateStep and Migration as I think a
            // migration was originally intendet for a chenge in Stakholder.

        ];
    }

    /**
     * @inheritdoc
     */
    public function getNamedObjectives(?Config $config = null): array
    {
        return [];
    }
}
