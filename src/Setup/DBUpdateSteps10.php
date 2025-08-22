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

    public function step_5(): void
    {
        if (!$this->db->tableColumnExists('xlas_as_writer', 'stitch_needed')) {
            $this->db->addTableColumn('xlas_as_writer', 'stitch_needed', [
                'notnull' => '1',
                'type' => 'integer',
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
        foreach($this->db->fetchObject($query)??[] as $obj) {
            $summaries_by_task_and_writer[$obj->task_id][$obj->writer_id][] = $obj;
        }

        foreach($summaries_by_task_and_writer as $task_id => $summaries_by_writer) {
            foreach($summaries_by_writer as $writer_id => $summaries) {
                $required_correctors = (int)$summaries[0]?->required_correctors ?? 0;
                $stitch_when_distance = (bool)$summaries[0]?->stitch_when_distance??0;
                $max_auto_distance = (int)$summaries[0]?->max_auto_distance??0;
                $stitch_when_decimals = (bool)$summaries[0]?->stitch_when_decimals??0;

                if (count($summaries) < $required_correctors) {
                    continue;// not enough correctors authorized => not yet ready
                }

                $minPoints = null;
                $maxPoints = null;
                foreach ($summaries as $summary) {
                    if ($summary->correction_authorized === null) {
                        continue;// At least one correction is not authorized
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
        if(!$this->db->tableExists("xlas_ta_corr_settings") && $this->db->tableExists("xlas_et_corr_settings") )
        {
            $this->db->renameTable("xlas_et_corr_settings", "xlas_ta_corr_settings");
        }
    }
    public function step_8(): void
    {
        # Remove CorrectionSettings Inclusions
       if($this->db->tableColumnExists('xlas_ta_corr_settings', 'fixed_inclusions')) {
           $this->db->dropTableColumn('xlas_ta_corr_settings', 'fixed_inclusions');
       }
       if($this->db->tableColumnExists('xlas_ta_corr_settings', 'include_comments')) {
           $this->db->dropTableColumn('xlas_ta_corr_settings', 'include_comments');
       }
       if($this->db->tableColumnExists('xlas_ta_corr_settings', 'include_comment_ratings')) {
           $this->db->dropTableColumn('xlas_ta_corr_settings', 'include_comment_ratings');
       }
       if($this->db->tableColumnExists('xlas_ta_corr_settings', 'include_comment_points')) {
           $this->db->dropTableColumn('xlas_ta_corr_settings', 'include_comment_points');
       }
       if($this->db->tableColumnExists('xlas_ta_corr_settings', 'include_criteria_points')) {
           $this->db->dropTableColumn('xlas_ta_corr_settings', 'include_criteria_points');
       }
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
        if($this->db->tableColumnExists('xlas_ta_corr_prefs', 'include_comments')) {
            $this->db->dropTableColumn('xlas_ta_corr_prefs', 'include_comments');
        }
        if($this->db->tableColumnExists('xlas_ta_corr_prefs', 'include_comment_ratings')) {
            $this->db->dropTableColumn('xlas_ta_corr_prefs', 'include_comment_ratings');
        }
        if($this->db->tableColumnExists('xlas_ta_corr_prefs', 'include_comment_points')) {
            $this->db->dropTableColumn('xlas_ta_corr_prefs', 'include_comment_points');
        }
        if($this->db->tableColumnExists('xlas_ta_corr_prefs', 'include_criteria_points')) {
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
        if($this->db->tableColumnExists('xlas_ta_corr_summary', 'include_comments')) {
            $this->db->dropTableColumn('xlas_ta_corr_summary', 'include_comments');
        }
        if($this->db->tableColumnExists('xlas_ta_corr_summary', 'include_comment_ratings')) {
            $this->db->dropTableColumn('xlas_ta_corr_summary', 'include_comment_ratings');
        }
        if($this->db->tableColumnExists('xlas_ta_corr_summary', 'include_comment_points')) {
            $this->db->dropTableColumn('xlas_ta_corr_summary', 'include_comment_points');
        }
        if($this->db->tableColumnExists('xlas_ta_corr_summary', 'include_criteria_points')) {
            $this->db->dropTableColumn('xlas_ta_corr_summary', 'include_criteria_points');
        }
    }

    private function addTaskIdAndWriterIdFromEssayId(string $table)
    {
        if(!$this->db->tableColumnExists($table, 'task_id')) {
            $this->db->addTableColumn($table, 'task_id', [
                'notnull' => '1',
                'type' => 'integer',
                'length' => 4,
                'default' => 0
            ]);
            $this->db->addIndex($table, array("task_id"), "idt");
        }

        if(!$this->db->tableColumnExists($table, 'writer_id')) {
            $this->db->addTableColumn($table, 'writer_id', [
                'notnull' => '1',
                'type' => 'integer',
                'length' => 4,
                'default' => 0
            ]);
            $this->db->addIndex($table, array("writer_id"), "idw");
        }

        $this->db->manipulate("
            UPDATE $table as target_table 
            LEFT JOIN xlas_et_essay AS essay ON target_table.essay_id = essay.id 
            SET target_table.task_id = essay.task_id, target_table.writer_id = essay.writer_id;
        ");

        if($this->db->tableColumnExists($table, 'essay_id')) {
            $this->db->dropTableColumn($table, 'essay_id');
        }
        if($this->db->indexExistsByFields($table, ['essay_id'])) {
            $this->db->dropIndexByFields($table, ['essay_id']);
        }
    }
}
