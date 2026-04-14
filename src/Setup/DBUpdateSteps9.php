<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use Exception;
use ILIAS\Setup\Objective;
use ILIAS\Setup\Environment;

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
            throw new Exception("Plugin database version is too low. Please update this Plugin atleast one time with LongEssayAssessment 3 / ILIAS 9.");
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

    public function step_9(): void
    {
        if (113 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        // obsolete
    }

    public function step_10(): void
    {
        if (114 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if (!$ilDB->tableColumnExists('xlas_writer', 'earliest_start')) {
            $ilDB->addTableColumn('xlas_writer', 'earliest_start', array(
                'notnull' => '0',
                'type' => 'timestamp',
                'default' => null
            ));
        }
    }

    public function step_11(): void
    {
        if (115 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if (!$ilDB->tableColumnExists('xlas_writer', 'latest_end')) {
            $ilDB->addTableColumn('xlas_writer', 'latest_end', array(
                'notnull' => '0',
                'type' => 'timestamp',
                'default' => null
            ));
        }
    }

    public function step_12(): void
    {
        if (116 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if (!$ilDB->tableColumnExists('xlas_writer', 'time_limit_minutes')) {
            $ilDB->addTableColumn('xlas_writer', 'time_limit_minutes', array(
                'notnull' => '0',
                'type' => 'integer',
                'length' => 4,
                'default' => null
            ));
        }
    }

    public function step_13(): void
    {
        if (117 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if ($ilDB->tableExists('xlas_time_extension')) {
            $query = "
                SELECT s.writing_end,  t.task_id, t.writer_id, t.minutes
                FROM xlas_task_settings s
                JOIN xlas_time_extension t ON t.task_id = s.task_id
                WHERE s.writing_end IS NOT NULL AND t.minutes > 0
            ";

            $result = $ilDB->query($query);
            while ($row = $ilDB->fetchAssoc($result)) {
                $end = new \DateTime($row['writing_end']);
                $seconds = (int) $row['minutes'] * 60;
                $end->setTimestamp($end->getTimestamp() + $seconds);

                $ilDB->update(
                    'xlas_writer',
                    [
                    'latest_end' => ['string', $end->format('Y-m-d H:i:s')]
                ],
                    [
                        'id' => ['integer', (int) $row['writer_id']],
                    ]
                );
            }
        }
    }

    public function step_14(): void
    {
        if (118 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if (!$ilDB->tableColumnExists('xlas_writer', 'working_start')) {

            $ilDB->addTableColumn('xlas_writer', 'working_start', array(
                'notnull' => '0',
                'type' => 'timestamp',
                'default' => null
            ));

            $query = "
                SELECT e.writer_id, e.edit_started
                FROM xlas_essay e
                WHERE e.edit_started IS NOT NULL
            ";

            $result = $ilDB->query($query);
            while ($row = $ilDB->fetchAssoc($result)) {
                $ilDB->update(
                    'xlas_writer',
                    [
                    'working_start' => ['string', $row['edit_started']]
                ],
                    [
                        'id' => ['integer', (int) $row['writer_id']],
                    ]
                );
            }
        }
    }

    public function step_15(): void
    {
        if (119 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        $query = "
            UPDATE xlas_log_entry SET category = 'working_time' WHERE category = 'extension';
        ";

        $ilDB->manipulate($query);
    }

    public function step_16(): void
    {
        if (120 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if (!$ilDB->tableColumnExists('xlas_rating_crit', 'is_general')) {
            $ilDB->addTableColumn('xlas_rating_crit', 'is_general', array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => 4,
                'default' => '0'
            ));
        }
    }

    public function step_17(): void
    {
        if (121 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if ($ilDB->tableExists('xlas_crit_points')) {
            // simplify column name
            if ($ilDB->tableColumnExists('xlas_crit_points', 'corr_comment_id')) {
                $ilDB->renameTableColumn('xlas_crit_points', 'corr_comment_id', 'comment_id');
            }

            // cleanup orphaned data
            $ilDB->manipulate("DELETE FROM xlas_crit_points WHERE criterion_id NOT IN (SELECT id FROM xlas_rating_crit)");
            $ilDB->manipulate("DELETE FROM xlas_crit_points WHERE comment_id NOT IN (SELECT id FROM xlas_corrector_comment)");

            // prepare storing pure comment points in this table
            // prepare storing points for general criteria
            $ilDB->manipulate("ALTER TABLE `xlas_crit_points` MODIFY `criterion_id` INT NULL DEFAULT NULL");
            $ilDB->manipulate("ALTER TABLE `xlas_crit_points` MODIFY `comment_id` INT NULL DEFAULT NULL");

            // add essay_id for quicker access
            if (!$ilDB->tableColumnExists('xlas_crit_points', 'essay_id')) {
                $ilDB->manipulate("ALTER TABLE `xlas_crit_points` ADD COLUMN `essay_id` INT NULL DEFAULT '0' AFTER `id`;");
                $ilDB->manipulate("UPDATE xlas_crit_points INNER JOIN xlas_corrector_comment ON xlas_crit_points.comment_id = xlas_corrector_comment.id 
                            SET xlas_crit_points.essay_id = xlas_corrector_comment.essay_id");
                $ilDB->manipulate("ALTER TABLE `xlas_crit_points` MODIFY `essay_id` INT NOT NULL;");
                $ilDB->addIndex('xlas_crit_points', ['essay_id'], 'i3');
            }
            // add corrector_id for quicker access
            if (!$ilDB->tableColumnExists('xlas_crit_points', 'corrector_id')) {
                $ilDB->manipulate("ALTER TABLE `xlas_crit_points` ADD COLUMN `corrector_id` INT NULL DEFAULT '0' AFTER `essay_id`;");
                $ilDB->manipulate("UPDATE xlas_crit_points INNER JOIN xlas_corrector_comment ON xlas_crit_points.comment_id = xlas_corrector_comment.id 
                            SET xlas_crit_points.corrector_id = xlas_corrector_comment.corrector_id");
                $ilDB->manipulate("ALTER TABLE `xlas_crit_points` MODIFY `corrector_id` INT NOT NULL;");
                $ilDB->addIndex('xlas_crit_points', ['corrector_id'], 'i4');
            }

            //
            $ilDB->manipulate("ALTER TABLE `xlas_crit_points` MODIFY `points` DOUBLE NOT NULL;");

            $ilDB->renameTable('xlas_crit_points', 'xlas_corrector_points');
        }
    }

    public function step_18(): void
    {
        if (122 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if ($ilDB->tableColumnExists('xlas_corrector_comment', 'points')) {
            $result = $ilDB->query("SELECT c.essay_id, c.corrector_id, c.id AS comment_id, c.points FROM xlas_corrector_comment c WHERE c.points > 0");
            while ($row = $ilDB->fetchAssoc($result)) {
                $id = $ilDB->nextId('xlas_corrector_points');
                $ilDB->insert('xlas_corrector_points', [
                    'id' => ['integer', $id],
                    'essay_id' => ['integer', $row['essay_id']],
                    'corrector_id' => ['integer', $row['corrector_id']],
                    'comment_id' => ['integer', $row['comment_id']],
                    'points' => ['float', $row['points']]
                ]);

                $ilDB->manipulate("UPDATE xlas_corrector_comment SET points = 0 where id = " . $ilDB->quote('integer', $row['comment_id']));
            }

            $ilDB->dropTableColumn('xlas_corrector_comment', 'points');
        }
    }

    public function step_19(): void
    {
        if (123 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if (!$ilDB->tableColumnExists('xlas_resource', 'embedded')) {
            $ilDB->addTableColumn('xlas_resource', 'embedded', [
                'notnull' => '1',
                'type' => 'integer',
                'length' => 4,
                'default' => '0'
            ]);
        }
    }

    public function step_20(): void
    {
        if (124 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if (!$ilDB->tableColumnExists('xlas_writer_comment', 'writer_id')) {
            $ilDB->addTableColumn('xlas_writer_comment', 'writer_id', [
                'notnull' => '1',
                'type' => 'integer',
                'length' => 4
            ]);
        }
    }

    public function step_21(): void
    {
        if (125 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if (!$ilDB->tableColumnExists('xlas_writer_comment', 'parent_number')) {
            $ilDB->addTableColumn('xlas_writer_comment', 'parent_number', [
                'notnull' => '1',
                'type' => 'integer',
                'length' => 4
            ]);
        }
    }

    // Version 3 steps 126 to 129 are done in DBUpdateSteps10
    // Version 3 step 238 must be done here, too, before the V10Migration

    public function step_22(): void
    {
        if (128 <= $this->db_version) {
            return;
        }
        $ilDB = $this->db;

        if (!$ilDB->tableColumnExists('xlas_task_settings', 'forwarding_url')) {
            $ilDB->addTableColumn('xlas_task_settings', 'forwarding_url', [
                'type' => 'text',
                'length' => '250',
                'default' => null
            ]);
        }
    }

    /**
     * Extra step to set missing service versions for version 3
     * Missing service versions for version 10 will be added in Steps for 10
     * @see \ILIAS\Plugin\LongEssayAssessment\Setup\DBUpdateSteps10::step_82
     */
    public function step_23(): void
    {
        if ($this->db->tableExists('xlas_essay')) {
            $queries = [
                "UPDATE xlas_essay SET service_version = 20210923 WHERE service_version = 0 AND writing_authorized < '2023-12-18 00:00:00'",
                "UPDATE xlas_essay SET service_version = 20231218 WHERE service_version = 0 AND writing_authorized < '2024-06-03 00:00:00'",
                "UPDATE xlas_essay SET service_version = 20240603 WHERE service_version = 0",
            ];

            foreach ($queries as $query) {
                $this->db->manipulate($query);
            }
        }
    }
}
