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

namespace ILIAS\Plugin\LongEssayAssessment\Data\System;

use ilDBInterface;
use ilDBConstants;

class CreateTables
{
    public function __construct(private readonly ilDBInterface $db)
    {
    }

    public function all(): void
    {
        $this->config();
    }

    public function config(): void
    {
        $this->db->createTable('xlas_sy_config', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'writer_url' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 250
            ],
            'corrector_url' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 250
            ],
            'primary_color' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 250
            ],
            'primary_text_color' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 250
            ],
            'simulate_offline' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'path_to_ghostscript' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 100
            ]
        ]);
    }
}
