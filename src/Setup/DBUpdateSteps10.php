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
use ilDBConstants;
use ilDBUpdateNewObjectType;
use ILIAS\Plugin\LongEssayAssessment\System\Data\Config;

class DBUpdateSteps10 implements \ilDatabaseUpdateSteps
{
    public const PREFIX = 'step_';

    private \ilDBInterface $db;
    private V10Migration $v10_migration;

    public function prepare(\ilDBInterface $db): void
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

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
        $this->db->manipulate("DELETE FROM il_db_steps WHERE `class` = " . $this->db->quote(DBUpdateSteps9::class));
        $this->db->manipulate("DELETE FROM il_db_steps WHERE `class` = " . $this->db->quote(self::class));

        $tables = [
            'xlas_as_alert',
            'xlas_as_corr_settings',
            'xlas_as_corrector',
            'xlas_as_dis_groups',
            'xlas_as_exp_file',
            'xlas_as_exp_settings',
            'xlas_as_grade_level',
            'xlas_as_location',
            'xlas_as_log_entry',
            'xlas_as_noti_queue',
            'xlas_as_noti_settings',
            'xlas_as_noti_users',
            'xlas_as_orga_settings',
            'xlas_as_pdf_config',
            'xlas_as_pdf_settings',
            'xlas_as_token',
            'xlas_as_writer',
            'xlas_et_essay',
            'xlas_et_essay_image',
            'xlas_et_marked_pdf',
            'xlas_et_write_settings',
            'xlas_et_writer_history',
            'xlas_et_writer_notice',
            'xlas_et_writer_prefs',
            'xlas_sy_config',
            'xlas_ta_corr_assign',
            'xlas_ta_corr_comm',
            'xlas_ta_corr_points',
            'xlas_ta_corr_prefs',
            'xlas_ta_corr_settings',
            'xlas_ta_corr_snippet',
            'xlas_ta_corr_summary',
            'xlas_ta_corr_ta_prefs',
            'xlas_ta_corr_template',
            'xlas_ta_rating_crit',
            'xlas_ta_resource',
            'xlas_ta_settings',
            'xlas_ta_writer_anno'
        ];

