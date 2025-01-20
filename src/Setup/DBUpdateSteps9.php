<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup\Objective;
use ILIAS\Setup\Environment;
use _PHPStan_9815bbba4\Symfony\Component\Console\Exception\LogicException;

class DBUpdateSteps9 implements \ilDatabaseUpdateSteps
{
    protected \ilDBInterface $db;
    protected int $db_version = 0;
    public function prepare(\ilDBInterface $db): void
    {
        $this->db = $db;

        $query = $db->query("SELECT * FROM il_plugin WHERE plugin_id = 'xlas'");

        if ($ret = $db->fetchAssoc($query)) {
            $this->db_version = (int) $ret['db_version'];
        }

        if ($this->db_version < 105) {
            throw new LogicException("Plugin database version is too low. Please update this Plugin atleast one time with LongEssayAssessment 3 / ILIAS 9.");
        }
    }

    public function step_1(): void
    {
        if (105 <= $this->db_version) {
            return;
        }

        $ilDB = $this->db;
        if (!$ilDB->tableColumnExists('xlas_corr_setting', 'reports_available_start')) {
            $ilDB->addTableColumn('xlas_corr_setting', 'reports_available_start', array(
                'type' => 'timestamp'
            ));
        }
    }


    public function step_2(): void
    {
        if (106 <= $this->db_version) {
            return;
        }

        $ilDB = $this->db;
        if ($ilDB->tableColumnExists('xlas_corrector_summary', 'include_writer_notes')) {
            $ilDB->dropTableColumn('xlas_corrector_summary', 'include_writer_notes');
        }
        if ($ilDB->tableColumnExists('xlas_corrector_prefs', 'include_writer_notes')) {
            $ilDB->dropTableColumn('xlas_corrector_prefs', 'include_writer_notes');
        }
    }


    public function step_3(): void
    {
        if (107 <= $this->db_version) {
            return;
        }

        $ilDB = $this->db;
        if (!$ilDB->tableColumnExists('xlas_task_settings', 'statistics_available')) {
            $ilDB->addTableColumn('xlas_task_settings', 'statistics_available', [
                'notnull' => '1',
                'type' => 'integer',
                'length' => 4,
                'default' => 0
            ]);
        }
    }


    public function step_4(): void
    {
        if (108 <= $this->db_version) {
            return;
        }

        $ilDB = $this->db;
        if (!$ilDB->tableColumnExists('xlas_writer_prefs', 'word_count_enabled')) {
            $ilDB->addTableColumn('xlas_writer_prefs', 'word_count_enabled', array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => 4,
                'default' => 0
            ));
        }
    }


    public function step_5(): void
    {
        if (109 <= $this->db_version) {
            return;
        }

        $ilDB = $this->db;
        if (!$ilDB->tableColumnExists('xlas_writer_prefs', 'word_count_characters')) {
            $ilDB->addTableColumn('xlas_writer_prefs', 'word_count_characters', array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => 4,
                'default' => 0
            ));
        }
    }


    public function step_6(): void
    {
        if (110 <= $this->db_version) {
            return;
        }

        $ilDB = $this->db;
        if (!$ilDB->tableColumnExists('xlas_task_settings', 'task_type')) {
            $ilDB->addTableColumn('xlas_task_settings', 'task_type', array(
                'notnull' => '1',
                'type' => 'text',
                'length' => 50,
                'default' => 'essay_editor'
            ));
        }
    }


    public function step_7(): void
    {
        if (111 <= $this->db_version) {
            return;
        }

        $ilDB = $this->db;
        if (!$ilDB->tableColumnExists('xlas_plugin_config', 'path_to_ghostscript')) {
            $ilDB->addTableColumn('xlas_plugin_config', 'path_to_ghostscript', array(
                'notnull' => '0',
                'type' => 'text',
                'length' => 100,
                'default' => null
            ));
        }
    }


    public function step_8(): void
    {
        if (112 <= $this->db_version) {
            return;
        }

        $ilDB = $this->db;
        if (!$ilDB->tableColumnExists('xlas_task_settings', 'writing_limit_minutes')) {
            $ilDB->addTableColumn('xlas_task_settings', 'writing_limit_minutes', array(
                'notnull' => '0',
                'type' => 'integer',
                'length' => 4,
                'default' => null
            ));
        }
    }
}
