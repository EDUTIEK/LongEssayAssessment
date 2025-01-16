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

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ilDBInterface;
use ilDBConstants;

class CreateTables
{
    public function __construct(private readonly ilDBInterface $db)
    {
    }

    public function all(): void
    {
        $this->correctorAssignment();
        $this->resource();
        $this->settings();
        $this->writerComment();
    }

    public function correctorAssignment(): void
    {
        $this->db->createTable('xlas_ta_corr_assign', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'writer_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'corrector_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'position' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'task_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function resource(): void
    {
        $this->db->createTable('xlas_ta_resource', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'task_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'title' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 255
            ],
            'description' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'url' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 4000
            ],
            'type' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 10
            ],
            'availability' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 10
            ],
            'file_id' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 50
            ]
        ]);
    }
    public function settings(): void
    {
        $this->db->createTable('xlas_ta_settings', [
            'task_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'instructions' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'solution' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ]
        ]);
    }
    public function writerComment(): void
    {
        $this->db->createTable('xlas_ta_writer_comment', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'task_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'comment' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'start_position' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'end_position' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
}
