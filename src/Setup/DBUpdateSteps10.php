<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup\Objective;
use ILIAS\Setup\Environment;

class DBUpdateSteps10 implements \ilDatabaseUpdateSteps
{
    protected \ilDBInterface $db;

    public function prepare(\ilDBInterface $db): void
    {
        $this->db = $db;
    }

    public function step_1(): void
    {
        // Build initial structure
    }


    public function step_2(): void
    {
        // Migrate data tables
    }

    public function step_3(): void
    {
        // Remove old tables or do other stuff
    }
}
