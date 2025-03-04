<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ilDBStepExecutionDB;
use ilDBStepReader;
use ILIAS\Plugin\LongEssayAssessment\System\File\Stakeholder;
use ILIAS\ResourceStorage\Stakeholder\Repository\StakeholderDBRepository;

/**
 * TODO: Failed update should be repetable
 * - it should be possible to execute steps 1 to 3 again
 * - drop tables before creation in step 1 (done)
 * - catch errors in step 1 to 3 and
 *      - delete entries for step 1 to 3 in il_db_steps
 *      - throw a runtime exception to abort the plugin update with explaining error message
 * - override ilPlugin::isActive to check if steps are executed
 */
class DBUpdateSteps10 implements \ilDatabaseUpdateSteps
{
    public const PREFIX = 'step_';

    private \ilDBInterface $db;
    private V10Migration $v10_migration;

    public function prepare(\ilDBInterface $db): void
    {
        $this->db = $db;
        $this->v10_migration = new V10Migration($db);
    }

    /**
     * Install the plugin
     * This can be called from the Plugin Administration GUI
     * @see \ilDatabaseUpdateStepsExecutedObjective::achieve
     */
    public function install(\ilDBInterface $db): void
    {
        $this->prepare($db);

        $execution_log = new ilDBStepExecutionDB($this->db, fn() => new \DateTime());
        $step_reader = new ilDBStepReader();

        $last_started_step = $execution_log->getLastStartedStep(self::class);
        $last_finished_step = $execution_log->getLastFinishedStep(self::class);

        foreach ($step_reader->readStepNumbers(self::class, self::PREFIX) as $step) {
            if ($step <= $last_finished_step) {
                continue;
            }
            $execution_log->started(self::class, $step);
            $method = self::PREFIX . $step;
            $this->$method();
            $execution_log->finished(self::class, $step);
        }
    }

    /**
     * Uninstall the plugin
     * This can be called from the Plugin Administration GUI
     */
    public function uninstall(\ilDBInterface $db): void
    {
        $this->prepare($db);
        $this->v10_migration->removeNewTables();
        $this->db->manipulate("DELETE FROM il_db_steps WHERE `class` = " . $this->db->quote(self::class));
    }

    public function step_1(): void
    {
        $this->v10_migration->createNewTables();
    }

    public function step_2(): void
    {
        if ($this->db->tableExists('xlas_plugin_config')) {
            // old tables still exist, habe been updated in DBUpdateSteps9 to latest state for ILIAS 9
            // now copy their content to the new tables
            $this->v10_migration->migrateTables();
        }
    }

    public function step_3(): void
    {
        $this->v10_migration->migrateStakeholders();
    }

    /**
     * TODO: postpone the removing
     * - give admins a chance to check interactively if everything is ok
     * - eventually just rename the tables
     * - provide a migration step to drop them
    */
    public function step_4(): void
    {
        $this->v10_migration->removeOldTables();
    }
}
