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
        $this->v10_migration->removeOldTables();
    }

    public function step_4(): void
    {
        $stakeholder = new Stakeholder();
        $qname = $stakeholder->getFullyQualifiedClassName();
        $id = $stakeholder->getId();
        $old_stkh = ['xlas_essay_image', 'xlas_essay', 'xlas_resource'];

        // create new stakeholder if it does not exists
        $this->db->manipulateF("REPLACE INTO " . StakeholderDBRepository::TABLE_NAME_REL . " VALUES (%s, %s)", ['text', 'text'], [$id, $qname]);

        // migrate all files from stakeholder xlas_essay_image, xlas_essay, xlas_resource to the new stakeholder
        $this->db->manipulate(
            "UPDATE " . StakeholderDBRepository::TABLE_NAME
            . " SET stakeholder_id = " . $this->db->quote($id, 'text')
            . " WHERE " . $this->db->in('stakeholder_id', $old_stkh, false, 'text')
        );

        // remove old stakeholder xlas_essay_image, xlas_essay, xlas_resource
        $this->db->manipulate("DELETE FROM " . StakeholderDBRepository::TABLE_NAME_REL . " WHERE " . $this->db->in('id', $old_stkh, false, 'text'));
    }
}