        foreach ($tables as $table) {
            $this->db->dropTable($table, false);
        }
    }

    private function ensureTable(string $name, array $fields): void
    {
        if (!$this->db->tableExists($name)) {
            $this->db->createTable($name, $fields);
        }
        if (!$this->db->sequenceExists($name)) {
            $this->db->createSequence($name);
        }
    }

    private function ensureTableColumn(string $table, string $column, array $options): void
    {
        if (!$this->db->tableColumnExists($table, $column)) {
            $this->db->addTableColumn($table, $column, $options);
        }
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

    public function step_4(): void
    {
        $this->v10_migration->removeOldTables();
    }

    public function step_5(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_writer', 'stitch_needed')) {
            $this->db->addTableColumn('xlas_as_writer', 'stitch_needed', [
                'notnull' => 1,
                'type' => ilDBConstants::T_INTEGER,
                'length' => 1,
                'default' => 0
            ]);
        }
    }
    public function step_6(): void
    {
        # Calculate new stitch_needed flag for all writer
        $summaries_by_task_and_writer = [];
        $writers_with_stitch = [];

        $sql = "SELECT 
            summary.id                      AS id, 
            writer.id                       AS writer_id, 
            essay.task_id                   AS task_id,
            summary.corection_authorized    AS correction_authorized,
            summary.points                  AS points,
            settings.required_correctors    AS required_correctors,
            settings.stitch_when_distance   AS stitch_when_distance,
            settings.stitch_when_decimals   AS stitch_when_decimals,
            settings.max_auto_distance      AS max_auto_distance
            FROM xlas_et_corr_summary       AS summary 
            LEFT JOIN xlas_et_essay         AS essay    ON (summary.essay_id = essay.id)
            LEFT JOIN xlas_as_writer        AS writer   ON (essay.writer_id = writer.id)
            LEFT JOIN xlas_as_corr_settings AS settings ON (writer.ass_id = settings.ass_id);";

        $query = $this->db->query($sql);
        foreach ($this->db->fetchAll($query, ilDBConstants::FETCHMODE_OBJECT) ?? [] as $obj) {
            $summaries_by_task_and_writer[$obj->task_id][$obj->writer_id][] = $obj;
        }

        foreach ($summaries_by_task_and_writer as $task_id => $summaries_by_writer) {
            foreach ($summaries_by_writer as $writer_id => $summaries) {
                $required_correctors = (int) $summaries[0]?->required_correctors ?? 0;
                $stitch_when_distance = (bool) $summaries[0]?->stitch_when_distance ?? 0;
                $max_auto_distance = (int) $summaries[0]?->max_auto_distance ?? 0;
                $stitch_when_decimals = (bool) $summaries[0]?->stitch_when_decimals ?? 0;

                if (count($summaries) < $required_correctors) {
                    continue;// not enough correctors authorized => not yet ready
                }

                $minPoints = null;
                $maxPoints = null;
                foreach ($summaries as $summary) {
                    if ($summary->correction_authorized === null) {
                        continue 2; // At least one correction is not authorized
                    }
                    if ($summary->points !== null) {
                        $minPoints = (isset($minPoints) ? min($minPoints, $summary->points) : $summary->points);
                        $maxPoints = (isset($maxPoints) ? max($maxPoints, $summary->points) : $summary->points);
                    }
                }


                if ($minPoints === null && $maxPoints === null) {
                    continue;// none of the correctors has given points
                }


                if ($stitch_when_distance) {
                    if (abs($maxPoints - $minPoints) > $max_auto_distance) {
                        // distance is within limit
                        $writers_with_stitch[] = $writer_id;
                        continue;
                    }
                }

                if ($stitch_when_decimals) {
                    $average = null;
                    $countOfPoints = 0;
                    $sumOfPoints = null;
                    foreach ($summaries as $summary) {
                        if ($summary->points !== null) {
                            $countOfPoints++;
                            $sumOfPoints += $summary->points;
                        }
                    }
                    if ($countOfPoints > 0) {
                        $average = $sumOfPoints / $countOfPoints;
                    }

                    if ($average === null || floor($average) < $average) {
                        // one corrector hasn't stored points (should not happen)
                        $writers_with_stitch[] = $writer_id;
                        continue;
                    }
                }
            }
        }

        $sql = "UPDATE xlas_as_writer SET stitch_needed=1 WHERE "
            . $this->db->in("id", $writers_with_stitch, false, "integer");

        $this->db->manipulate($sql);
    }
    public function step_7(): void
    {
        # Move CorrectionSettings to Task
        if (!$this->db->tableExists("xlas_ta_corr_settings") && $this->db->tableExists("xlas_et_corr_settings")) {
            $this->db->renameTable("xlas_et_corr_settings", "xlas_ta_corr_settings");
        }
    }
    public function step_8(): void
    {
        # Don't remove CorrectionSettings Inclusions - they will be converted in step 30
    }
    public function step_9(): void
    {
        # Move CorectorPrefs to Task
        if (!$this->db->tableExists("xlas_ta_corr_prefs") && $this->db->tableExists("xlas_et_corr_prefs")) {
            $this->db->renameTable("xlas_et_corr_prefs", "xlas_ta_corr_prefs");
        }
    }
    public function step_10(): void
    {
        # Remove CorectorPrefs Inclusions
        if ($this->db->tableColumnExists('xlas_ta_corr_prefs', 'include_comments')) {
            $this->db->dropTableColumn('xlas_ta_corr_prefs', 'include_comments');
        }
        if ($this->db->tableColumnExists('xlas_ta_corr_prefs', 'include_comment_ratings')) {
            $this->db->dropTableColumn('xlas_ta_corr_prefs', 'include_comment_ratings');
        }
        if ($this->db->tableColumnExists('xlas_ta_corr_prefs', 'include_comment_points')) {
            $this->db->dropTableColumn('xlas_ta_corr_prefs', 'include_comment_points');
        }
        if ($this->db->tableColumnExists('xlas_ta_corr_prefs', 'include_criteria_points')) {
            $this->db->dropTableColumn('xlas_ta_corr_prefs', 'include_criteria_points');
        }
    }
    public function step_11(): void
    {
        # Move CorrectorTaskPrefs to Task
        if (!$this->db->tableExists("xlas_ta_corr_ta_prefs") && $this->db->tableExists("xlas_et_corr_ta_prefs")) {
            $this->db->renameTable("xlas_et_corr_ta_prefs", "xlas_ta_corr_ta_prefs");
        }
    }
    public function step_12(): void
    {
        # Move CorrectorTaskPrefs to Task
        if (!$this->db->tableExists("xlas_ta_rating_crit") && $this->db->tableExists("xlas_et_rating_crit")) {
            $this->db->renameTable("xlas_et_rating_crit", "xlas_ta_rating_crit");
        }
    }

    public function step_13(): void
    {
        # Move CorrectorComment to Task
        if (!$this->db->tableExists("xlas_ta_corr_comm") && $this->db->tableExists("xlas_et_corr_comm")) {
            $this->db->renameTable("xlas_et_corr_comm", "xlas_ta_corr_comm");
        }
    }
    public function step_14(): void
    {
        # Add task_id and writer_id to CorrectorComment
        $this->addTaskIdAndWriterIdFromEssayId('xlas_ta_corr_comm');
    }

    public function step_15(): void
    {
        # Move CorrectorPoints to Task
        if (!$this->db->tableExists("xlas_ta_corr_points") && $this->db->tableExists("xlas_et_corr_points")) {
            $this->db->renameTable("xlas_et_corr_points", "xlas_ta_corr_points");
        }
    }

    public function step_16(): void
    {
        # Add task_id and writer_id to CorrectorPoints
        $this->addTaskIdAndWriterIdFromEssayId('xlas_ta_corr_points');
    }

    public function step_17(): void
    {
        # Move CorrectorSummary to Task
        if (!$this->db->tableExists("xlas_ta_corr_summary") && $this->db->tableExists("xlas_et_corr_summary")) {
            $this->db->renameTable("xlas_et_corr_summary", "xlas_ta_corr_summary");
        }
    }

    public function step_18(): void
    {
        # Add task_id and writer_id to CorrectorSummary
        $this->addTaskIdAndWriterIdFromEssayId('xlas_ta_corr_summary');
    }
    public function step_19(): void
    {
        # Remove CorrectorSummary Inclusions
        if ($this->db->tableColumnExists('xlas_ta_corr_summary', 'include_comments')) {
            $this->db->dropTableColumn('xlas_ta_corr_summary', 'include_comments');
        }
        if ($this->db->tableColumnExists('xlas_ta_corr_summary', 'include_comment_ratings')) {
            $this->db->dropTableColumn('xlas_ta_corr_summary', 'include_comment_ratings');
        }
        if ($this->db->tableColumnExists('xlas_ta_corr_summary', 'include_comment_points')) {
            $this->db->dropTableColumn('xlas_ta_corr_summary', 'include_comment_points');
        }
        if ($this->db->tableColumnExists('xlas_ta_corr_summary', 'include_criteria_points')) {
            $this->db->dropTableColumn('xlas_ta_corr_summary', 'include_criteria_points');
        }
    }

    private function addTaskIdAndWriterIdFromEssayId(string $table)
    {
        if (!$this->db->tableColumnExists($table, 'task_id')) {
            $this->db->addTableColumn($table, 'task_id', [
                'notnull' => 1,
                'type' => ilDBConstants::T_INTEGER,
                'length' => 4,
                'default' => 0
            ]);
            $this->db->addIndex($table, array("task_id"), "idt");
        }

        if (!$this->db->tableColumnExists($table, 'writer_id')) {
            $this->db->addTableColumn($table, 'writer_id', [
                'notnull' => 1,
                'type' => ilDBConstants::T_INTEGER,
                'length' => 4,
                'default' => 0
            ]);
            $this->db->addIndex($table, array("writer_id"), "idw");
        }
        if ($this->db->tableColumnExists($table, 'writer_id')
            && $this->db->tableColumnExists($table, 'writer_id')
            && $this->db->tableColumnExists($table, 'essay_id')
        ) {
            $this->db->manipulate("
                UPDATE $table as target_table 
                JOIN xlas_et_essay AS essay ON target_table.essay_id = essay.id 
                SET target_table.task_id = essay.task_id, target_table.writer_id = essay.writer_id;
            ");
        }


        if ($this->db->tableColumnExists($table, 'essay_id')) {
            $this->db->dropTableColumn($table, 'essay_id');
        }
        if ($this->db->indexExistsByFields($table, ['essay_id'])) {
            $this->db->dropIndexByFields($table, ['essay_id']);
        }
    }

    public function step_20(): void
    {
        $this->ensureTableColumn('xlas_as_orga_settings', 'template', [
            'notnull' => 1,
            'type' => ilDBConstants::T_INTEGER,
            'length' => 1,
            'default' => 0,
        ]);
        $this->ensureTableColumn('xlas_as_orga_settings', 'src_template_name', [
            'notnull' => 0,
            'type' => ilDBConstants::T_TEXT,
            'length' => 255,
        ]);
        $this->ensureTable('xlas_as_dis_groups', [
            'ass_id' => ['type' => ilDBConstants::T_INTEGER, 'notnull' => 1],
            'name' => ['type' => ilDBConstants::T_TEXT, 'notnull' => 1, 'length' => 50],
        ]);
    }

    public function step_21(): void
    {
        // obsolete
    }

    public function step_22(): void
    {
        $this->ensureTableColumn('xlas_sy_config', 'hash_algo', [
            'type' => ilDBConstants::T_TEXT,
            'length' => 50,
            'default' => Config::DEFAULT_HASH_ALGO,
            'notnull' => 1,
        ]);
    }

    public function step_23(): void
    {
        // ilDBUpdateNewObjectType needs the global database
        global $DIC;
        if (!isset($DIC['ilDB'])) {
            $DIC['ilDB'] = $this->db;
        }

        require_once __DIR__ . '/../../../../../../../../../../components/ILIAS/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php';
        $type_id = ilDBUpdateNewObjectType::addNewType('xlas', 'Long Essay Assessment');
        $ops_id = ilDBUpdateNewObjectType::addCustomRBACOperation('maintain_task', 'Maintain Task Definition', 'object', 3200);
        ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
        $ops_id = ilDBUpdateNewObjectType::addCustomRBACOperation('maintain_writers', 'Maintain Writers', 'object', 3210);
        ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
        $ops_id = ilDBUpdateNewObjectType::addCustomRBACOperation('maintain_correctors', 'Maintain Correctors', 'object', 3220);
        ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
        $ops_id = ilDBUpdateNewObjectType::addCustomRBACOperation('edit_templates', 'Edit Templates', 'object', 3230);
        ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
    }

    /** Version 3 step #126 */
    public function step_24(): void
    {
        if ($this->db->tableExists('xlas_writer_comment')) {
            $this->db->dropTable('xlas_writer_comment');
        }
    }

    /** Version 3 step #127 */
    public function step_25(): void
    {
        if ($this->db->tableExists('xlas_writer_annotation')) {
            $this->db->renameTable('xlas_writer_annotation', 'xlas_ta_writer_anno');
        } else {
            $fields = [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'task_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'writer_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'resource_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'mark_key' => ['notnull' => 1, 'type' => ilDBConstants::T_TEXT, 'length' => 50],
                'mark_value' => ['type' => ilDBConstants::T_CLOB],
                'parent_number' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'start_position' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'end_position' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'comment' => ['type' => ilDBConstants::T_CLOB]
            ];
            if (!$this->db->tableExists('xlas_ta_writer_anno')) {
                $this->db->createTable('xlas_ta_writer_anno', $fields);
                $this->db->addPrimaryKey('xlas_ta_writer_anno', array( 'id' ));
                $this->db->addIndex("xlas_ta_writer_anno", array("task_id"), "i1");
                $this->db->addIndex("xlas_ta_writer_anno", array("resource_id"), "i2");

                if (! $this->db->sequenceExists('xlas_ta_writer_anno')) {
                    $this->db->createSequence('xlas_ta_writer_anno');
                }
            }
        }
    }

    /** Version 3 step #128 */
    public function step_26(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_orga_settings', 'forwarding_url')) {
            $this->db->addTableColumn('xlas_as_orga_settings', 'forwarding_url', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 250,
                'default' => null
            ]);
        }
    }

    /** Version 3 step #129 */
    public function step_27(): void
    {
        if ($this->db->tableExists('xlas_corr_snippet')) {
            $this->db->renameTable('xlas_corr_snippet', 'xlas_et_corr_snippet');
        } else {
            $fields = [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'task_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'corrector_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'key' => ['notnull' => 1, 'type' => ilDBConstants::T_TEXT, 'length' => 50],
                'purpose' => ['notnull' => 1, 'type' => ilDBConstants::T_TEXT, 'length' => 20],
                'text' => ['type' => ilDBConstants::T_CLOB]
            ];
            $this->db->createTable('xlas_et_corr_snippet', $fields);
            $this->db->addPrimaryKey('xlas_et_corr_snippet', array('id'));
            $this->db->addIndex("xlas_et_corr_snippet", array("task_id"), "i1");

            if (!$this->db->sequenceExists('xlas_et_corr_snippet')) {
                $this->db->createSequence('xlas_et_corr_snippet');
            }
        }
    }

    public function step_28(): void
    {
        # Readd if removed CorrectionSettings Inclusions
        if (!$this->db->tableColumnExists('xlas_ta_corr_settings', 'fixed_inclusions')) {
            $this->db->addTableColumn('xlas_ta_corr_settings', 'fixed_inclusions', [
                'type' => 'integer',
                'notnull' => '1',
                'default' => 0,
            ]);
        }
        if (!$this->db->tableColumnExists('xlas_ta_corr_settings', 'include_comments')) {
            $this->db->addTableColumn('xlas_ta_corr_settings', 'include_comments', [
                'type' => 'integer',
                'notnull' => '1',
                'default' => 1,
            ]);
        }
        if (!$this->db->tableColumnExists('xlas_ta_corr_settings', 'include_comment_ratings')) {
            $this->db->addTableColumn('xlas_ta_corr_settings', 'include_comment_ratings', [
                'type' => 'integer',
                'notnull' => '1',
                'default' => 1,
            ]);
        }
        if (!$this->db->tableColumnExists('xlas_ta_corr_settings', 'include_comment_points')) {
            $this->db->addTableColumn('xlas_ta_corr_settings', 'include_comment_points', [
                'type' => 'integer',
                'notnull' => '1',
                'default' => 1,
            ]);
        }
        if (!$this->db->tableColumnExists('xlas_ta_corr_settings', 'include_criteria_points')) {
            $this->db->addTableColumn('xlas_ta_corr_settings', 'include_criteria_points', [
                'type' => 'integer',
                'notnull' => '1',
                'default' => 1,
            ]);
        }
    }

    public function step_29(): void
    {
        if (!$this->db->tableExists('xlas_as_pdf_config')) {
            $fields = [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'ass_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'purpose' => ['notnull' => 1, 'type' => ilDBConstants::T_TEXT, 'length' => 20],
                'component' => ['notnull' => 1, 'type' => ilDBConstants::T_TEXT, 'length' => 50],
                'key' => ['notnull' => 1, 'type' => ilDBConstants::T_TEXT, 'length' => 50],
                'active' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'position' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
            ];

            $this->db->createTable('xlas_as_pdf_config', $fields);
            $this->db->addPrimaryKey('xlas_as_pdf_config', array('id'));
            $this->db->addIndex("xlas_as_pdf_config", array("ass_id"), "i1");
            $this->db->createSequence('xlas_as_pdf_config');
        }
    }

    public function step_30(): void
    {
        # Existence of fixed_inclusions is indicator that inclusions need to be converted
        if ($this->db->tableColumnExists('xlas_ta_corr_settings', 'fixed_inclusions')) {

            // enable all inclusions of they were not fixed
            $this->db->manipulate("
                UPDATE xlas_ta_corr_settings
                SET include_comments = 1, include_comment_ratings = 1, include_comment_points = 1, include_criteria_points = 1
                WHERE fixed_inclusions = 0;
            ");

            // merge INCLUDE_INFO and INCLUDE_RELEVANT values
            $this->db->manipulate("UPDATE xlas_ta_corr_settings SET include_comments = 1 WHERE include_comments = 2");
            $this->db->manipulate("UPDATE xlas_ta_corr_settings SET include_comment_ratings = 1 WHERE include_comment_ratings = 2");

            // merge settings for comment_points and criteria_points
            $this->db->manipulate("UPDATE xlas_ta_corr_settings SET include_comment_points = 1 WHERE include_comment_points = 2 OR include_criteria_points > 0");

            // use better column names
            $this->db->renameTableColumn("xlas_ta_corr_settings", "include_comments", "enable_comments");
            $this->db->renameTableColumn("xlas_ta_corr_settings", "include_comment_ratings", "enable_comment_ratings");
            $this->db->renameTableColumn("xlas_ta_corr_settings", "include_comment_points", "enable_partial_points");

            // cleanup obselete tables
            $this->db->dropTableColumn("xlas_ta_corr_settings", "include_criteria_points");
            $this->db->dropTableColumn("xlas_ta_corr_settings", "fixed_inclusions");
        }
    }

    public function step_31(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_pdf_settings', 'format')) {
            $this->db->addTableColumn('xlas_as_pdf_settings', 'format', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 25,
                'notnull' => true,
                'default' => 'edutiek'
            ]);
        }

        if (!$this->db->tableColumnExists('xlas_as_pdf_settings', 'feedback_mode')) {
            $this->db->addTableColumn('xlas_as_pdf_settings', 'feedback_mode', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 25,
                'notnull' => true,
                'default' => 'side-by-side'
            ]);
        }
    }

    public function step_32(): void
    {
        if ($this->db->tableExists('xlas_et_corr_snippet')) {
            $this->db->renameTable('xlas_et_corr_snippet', 'xlas_ta_corr_snippet');
            $this->db->renameTableColumn('xlas_ta_corr_snippet', 'task_id', 'ass_id');
        }

        if (!$this->db->tableColumnExists('xlas_ta_corr_snippet', 'title')) {
            $this->db->addTableColumn('xlas_ta_corr_snippet', 'title', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 50,
                'default' => null
            ]);
        }
    }

    public function step_33(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_summary', 'summary_pdf')) {
            $this->db->addTableColumn('xlas_ta_corr_summary', 'summary_pdf', [
                'type' => ilDBConstants::T_TEXT,
                'default' => null
            ]);
        }

        if (!$this->db->tableColumnExists('xlas_ta_corr_settings', 'enable_summary_pdf')) {
            $this->db->addTableColumn('xlas_ta_corr_settings', 'enable_summary_pdf', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => 0
            ]);
        }

        if (!$this->db->tableColumnExists('xlas_ta_corr_settings', 'summary_pdf_advice')) {
            $this->db->addTableColumn('xlas_ta_corr_settings', 'summary_pdf_advice', [
                'type' => ilDBConstants::T_TEXT,
                'default' => null
            ]);
        }
    }

    public function step_34(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_writer', 'correction_status')) {
            $this->db->addTableColumn('xlas_as_writer', 'correction_status', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 25,
                'notnull' => true,
                'default' => 'open'
            ]);
        }
    }

    public function step_35(): void
    {
        if ($this->db->tableColumnExists('xlas_as_writer', 'correction_finalized')) {
            $this->db->manipulate("UPDATE xlas_as_writer SET correction_status = 'finalized' WHERE correction_finalized IS NOT NULL");
            $this->db->renameTableColumn('xlas_as_writer', 'correction_finalized', 'correction_status_changed');
        }
        if ($this->db->tableColumnExists('xlas_as_writer', 'correction_finalized_by')) {
            $this->db->renameTableColumn('xlas_as_writer', 'correction_finalized_by', 'correction_status_changed_by');
        }
    }

    public function step_36(): void
    {
        if ($this->db->tableColumnExists('xlas_as_writer', 'stitch_needed')) {
            $this->db->manipulate("UPDATE xlas_as_writer SET correction_status = 'stitch' WHERE stitch_needed > 0");
            $this->db->dropTableColumn('xlas_as_writer', 'stitch_needed');
        }
    }

    public function step_37(): void
    {
        if ($this->db->tableColumnExists('xlas_as_corr_settings', 'stitch_when_distance')) {
            $this->db->renameTableColumn('xlas_as_corr_settings', 'stitch_when_distance', 'procedure_when_distance');
        }
        if ($this->db->tableColumnExists('xlas_as_corr_settings', 'stitch_when_decimals')) {
            $this->db->renameTableColumn('xlas_as_corr_settings', 'stitch_when_decimals', 'procedure_when_decimals');
        }
    }

    public function step_38(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'procedure')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'procedure', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 25,
                'notnull' => true,
                'default' => 'none'
            ]);
        }

        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'stitch_after_procedure')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'stitch_after_procedure', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => 1
            ]);
        }

        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'approximation')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'approximation', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 25,
                'notnull' => true,
                'default' => 'decide'
            ]);
        }
    }
    public function step_39(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'revision_between')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'revision_between', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => 0
            ]);
        }
    }

    public function step_40(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'no_manual_decimals')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'no_manual_decimals', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => 0
            ]);
        }
    }

    public function step_41(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'wait_for_first')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'wait_for_first', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => 0
            ]);
        }
    }

    public function step_42(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'undo_authorization')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'undo_authorization', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => 1
            ]);
        }
    }

    public function step_43(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'instant_status')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'instant_status', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => 1
            ]);
        }
    }

    public function step_44(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_grade_level', 'statement')) {
            $this->db->addTableColumn('xlas_as_grade_level', 'statement', [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
            ]);
        }
    }

    public function step_45(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_summary', 'pre_graded')) {
            $this->db->addTableColumn('xlas_ta_corr_summary', 'pre_graded', [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false,
            ]);
        }
    }

    public function step_46(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_summary', 'revised')) {
            $this->db->addTableColumn('xlas_ta_corr_summary', 'revised', [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false,
            ]);
        }
        if (!$this->db->tableColumnExists('xlas_ta_corr_summary', 'revision_text')) {
            $this->db->addTableColumn('xlas_ta_corr_summary', 'revision_text', [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
            ]);
        }
        if (!$this->db->tableColumnExists('xlas_ta_corr_summary', 'revision_points')) {
            $this->db->addTableColumn('xlas_ta_corr_summary', 'revision_points', [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => false,
            ]);
        }
    }

    public function step_47(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_prefs', 'filter_grading_status')) {
            $this->db->addTableColumn('xlas_ta_corr_prefs', 'filter_grading_status', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 250,
                'notnull' => false,
            ]);
        }

        if (!$this->db->tableColumnExists('xlas_ta_corr_prefs', 'filter_assigned_position')) {
            $this->db->addTableColumn('xlas_ta_corr_prefs', 'filter_assigned_position', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
            ]);
        }
    }

    public function step_48(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'max_points')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'max_points', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false
            ]);
        }

        if (!$this->db->tableColumnExists('xlas_ta_settings', 'weight')) {
            $this->db->addTableColumn('xlas_ta_settings', 'weight', [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => false,
                'default' => 1
            ]);
        }

        // move max_points back to assessment
        // at this step only one essay task exists in an update from ILIAS 9
        if ($this->db->tableExists('xlas_et_task_settings')) {
            $query = "SELECT ass_id, max_points FROM xlas_et_task_settings";
            $result = $this->db->query($query);
            while ($row = $this->db->fetchAssoc($result)) {
                $this->db->manipulateF(
                    "UPDATE xlas_as_corr_settings SET max_points = %s WHERE ass_id = %s",
                    [ilDBConstants::T_INTEGER, ilDBConstants::T_INTEGER],
                    [$row['max_points'], $row['ass_id']]
                );
            }

            $this->db->dropTable('xlas_et_task_settings');
        }
    }

    public function step_49(): void
    {
        if ($this->db->tableColumnExists('xlas_as_pdf_settings', 'add_header')) {
            $this->db->dropTableColumn('xlas_as_pdf_settings', 'add_header');
        }
        if ($this->db->tableColumnExists('xlas_as_pdf_settings', 'add_footer')) {
            $this->db->dropTableColumn('xlas_as_pdf_settings', 'add_footer');
        }
        if ($this->db->tableColumnExists('xlas_as_pdf_settings', 'top_margin')) {
            $this->db->dropTableColumn('xlas_as_pdf_settings', 'top_margin');
        }
        if ($this->db->tableColumnExists('xlas_as_pdf_settings', 'bottom_margin')) {
            $this->db->dropTableColumn('xlas_as_pdf_settings', 'bottom_margin');
        }
        if ($this->db->tableColumnExists('xlas_as_pdf_settings', 'left_margin')) {
            $this->db->dropTableColumn('xlas_as_pdf_settings', 'left_margin');
        }
        if ($this->db->tableColumnExists('xlas_as_pdf_settings', 'right_margin')) {
            $this->db->dropTableColumn('xlas_as_pdf_settings', 'right_margin');
        }
    }

    public function step_50(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_summary', 'require_other_revision')) {
            $this->db->addTableColumn('xlas_ta_corr_summary', 'require_other_revision', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'default' => 0
            ]);
        }
    }

    public function step_51(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_comm', 'key')) {
            $this->db->addTableColumn('xlas_ta_corr_comm', 'key', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 50,
                'notnull' => false,
                'default' => ''
            ]);
            $this->db->manipulate("UPDATE xlas_ta_corr_comm SET `key` = CONCAT('C', '_', corrector_id, '_', RAND())");
        }
    }

    public function step_52(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_points', 'key')) {
            $this->db->addTableColumn('xlas_ta_corr_points', 'key', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 50,
                'notnull' => false,
                'default' => ''
            ]);
            $this->db->manipulate("UPDATE xlas_ta_corr_points SET `key` = CONCAT('P', '_', corrector_id, '_', RAND())");
        }
    }

    public function step_53(): void
    {
        if (!$this->db->indexExistsByFields('xlas_ta_corr_summary', ['summary_pdf'])) {
            $this->db->addIndex('xlas_ta_corr_summary', ['summary_pdf'], 'idp');
        }
    }

    public function step_54(): void
    {
        // ilDBUpdateNewObjectType needs the global database
        global $DIC;
        if (!isset($DIC['ilDB'])) {
            $DIC['ilDB'] = $this->db;
        }

        require_once __DIR__ . '/../../../../../../../../../../components/ILIAS/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php';
        $type_id = \ilDBUpdateNewObjectType::getObjectTypeId('xlas');
        $ops_id = \ilDBUpdateNewObjectType::addCustomRBACOperation('proctor_writer', 'Proctor Assessment', 'object', 3240);
        \ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
    }

    public function step_55(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'pseudonymization')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'pseudonymization', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 20,
                'notnull' => true,
                'default' => 'writer_id'
            ]);
        }
    }

    public function step_56(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_writer', 'finalized_from_status')) {
            $this->db->addTableColumn('xlas_as_writer', 'finalized_from_status', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 20,
                'notnull' => false
            ]);
        }
    }

    public function step_57(): void
    {
        if (!$this->db->tableColumnExists('xlas_et_essay', 'pdf_from_written_text')) {
            $this->db->addTableColumn('xlas_et_essay', 'pdf_from_written_text', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => 0
            ]);
        }
    }

    public function step_58(): void
    {
        if (!$this->db->tableColumnExists('xlas_et_essay', 'word_count')) {
            $this->db->addTableColumn('xlas_et_essay', 'word_count', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => 0
            ]);
        }
    }

    public function step_59(): void
    {
        /** possible candidate for a migration script */
        if ($this->db->tableColumnExists('xlas_et_essay', 'word_count')) {
            $counts = [];
            $query = $this->db->query('SELECT id, written_text FROM xlas_et_essay');

            while ($row = $this->db->fetchAssoc($query)) {
                if (!empty($row['written_text'])) {
                    $counts[$row['id']] = str_word_count($row['written_text']);
                }
            }

            $this->db->createTable('xlas_temp_essay', [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'word_count' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER]
            ], true);
            $this->db->addPrimaryKey('xlas_temp_essay', ['id']);

            $iqa = fn($a) => array_map(fn($b) => $this->db->quote($b, ilDBConstants::T_INTEGER), $a);
            $values = array_map(fn($k, $v) => "($k, $v)", $iqa(array_keys($counts)), $iqa($counts));
            if (!empty($values)) {
                $this->db->manipulate("INSERT INTO xlas_temp_essay (id, word_count) VALUES " . implode(',', $values));
            }
            $this->db->manipulate("UPDATE xlas_et_essay e JOIN xlas_temp_essay t ON e.id = t.id SET e.word_count = t.word_count");
            $this->db->dropTable('xlas_temp_essay');
        }
    }

    public function step_60(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_writer', 'writing_status')) {
            $this->db->addTableColumn('xlas_as_writer', 'writing_status', [
                'notnull' => 1,
                'type' => ilDBConstants::T_INTEGER,
                'length' => 1,
                'default' => 0
            ]);
            $this->db->addIndex('xlas_as_writer', ['writing_status'], 'iws');
        }
    }

    public function step_61(): void
    {
        $update = "UPDATE xlas_as_writer
                    SET writing_status = CASE
                     WHEN writing_excluded IS NOT NULL THEN 1  -- EXCLUDED
                     WHEN writing_authorized IS NOT NULL THEN 2 -- AUTHORIZED
                     WHEN working_start IS NOT NULL THEN 3     -- STARTED
                     ELSE 4                                    -- NOT_STARTED
                END";

        $this->db->manipulate($update);
    }

    public function step_62(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_writer', 'combined_status')) {
            $this->db->addTableColumn('xlas_as_writer', 'combined_status', [
                'notnull' => 1,
                'type' => ilDBConstants::T_INTEGER,
                'length' => 1,
                'default' => 0
            ]);
            $this->db->addIndex('xlas_as_writer', ['combined_status'], 'ics');
        }
    }

    public function step_63(): void
    {
        $update = "UPDATE xlas_as_writer
                    SET combined_status = CASE
                        -- If NOT AUTHORIZED, combined_status = writing_status
                        WHEN writing_status <> 2 THEN writing_status
                        -- writing_status = AUTHORIZED -> depends on correction_status
                        WHEN correction_status = 'open' THEN 5           -- OPEN
                        WHEN correction_status = 'stitch' THEN 6         -- STITCH_NEEDED
                        WHEN correction_status = 'finalized' THEN 7      -- FINALIZED
                        WHEN correction_status = 'approximation' THEN 8  -- APPROXIMATION
                        WHEN correction_status = 'consulting' THEN 9     -- CONSULTING                        
                        ELSE 2                                           -- WRITING_AUTHORIZED as fallback
                    END";

        $this->db->manipulate($update);

    }

    public function step_64(): void
    {
        if (!$this->db->tableExists('xlas_ta_corr_template')) {
            $fields = [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'task_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'corrector_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'shared' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'content' => ['type' => ilDBConstants::T_TEXT]
            ];
            $this->db->createTable('xlas_ta_corr_template', $fields);
            $this->db->addPrimaryKey('xlas_ta_corr_template', ['id']);
            $this->db->addIndex("xlas_ta_corr_template", ["task_id"], "i1");
            $this->db->addIndex("xlas_ta_corr_template", ["corrector_id"], "i2");

            if (!$this->db->sequenceExists('xlas_ta_corr_template')) {
                $this->db->createSequence('xlas_ta_corr_template');
            }
        }
    }

    public function step_65(): void
    {
        $this->db->modifyTableColumn('xlas_sy_config', 'primary_color', ['type' => ilDBConstants::T_TEXT, 'length' => 10]);
        $this->db->modifyTableColumn('xlas_sy_config', 'primary_text_color', ['type' => ilDBConstants::T_TEXT, 'length' => 10]);
        $this->db->addTableColumn('xlas_sy_config', 'corrector1_color', ['type' => ilDBConstants::T_TEXT, 'length' => 10]);
        $this->db->addTableColumn('xlas_sy_config', 'corrector2_color', ['type' => ilDBConstants::T_TEXT, 'length' => 10]);
        $this->db->addTableColumn('xlas_sy_config', 'corrector3_color', ['type' => ilDBConstants::T_TEXT, 'length' => 10]);
    }

    public function step_66(): void
    {
        $this->db->modifyTableColumn('xlas_sy_config', 'path_to_ghostscript', ['type' => ilDBConstants::T_TEXT, 'length' => 250]);
        $this->db->addTableColumn('xlas_sy_config', 'path_to_pdftk', ['type' => ilDBConstants::T_TEXT, 'length' => 150]);
    }

    public function step_67(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'download_writing')) {
            $this->db->addTableColumn(
                'xlas_as_corr_settings',
                'download_writing',
                ['type' => ilDBConstants::T_INTEGER, 'notnull' => 1, 'default' => 1]
            );
        }
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'download_correction')) {
            $this->db->addTableColumn(
                'xlas_as_corr_settings',
                'download_correction',
                ['type' => ilDBConstants::T_INTEGER, 'notnull' => 1, 'default' => 1]
            );
        }
    }

    public function step_68(): void
    {
        if (!$this->db->tableExists('xlas_as_exp_settings')) {
            $fields = [
                'ass_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'result_export_format' => ['type' => ilDBConstants::T_TEXT, 'length' => 20, 'default' => 'edutiek']
            ];
            $this->db->createTable('xlas_as_exp_settings', $fields);
            $this->db->addPrimaryKey('xlas_as_exp_settings', ['ass_id']);
        }
    }

    public function step_69(): void
    {
        if (!$this->db->tableExists('xlas_as_exp_file')) {
            $fields = [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'ass_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'file_id' => ['notnull' => 1, 'type' => ilDBConstants::T_TEXT, 'length' => 64],
                'type' => ['type' => ilDBConstants::T_TEXT, 'length' => 50, 'notnull' => 1]
            ];
            $this->db->createTable('xlas_as_exp_file', $fields);
            $this->db->addPrimaryKey('xlas_as_exp_file', ['id']);
            $this->db->addIndex('xlas_as_exp_file', ['ass_id'], 'i1');

            if (!$this->db->sequenceExists('xlas_as_exp_file')) {
                $this->db->createSequence('xlas_as_exp_file');
            }
        }
    }


    public function step_70(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_ta_prefs', 'id')) {
            // add new primary column and create sequence table
            $this->db->addTableColumn(
                'xlas_ta_corr_ta_prefs',
                'id',
                ['type' => ilDBConstants::T_INTEGER, 'notnull' => 1, 'default' => 0]
            );
            $this->db->createSequence('xlas_ta_corr_ta_prefs', 1);

            // remove posssible duplicates
            $result = $this->db->query("SELECT corrector_id, task_id, MAX(criterion_copy) as criterion_copy_max, COUNT(criterion_copy)  as dublicate_count FROM xlas_ta_corr_ta_prefs WHERE id = 0 GROUP BY corrector_id, task_id HAVING dublicate_count > 1");
            while ($row = $this->db->fetchAssoc($result)) {
                $this->db->manipulateF(
                    "DELETE FROM xlas_ta_corr_ta_prefs WHERE corrector_id = %s AND task_id = %s",
                    [ilDBConstants::T_INTEGER, ilDBConstants::T_INTEGER],
                    [$row['corrector_id'], $row['task_id']]
                );
                $id = $this->db->nextId('xlas_ta_corr_ta_prefs');
                $this->db->insert('xlas_ta_corr_ta_prefs', [
                        'id' => [ilDBConstants::T_INTEGER, $id],
                        'corrector_id' => [ilDBConstants::T_INTEGER, $row['corrector_id']],
                        'task_id' => [ilDBConstants::T_INTEGER, $row['task_id']],
                        'criterion_copy' => [ilDBConstants::T_INTEGER, $row['criterion_copy_max']]
                    ]);
            }

            // add missing primary ids
            $result = $this->db->query("SELECT corrector_id, task_id FROM xlas_ta_corr_ta_prefs WHERE id = 0");
            while ($row = $this->db->fetchAssoc($result)) {
                $id = $this->db->nextId('xlas_ta_corr_ta_prefs');
                $this->db->manipulateF(
                    "UPDATE xlas_ta_corr_ta_prefs SET id = %s WHERE corrector_id = %s AND task_id = %s AND id = 0",
                    [ilDBConstants::T_INTEGER, ilDBConstants::T_INTEGER, ilDBConstants::T_INTEGER],
                    [$id, $row['corrector_id'], $row['task_id']]
                );
            }

            // declare primary and set to not null
            $this->db->addPrimaryKey('xlas_ta_corr_ta_prefs', ['id']);
            $this->db->modifyTableColumn('xlas_ta_corr_ta_prefs', 'id', ['type' => ilDBConstants::T_INTEGER, 'notnull' => 1]);
        }
    }

    public function step_71(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_writer', 'imported_status')) {
            $this->db->addTableColumn('xlas_as_writer', 'imported_status', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 50,
            ]);
        }
    }

    public function step_72(): void
    {
        if (!$this->db->tableColumnExists('xlas_et_essay', 'pdf_hash')) {
            $this->db->addTableColumn('xlas_et_essay', 'pdf_hash', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 250,
            ]);
        }
    }

    /**
     * Create real corrections for stitch decisions in version 3
     */
    public function step_73(): void
    {
        if ($this->db->tableColumnExists('xlas_as_writer', 'stitch_comment')) {
            $done = [];
            $correctors = [];

            $query = "
                SELECT w.id, w.ass_id, t.task_id as task_id, 
                w.final_points, w.stitch_comment, w.correction_status_changed, w.correction_status_changed_by
                FROM xlas_as_writer w
                JOIN xlas_ta_settings t ON t.ass_id = w.ass_id
                WHERE stitch_comment IS NOT NULL AND final_points IS NOT NULL
            ";

            $result = $this->db->query($query);

            foreach ($this->db->fetchAll($result) as $writer) {
                $writer_id = $writer['id'];
                $ass_id = $writer['ass_id'];
                $task_id = $writer['task_id'];
                $points = $writer['final_points'];
                $comment = $writer['stitch_comment'];
                $changed = $writer['correction_status_changed'];
                $changed_by = $writer['correction_status_changed_by'];

                if (isset($done[$writer_id])) {
                    continue;
                }

                $corrector_id = $correctors[$ass_id][$changed_by] ?? null;
                if ($corrector_id === null) {
                    $corrector_id = $this->db->nextId('xlas_as_corrector');
                    $this->db->insert('xlas_as_corrector', [
                        'id' => [ilDBConstants::T_INTEGER, $corrector_id],
                        'ass_id' => [ilDBConstants::T_INTEGER, $writer['ass_id']],
                        'user_id' => [ilDBConstants::T_INTEGER,$writer['correction_status_changed_by']],
                    ]);
                    $correctors[$ass_id][$changed_by] = $corrector_id;
                }

                $assignment_id = $this->db->nextId('xlas_ta_corr_assign');
                $this->db->insert('xlas_ta_corr_assign', [
                    'id' => [ilDBConstants::T_INTEGER, $assignment_id],
                    'task_id' => [ilDBConstants::T_INTEGER, $task_id],
                    'writer_id' => [ilDBConstants::T_INTEGER, $writer_id],
                    'corrector_id' => [ilDBConstants::T_INTEGER, $corrector_id],
                    'position' => [ilDBConstants::T_INTEGER, 2],
                ]);

                $summary_id = $this->db->nextId('xlas_ta_corr_summary');
                $this->db->insert('xlas_ta_corr_summary', [
                    'id' => [ilDBConstants::T_INTEGER, $summary_id],
                    'task_id' => [ilDBConstants::T_INTEGER, $task_id],
                    'writer_id' => [ilDBConstants::T_INTEGER, $writer_id],
                    'corrector_id' => [ilDBConstants::T_INTEGER, $corrector_id],
                    'points' => [ilDBConstants::T_INTEGER, $points],
                    'summary_text' => [ilDBConstants::T_TEXT, $comment],
                    'last_change' => [ilDBConstants::T_DATETIME, $changed],
                    'corection_authorized' => [ilDBConstants::T_DATETIME, $changed],
                    'correction_authorized_by' => [ilDBConstants::T_INTEGER, $changed_by],
                ]);

                $this->db->update('xlas_as_writer', [
                    'finalized_from_status' => [ilDBConstants::T_TEXT, 'stitch'],
                    'correction_status' => [ilDBConstants::T_TEXT, 'finalized'],
                    'combined_status' => [ilDBConstants::T_INTEGER, 7],
                ], [
                   'id' => [ilDBConstants::T_INTEGER, $writer_id],
                ]);

                $done[$writer_id] = true;
            }
        }

        $this->db->dropTableColumn('xlas_as_writer', 'stitch_comment');
    }

    /**
     * Exchange width and height of rectangular marks
     */
    public function step_74(): void
    {
        $query = "
            UPDATE xlas_ta_corr_comm 
            SET marks = REPLACE(REPLACE(REPLACE(marks, 'width', 'temp'), 'height', 'width'), 'temp', 'height')
            WHERE marks IS NOT NULL AND marks <> '[]'
        ";

        $this->db->manipulate($query);
    }

    public function step_75(): void
    {
        $query = "UPDATE xlas_ta_resource SET `type` = 'instructions' WHERE `type` = 'instruct'";
        $this->db->manipulate($query);
    }

    public function step_76(): void
    {
        if (!$this->db->tableExists('xlas_as_noti_settings')) {
            $fields = [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'ass_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'type' => ['type' => ilDBConstants::T_TEXT, 'length' => 50, 'notnull' => 1],
                'active' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'subject' => ['type' => ilDBConstants::T_TEXT, 'length' => 250, 'notnull' => 1],
                'body' => ['type' => ilDBConstants::T_CLOB],
            ];
            $this->db->createTable('xlas_as_noti_settings', $fields);
            $this->db->addPrimaryKey('xlas_as_noti_settings', ['id']);

            if (!$this->db->sequenceExists('xlas_as_noti_settings')) {
                $this->db->createSequence('xlas_as_noti_settings');
            }
        }

        if (!$this->db->tableExists('xlas_as_noti_users')) {
            $fields = [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'ass_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'user_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'type' => ['type' => ilDBConstants::T_TEXT, 'length' => 50, 'notnull' => 1],
            ];
            $this->db->createTable('xlas_as_noti_users', $fields);
            $this->db->addPrimaryKey('xlas_as_noti_users', ['id']);
            $this->db->addIndex('xlas_as_noti_users', ['user_id'], 'i1');

            if (!$this->db->sequenceExists('xlas_as_noti_users')) {
                $this->db->createSequence('xlas_as_noti_users');
            }
        }

        if (!$this->db->tableExists('xlas_as_noti_queue')) {
            $fields = [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'ass_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'user_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'type' => ['type' => ilDBConstants::T_TEXT, 'length' => 50, 'notnull' => 1],
                'added' => ['type' => ilDBConstants::T_TIMESTAMP, 'notnull' => 1]
            ];
            $this->db->createTable('xlas_as_noti_queue', $fields);
            $this->db->addPrimaryKey('xlas_as_noti_queue', ['id']);
            $this->db->addIndex('xlas_as_noti_queue', ['user_id'], 'i1');
            $this->db->addIndex('xlas_as_noti_queue', ['added'], 'i2');

            if (!$this->db->sequenceExists('xlas_as_noti_queue')) {
                $this->db->createSequence('xlas_as_noti_queue');
            }
        }
    }

    public function step_77(): void
    {
        if ($this->db->tableExists('xlas_et_essay_import')) {
            $this->db->dropTable('xlas_et_essay_import');
        }
    }

    public function step_78(): void
    {
        if (!$this->db->indexExistsByFields('xlas_as_alert', ['ass_id'])) {
            $this->db->addIndex('xlas_as_alert', ['ass_id'], 'i2');
        }
        if (!$this->db->indexExistsByFields('xlas_as_corrector', ['ass_id'])) {
            $this->db->addIndex('xlas_as_corrector', ['ass_id'], 'i2');
        }
        if (!$this->db->indexExistsByFields('xlas_as_dis_groups', ['ass_id'])) {
            $this->db->addIndex('xlas_as_dis_groups', ['ass_id'], 'i1');
        }
        if (!$this->db->indexExistsByFields('xlas_as_grade_level', ['ass_id'])) {
            $this->db->addIndex('xlas_as_grade_level', ['ass_id'], 'i1');
        }
        if (!$this->db->indexExistsByFields('xlas_as_location', ['ass_id'])) {
            $this->db->addIndex('xlas_as_location', ['ass_id'], 'i1');
        }
        if (!$this->db->indexExistsByFields('xlas_as_log_entry', ['ass_id'])) {
            $this->db->addIndex('xlas_as_log_entry', ['ass_id'], 'i1');
        }
        if ($this->db->indexExistsByFields('xlas_as_noti_queue', ['added'])) {
            $this->db->dropIndexByFields('xlas_as_noti_queue', ['added']);
        }
        if (!$this->db->indexExistsByFields('xlas_as_noti_queue', ['ass_id'])) {
            $this->db->addIndex('xlas_as_noti_queue', ['ass_id'], 'i2');
        }
        if (!$this->db->indexExistsByFields('xlas_as_noti_queue', ['type'])) {
            $this->db->addIndex('xlas_as_noti_queue', ['type'], 'i3');
        }
        if (!$this->db->indexExistsByFields('xlas_as_noti_settings', ['ass_id'])) {
            $this->db->addIndex('xlas_as_noti_settings', ['ass_id'], 'i1');
        }
        if (!$this->db->indexExistsByFields('xlas_as_noti_users', ['ass_id'])) {
            $this->db->addIndex('xlas_as_noti_users', ['ass_id'], 'i2');
        }
        if (!$this->db->indexExistsByFields('xlas_as_token', ['ass_id'])) {
            $this->db->addIndex('xlas_as_token', ['ass_id'], 'i3');
        }
        if (!$this->db->indexExistsByFields('xlas_as_writer', ['ass_id'])) {
            $this->db->addIndex('xlas_as_writer', ['ass_id'], 'i3');
        }
        if (!$this->db->indexExistsByFields('xlas_ta_corr_comm', ['corrector_id'])) {
            $this->db->addIndex('xlas_ta_corr_comm', ['corrector_id'], 'idc');
        }
        if (!$this->db->indexExistsByFields('xlas_ta_corr_points', ['criterion_id'])) {
            $this->db->addIndex('xlas_ta_corr_points', ['criterion_id'], 'i2');
        }
        if (!$this->db->indexExistsByFields('xlas_ta_corr_snippet', ['corrector_id'])) {
            $this->db->addIndex('xlas_ta_corr_snippet', ['corrector_id'], 'i2');
        }
        if (!$this->db->indexExistsByFields('xlas_ta_corr_ta_prefs', ['task_id'])) {
            $this->db->addIndex('xlas_ta_corr_ta_prefs', ['task_id'], 'i1');
        }
        if (!$this->db->indexExistsByFields('xlas_ta_corr_ta_prefs', ['corrector_id'])) {
            $this->db->addIndex('xlas_ta_corr_ta_prefs', ['corrector_id'], 'i2');
        }
        if (!$this->db->indexExistsByFields('xlas_ta_rating_crit', ['corrector_id'])) {
            $this->db->addIndex('xlas_ta_rating_crit', ['corrector_id'], 'i2');
        }
        if (!$this->db->indexExistsByFields('xlas_ta_settings', ['ass_id'])) {
            $this->db->addIndex('xlas_ta_settings', ['ass_id'], 'i1');
        }
        if (!$this->db->indexExistsByFields('xlas_ta_writer_anno', ['writer_id'])) {
            $this->db->addIndex('xlas_ta_writer_anno', ['writer_id'], 'i3');
        }
    }

    public function step_79(): void
    {
        $query = "
            SELECT s.ass_id, s.review_notif_text AS txt 
            FROM xlas_as_orga_settings s 
            WHERE s.review_notification > 0
        ";
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $id = $this->db->nextId('xlas_as_noti_settings');
            $subject = "Die Einsichtnahme für die Abgaben „[assessment_title]“ hat begonnen";
            $body = "Hallo [fullname],\n\n"
                . "Ihre Abgabe wurde korrigiert und steht nun zur Einsichtnahme bereit.\n\n"
                . (empty($row['txt']) ? "" : $row['txt'] . "\n\n")
                . "Wählen Sie den folgenden Link, um auf den Inhalt der Abgabe zuzugreifen:\n"
                . "[assessment_link]";

            $this->db->insert('xlas_as_noti_settings', [
                'id' => [ilDBConstants::T_INTEGER, $id],
                'ass_id' => [ilDBConstants::T_INTEGER, $row['ass_id']],
                'type' => [ilDBConstants::T_TEXT, 'writer_correction_finalized'],
                'active' => [ilDBConstants::T_INTEGER, 1],
                'subject' => [ilDBConstants::T_TEXT, $subject],
                'body' => [ilDBConstants::T_CLOB, $body],
            ]);
        }
    }

    public function step_80(): void
    {
        if ($this->db->tableColumnExists('xlas_as_orga_settings', 'review_notification')) {
            $this->db->dropTableColumn('xlas_as_orga_settings', 'review_notification');
        }
        if ($this->db->tableColumnExists('xlas_as_orga_settings', 'review_notif_text')) {
            $this->db->dropTableColumn('xlas_as_orga_settings', 'review_notif_text');
        }
    }

    /**
     * Add missing write settings, keeping defaults from version 3
     */
    public function step_81(): void
    {
        $query = "SELECT s.ass_id FROM xlas_as_orga_settings s WHERE NOT EXISTS (SELECT 1 FROM xlas_et_write_settings w WHERE w.ass_id = s.ass_id)";
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $this->db->insert('xlas_et_write_settings', [
                'ass_id' => [ilDBConstants::T_INTEGER, $row['ass_id']],
                'headline_scheme' => [ilDBConstants::T_TEXT, 'three'],
                'formatting_options' => [ilDBConstants::T_TEXT, 'medium'],
                'notice_boards' => [ilDBConstants::T_INTEGER, 0],
                'copy_allowed' => [ilDBConstants::T_INTEGER, 0],
                'add_paragraph_numbers' => [ilDBConstants::T_INTEGER, 1],
                'add_correction_margin' => [ilDBConstants::T_INTEGER, 0],
                'left_correction_margin' => [ilDBConstants::T_INTEGER, 0],
                'allow_spellcheck' => [ilDBConstants::T_INTEGER, 0],
                'writing_type' => [ilDBConstants::T_TEXT, 'essay_editor'],
            ]);
        }
    }

    /**
     * Add missing initial service version for version 10
     * Missing service version for version 3 were added in
     * @see \ILIAS\Plugin\LongEssayAssessment\Setup\DBUpdateSteps9::step_23
     */
    public function step_82(): void
    {
        $this->db->manipulate("UPDATE xlas_et_essay SET service_version = 20241213 WHERE service_version = 0");
    }

    public function step_83(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_settings', 'pdf_marking')) {
            $this->db->addTableColumn('xlas_ta_corr_settings', 'pdf_marking', [
                'type' => ilDBConstants::T_TEXT,
                'length' => 10,
                'notnull' => true,
                'default' => 'images',
            ]);
        }
    }

    public function step_84(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_corr_settings', 'undo_first_authorization')) {
            $this->db->addTableColumn('xlas_as_corr_settings', 'undo_first_authorization', [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'default' => false,
            ]);
        }
    }

    /**
     * Extend text field lengths
     * - All fields edited with TinyMCE should be LONGTEXT (T_CLOB)
     * - Marks must be LONGTEXT, because freehand drawing creates long coordinate lists
     *
     * Notes:
     * - ILIAS support only T_TEXT and T_CLOB
     * - T_TEXT without length maps to mariadb TEXT
     * - T_TEXT with length maps to mariadb VARCHAR, max length is 4000
     * - T_CLOB maps to mariadb LONGTEXT (CLOB in mariadb is ALIAS for LONGTEXT)
     */
    public function step_85(): void
    {
        $this->db->modifyTableColumn('xlas_as_corrector', 'correction_report', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_as_orga_settings', 'description', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_as_orga_settings', 'closing_message', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_et_essay', 'written_text', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_et_writer_history', 'content', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_et_writer_notice', 'note_text', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_ta_corr_comm', 'marks', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_ta_corr_summary', 'summary_text', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_ta_corr_summary', 'revision_text', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_ta_corr_template', 'content', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_ta_settings', 'instructions', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_ta_settings', 'solution', ['type' => ilDBConstants::T_CLOB, 'notnull' => false]);
    }

    /**
     * Shorten text fields lengths
     * - These fields don't have long data
     */
    public function step_86(): void
    {
        $this->db->modifyTableColumn('xlas_as_noti_settings', 'body', ['type' => ilDBConstants::T_TEXT, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_ta_corr_snippet', 'text', ['type' => ilDBConstants::T_TEXT, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_ta_corr_summary', 'summary_pdf', ['type' => ilDBConstants::T_TEXT, 'length' => 50, 'notnull' => false]);
        $this->db->modifyTableColumn('xlas_ta_writer_anno', 'comment', ['type' => ilDBConstants::T_TEXT, 'notnull' => false]);
    }

    /**
     * PDF file with correction marks from one or all correctors
     */
    public function step_87(): void
    {
        if (!$this->db->tableExists('xlas_et_marked_pdf')) {
            $fields = [
                'id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'task_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'writer_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'corrector_id' => ['notnull' => 1, 'type' => ilDBConstants::T_INTEGER],
                'own_pdf' => ['notnull' => 1, 'type' => ilDBConstants::T_TEXT, 'length' => 50],
                'sum_pdf' => ['notnull' => 1, 'type' => ilDBConstants::T_TEXT, 'length' => 50],
            ];
            $this->db->createTable('xlas_et_marked_pdf', $fields);
            $this->db->addPrimaryKey('xlas_et_marked_pdf', ['id']);
            $this->db->addIndex("xlas_et_marked_pdf", ["task_id"], "i1");
            $this->db->addIndex("xlas_et_marked_pdf", ["writer_id"], "i2");
            $this->db->addIndex("xlas_et_marked_pdf", ["corrector_id"], "i3");

            if (!$this->db->sequenceExists('xlas_et_marked_pdf')) {
                $this->db->createSequence('xlas_et_marked_pdf');
            }
        }
    }

    public function step_88(): void
    {
        if ($this->db->tableColumnExists('xlas_ta_corr_snippet', 'title')) {
            $this->db->dropTableColumn('xlas_ta_corr_snippet', 'title');
        }

        if (!$this->db->tableColumnExists('xlas_ta_corr_snippet', 'shortcut')) {
            $this->db->addTableColumn('xlas_ta_corr_snippet', 'shortcut', [
                'type' => ilDBConstants::T_TEXT, 'length' => 20, 'notnull' => 0, 'default' => null]);
        }
    }

    public function step_89(): void
    {
        if (!$this->db->tableColumnExists('xlas_ta_corr_prefs', 'default_shape')) {
            $this->db->addTableColumn('xlas_ta_corr_prefs', 'default_shape', [
                'type' => ilDBConstants::T_TEXT, 'length' => 20, 'notnull' => 0, 'default' => null]);
        }

        if (!$this->db->tableColumnExists('xlas_ta_corr_prefs', 'display_labels')) {
            $this->db->addTableColumn('xlas_ta_corr_prefs', 'display_labels', [
                'type' => ilDBConstants::T_INTEGER, 'notnull' => 1, 'default' => 0]);
        }

        if (!$this->db->tableColumnExists('xlas_ta_corr_prefs', 'select_words')) {
            $this->db->addTableColumn('xlas_ta_corr_prefs', 'select_words', [
                'type' => ilDBConstants::T_INTEGER, 'notnull' => 1, 'default' => 1]);
        }
    }
}
