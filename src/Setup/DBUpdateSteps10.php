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
    private \ilDBInterface $db;
    private V10Migration $v10_migration;

    public function prepare(\ilDBInterface $db): void
    {
        $this->db = $db;
        $this->v10_migration = new V10Migration($db);
    }

    public function step_1(): void
    {
        $this->v10_migration->createNewTables();
    }

    public function step_2(): void
    {
        $this->v10_migration->migrateTables();
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
