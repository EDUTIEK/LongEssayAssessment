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

use ilDBInterface;
use ilDBConstants;

class V10Migration
{
    public function __construct(private readonly ilDBInterface $db)
    {
    }

    public function createNewTables(): void
    {
        $indices = $this->indices();

        foreach ($this->tableInfo() as $table => $field_info) {
            $fields = [];
            $primary_fields = [];
            foreach ($field_info as $field => $info) {
                // If the Type is not set, this is a new field.
                // As there is only one new field, this is hard coded (xlas_ta_corr_assign.task_id).
                if (!isset($info['Type'])) {
                    $info['Type'] = 'int(11)';
                    $info['Key'] = 'PRI';
                }
                $length = $this->dbLength($info['Type']);
                $fields[$field] = [
                    'type' => $this->dbType($info['Type']),
                    'notnull' => !($info['Null'] ?? false),
                ];
                if (isset($length)) {
                    $fields[$field]['length'] = (int) $length;
                }
                if ($info['Key'] === 'PRI') {
                    $primary_fields[] = $field;
                }
            }

            $this->db->createTable($table, $fields);
            if ($primary_fields !== []) {
                $this->db->addPrimaryKey($table, $primary_fields);
                $this->db->createSequence($table);
            }
            foreach ($indices[$table] ?? [] as $i => $index) {
                $this->db->addIndex($table, $index, 'i' . ($i + 1));
            }
        }
    }

    public function migrateTables(): void
    {
        foreach ($this->tableInfo() as $table => $fields) {
            $this->migrateTable($table, $fields);
            $this->updateSequence($table, $fields);
        }
    }

    public function removeOldTables(): void
    {
        $drop_me = ['xlas_time_extension'];
        foreach ($this->tableInfo() as $table => $fields) {
            foreach ($fields as $info) {
                if (isset($info['src_table'])) {
                    $drop_me[] = $info['src_table'];
                }
            }
        }
        $drop_me = array_unique($drop_me);
        array_map($this->db->dropTable(...), $drop_me);
    }

    /**
     * Returns the corresponding ilDBConstants::T_* value for a db type. db type as in MySQL terms.
     */
    private function dbType(string $type): string
    {
        return $this->pregMatch($type, [
            'varchar\\(.*' => ilDBConstants::T_TEXT,
            '.*text.*' => ilDBConstants::T_TEXT,
            '.*int\\(.*' => ilDBConstants::T_INTEGER,
            'datetime' => ilDBConstants::T_TIMESTAMP,
            'double' => ilDBConstants::T_FLOAT,
        ]);
    }

    /**
     * Returns the default value for a given db type. db type as in MySQL terms not one of `ilDBConstants::T_*`.
     * E.g.:
     * $this->dbDefault('tinyint(4)'); // => '0'
     */
    private function dbDefault(string $db_type): string
    {
        return $this->pregMatch($db_type, [
            '.*int\\(.*' => '0',
            'varchar\\(.*' => '',
            '.*text' => '',
        ]);
    }

    /**
     * Returns the length of a given db type, or `null` if it doesn't have any. db type as in MySQL terms not one of `ilDBConstants::T_*`.
     * The returned values are the corresponding values for a `$this->db->createTable(...)` call, which will create a field of `$type`,
     * as the length property in ILIAS is not necessarily the same as the created one on the database.
     * E.g.
     * ['type' => 'integer', 'length' => 1] will result in `tinyint(4)`.
     */
    private function dbLength(string $type): ?string
    {
        return $this->pregMatch($type, [
            '.*int\\(4\\)' => '1',
            '.*int\\(6\\)' => '2',
            '.*int\\(9\\)' => '3',
            '.*int\\(11\\)' => '4',
            '.*varchar\\(([0-9])\\)' => '\\1',
        ]);
    }

    /**
     * Migrates one (or more) old table(s) to exactly one new table.
     * $field_info contains the new fields, their old names and their src table.
     *
     * @param array<string, array{Field: string, ?Type: string, ?Null: string, ?Key: string, ?Default: ?string, ?src_table: string}> $field_info Directly corresponds to $this->tableInfo()[$name].
     */
    private function migrateTable(string $name, array $field_info): void
    {
        $fields = array_keys($field_info);
        $quote_id = $this->db->quoteIdentifier(...);
        $quote = $this->db->quote(...);

        $join = [];
        $what = [];
        foreach ($field_info as $field => $info) {
            // If 'src_table' is not set, this is a new field.
            // As there is only one new field, this is hard coded (xlas_ta_corr_assign.task_id).
            // The default value for type int(11) is used.
            if (!isset($info['src_table'])) {
                $what[] = sprintf('%s AS %s', $quote($this->dbDefault($info['Type'] ?? 'int(11)')), $quote_id($field));
            } else {
                $join[] = $info['src_table'];
                $what[] = $this->sprintfId('%s.%s AS %s', $info['src_table'], $info['Field'], $field);
            }
        }

        $join = array_unique($join);
        $main_src = $join[0];
        // Create join parts, with the corresponding join fields from $foreign_keys.
        $join = array_map(
            function (string $join_me) use ($main_src): string {
                [$root_field, $join_field] = $this->foreignKeys()[$main_src][$join_me];
                return $this->sprintfId('LEFT JOIN %s ON %s.%s = %s.%s', $join_me, $main_src, $root_field, $join_me, $join_field);
            },
            array_slice($join, 1) // Drop $main_src
        );

        $select = sprintf('SELECT %s FROM %s %s', join(', ', $what), $quote_id($main_src), join('', $join));
        $this->db->manipulate(sprintf('INSERT INTO %s (%s) %s', $quote_id($name), join(', ', array_map($quote_id, $fields)), $select));
    }

    private function updateSequence(string $table, array $field_info): void
    {
        if (!$this->db->sequenceExists($table)) {
            return;
        }
        $primary_field = key(array_filter($field_info, fn($a) => $a['Key'] ?? null === 'PRI'));
        $this->db->manipulate($this->sprintfId('INSERT INTO %s (sequence) SELECT MAX(%s) FROM %s', $table . '_seq', $primary_field, $table));
    }

    /**
     * Like sprintf but each value is quoted with $this->db->quoteIdentifier(...).
     */
    private function sprintfId(string $format, ...$args): string
    {
        return sprintf($format, ...array_map($this->db->quoteIdentifier(...), $args));
    }

    /**
     * Matches the given $match against the given cases as regular expressions, and returns the corresponding case.
     * The whole regex is matched against $match (it is surrounded with `^` and `$`) and is surrounded with slashes.
     *
     * E.g.:
     * $this->pregMatch('varchar(255)', [['varchar.*' => 'text']]); // => 'text'
     * $this->pregMatch('varchar(255)', [['varchar\\(([0-9]+)\\)' => '\\1']]); // => '255'
     */
    private function pregMatch(string $match, array $cases): ?string
    {
        $reg = fn(string $s): string => '/^' . $s . '$/';
        $ret = preg_replace(array_map($reg, array_keys($cases)), array_values($cases), $match);
        return $ret === $match ? null : $ret;
    }

    /**
     * This is required for the method `migrateTables`.
     * This method returns the information on how to join all `src_table`'s together, to create one INSERT SQL statement.
     * This is needed as some new tables are merged from multiple old ones.
     * The array is of the form:
     * ['old_main_table_name' => ['old_table_to_join' => ['old_main_field', 'old_join_field']]]
     * Which will result in a query like:
     * SELECT ... FROM old_main_table_name LEFT JOIN old_table_to_join ON old_main_table_name.old_main_field = old_table_to_join.old_join_field ...
     *
     * @return array<string, array<string, string[]>>
     */
    private function foreignKeys(): array
    {
        return [
            'xlas_writer' => ['xlas_essay' => ['id', 'writer_id']],
            'xlas_editor_settings' => ['xlas_task_settings' => ['task_id', 'task_id']],
            'xlas_object_settings' => ['xlas_task_settings' => ['obj_id', 'task_id']],
        ];
    }

    /**
     * Returns all indices the new tables should have. These directly correspond to an `$this->db->addIndex()` call:
     * ['new_table_name' => [['field_a', 'field_b'], ['field_c']]]
     * // => $this->db->addIndex('new_table_name', ['field_a', 'field_b'], 'i1');
     * // => $this->db->addIndex('new_table_name', ['field_c'], 'i2');
     *
     * This is used when the new tables are created.
     *
     * @return array<string, string[][]>
     */
    private function indices(): array
    {
        return [
            'xlas_as_writer' => [['user_id'], ['location']],
            'xlas_as_corrector' => [['user_id']],
            'xlas_ta_writer_comment' => [['task_id']],
            'xlas_et_corr_summary' => [['essay_id'], ['essay_id', 'corrector_id']],
            'xlas_et_corr_comm' => [['essay_id']],
            'xlas_as_token' => [['user_id'], ['valid_until']],
            'xlas_ta_corr_assign' => [['writer_id', 'corrector_id'], ['corrector_id', 'writer_id']],
            'xlas_et_rating_crit' => [['task_id']],
            'xlas_et_writer_history' => [['essay_id'], ['hash_before'], ['hash_after']],
            'xlas_ta_resource' => [['task_id'], ['file_id']],
            'xlas_as_alert' => [['writer_id']],
            'xlas_et_essay' => [['uuid'], ['writer_id'], ['task_id']],
            'xlas_et_essay_image' => [['essay_id']],
            'xlas_et_writer_notice' => [['essay_id']],
            'xlas_et_corr_points' => [['comment_id'], ['essay_id'], ['corrector_id']],
        ];
    }

    /**
     * Merged information from the file `Tabellen-Migration.xlsx` and the old (pre v10) database schema.
     *
     * The array is of the form:
     * ['new_table_a' => ['new_field_a' => ['Field' => 'old_field_name', 'Type' => 'varchar(255)', 'src_table' => 'old_table_name']]]
     *
     *
     * @return array<string, array<string, array{Field: string, ?Type: string, ?Null: string, ?Key: string, ?Default: ?string, ?src_table: string}>>
     */
    private function tableInfo(): array
    {
        return array(
            'xlas_as_alert' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_alert',
                        ),
                    'title' =>
                        array(
                            'Field' => 'title',
                            'Type' => 'varchar(255)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_alert',
                        ),
                    'message' =>
                        array(
                            'Field' => 'message',
                            'Type' => 'longtext',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_alert',
                        ),
                    'writer_id' =>
                        array(
                            'Field' => 'writer_id',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_alert',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_alert',
                        ),
                    'shown_from' =>
                        array(
                            'Field' => 'shown_from',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_alert',
                        ),
                    'shown_until' =>
                        array(
                            'Field' => 'shown_until',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_alert',
                        ),
                ),
            'xlas_as_corrector' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector',
                        ),
                    'user_id' =>
                        array(
                            'Field' => 'user_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector',
                        ),
                    'correction_report' =>
                        array(
                            'Field' => 'correction_report',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corrector',
                        ),
                ),
            'xlas_as_corr_settings' =>
                array(
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'Für alle Task-Typen und Tasks des Assessments gleich',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'required_correctors' =>
                        array(
                            'Field' => 'required_correctors',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'max_auto_distance' =>
                        array(
                            'Field' => 'max_auto_distance',
                            'Type' => 'double',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'mutual_visibility' =>
                        array(
                            'Field' => 'mutual_visibility',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'assign_mode' =>
                        array(
                            'Field' => 'assign_mode',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => 'random_equal',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'stitch_when_distance' =>
                        array(
                            'Field' => 'stitch_when_distance',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '1',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'stitch_when_decimals' =>
                        array(
                            'Field' => 'stitch_when_decimals',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'anonymize_correctors' =>
                        array(
                            'Field' => 'anonymize_correctors',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'reports_enabled' =>
                        array(
                            'Field' => 'reports_enabled',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'reports_available_start' =>
                        array(
                            'Field' => 'reports_available_start',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_corr_setting',
                        ),
                ),
            'xlas_as_grade_level' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_grade_level',
                        ),
                    'min_points' =>
                        array(
                            'Field' => 'min_points',
                            'Type' => 'double',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_grade_level',
                        ),
                    'grade' =>
                        array(
                            'Field' => 'grade',
                            'Type' => 'varchar(255)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_grade_level',
                        ),
                    'code' =>
                        array(
                            'Field' => 'code',
                            'Type' => 'varchar(255)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_grade_level',
                        ),
                    'passed' =>
                        array(
                            'Field' => 'passed',
                            'Type' => 'tinyint(4)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_grade_level',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'object_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_grade_level',
                        ),
                ),
            'xlas_as_location' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_location',
                        ),
                    'title' =>
                        array(
                            'Field' => 'title',
                            'Type' => 'varchar(255)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_location',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'Alle Tasks werden durch einen Teilnehmer am selben Ort geschrieben',
                            'src_table' => 'xlas_location',
                        ),
                ),
            'xlas_as_log_entry' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_log_entry',
                        ),
                    'timestamp' =>
                        array(
                            'Field' => 'timestamp',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_log_entry',
                        ),
                    'category' =>
                        array(
                            'Field' => 'category',
                            'Type' => 'varchar(255)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_log_entry',
                        ),
                    'entry' =>
                        array(
                            'Field' => 'entry',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_log_entry',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_log_entry',
                        ),
                ),
            'xlas_as_pdf_settings' =>
                array(
                    'add_header' =>
                        array(
                            'Field' => 'add_header',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '1',
                            'Extra' => '',
                            'src_table' => 'xlas_pdf_settings',
                        ),
                    'add_footer' =>
                        array(
                            'Field' => 'add_footer',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '1',
                            'Extra' => '',
                            'src_table' => 'xlas_pdf_settings',
                        ),
                    'top_margin' =>
                        array(
                            'Field' => 'top_margin',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '10',
                            'Extra' => '',
                            'src_table' => 'xlas_pdf_settings',
                        ),
                    'bottom_margin' =>
                        array(
                            'Field' => 'bottom_margin',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '10',
                            'Extra' => '',
                            'src_table' => 'xlas_pdf_settings',
                        ),
                    'left_margin' =>
                        array(
                            'Field' => 'left_margin',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '10',
                            'Extra' => '',
                            'src_table' => 'xlas_pdf_settings',
                        ),
                    'right_margin' =>
                        array(
                            'Field' => 'right_margin',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '10',
                            'Extra' => '',
                            'src_table' => 'xlas_pdf_settings',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_pdf_settings',
                        ),
                ),
            'xlas_as_orga_settings' =>
                array(
                    'online' =>
                        array(
                            'Field' => 'online',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_object_settings',
                        ),
                    'participation_type' =>
                        array(
                            'Field' => 'participation_type',
                            'Type' => 'varchar(10)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_object_settings',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'obj_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_object_settings',
                        ),
                    'description' =>
                        array(
                            'Field' => 'description',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'closing_message' =>
                        array(
                            'Field' => 'closing_message',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'writing_start' =>
                        array(
                            'Field' => 'writing_start',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'writing_end' =>
                        array(
                            'Field' => 'writing_end',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'writing_limit_minutes' =>
                        array(
                            'Field' => 'writing_limit_minutes',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'correction_start' =>
                        array(
                            'Field' => 'correction_start',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'correction_end' =>
                        array(
                            'Field' => 'correction_end',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'review_start' =>
                        array(
                            'Field' => 'review_start',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'review_end' =>
                        array(
                            'Field' => 'review_end',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'keep_available' =>
                        array(
                            'Field' => 'keep_essay_available',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'solution_available_date' =>
                        array(
                            'Field' => 'solution_available_date',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'result_available_type' =>
                        array(
                            'Field' => 'result_available_type',
                            'Type' => 'varchar(10)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => 'review',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'result_available_date' =>
                        array(
                            'Field' => 'result_available_date',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'solution_available' =>
                        array(
                            'Field' => 'solution_available',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'review_enabled' =>
                        array(
                            'Field' => 'review_enabled',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'review_notification' =>
                        array(
                            'Field' => 'review_notification',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'review_notif_text' =>
                        array(
                            'Field' => 'review_notif_text',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'statistics_available' =>
                        array(
                            'Field' => 'statistics_available',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                ),
            'xlas_as_token' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_access_token',
                        ),
                    'user_id' =>
                        array(
                            'Field' => 'user_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_access_token',
                        ),
                    'token' =>
                        array(
                            'Field' => 'token',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_access_token',
                        ),
                    'ip' =>
                        array(
                            'Field' => 'ip',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_access_token',
                        ),
                    'purpose' =>
                        array(
                            'Field' => 'purpose',
                            'Type' => 'varchar(10)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => 'data',
                            'Extra' => '',
                            'src_table' => 'xlas_access_token',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_access_token',
                        ),
                    'valid_until' =>
                        array(
                            'Field' => 'valid_until',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_access_token',
                        ),
                ),
            'xlas_as_writer' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer',
                        ),
                    'user_id' =>
                        array(
                            'Field' => 'user_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer',
                        ),
                    'pseudonym' =>
                        array(
                            'Field' => 'pseudonym',
                            'Type' => 'varchar(255)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_writer',
                        ),
                    'earliest_start' =>
                        array(
                            'Field' => 'earliest_start',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_writer',
                        ),
                    'latest_end' =>
                        array(
                            'Field' => 'latest_end',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_writer',
                        ),
                    'time_limit_minutes' =>
                        array(
                            'Field' => 'time_limit_minutes',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => ' ',
                            'src_table' => 'xlas_writer',
                        ),
                    'working_start' =>
                        array(
                            'Field' => 'working_start',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_writer',
                        ),
                    'final_points' =>
                        array(
                            'Field' => 'final_points',
                            'Type' => 'double',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'final_grade_level_id' =>
                        array(
                            'Field' => 'final_grade_level_id',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'writing_authorized' =>
                        array(
                            'Field' => 'writing_authorized',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_essay',
                        ),
                    'writing_authorized_by' =>
                        array(
                            'Field' => 'writing_authorized_by',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'correction_finalized_by' =>
                        array(
                            'Field' => 'correction_finalized_by',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'writing_excluded' =>
                        array(
                            'Field' => 'writing_excluded',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_essay',
                        ),
                    'writing_excluded_by' =>
                        array(
                            'Field' => 'writing_excluded_by',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'stitch_comment' =>
                        array(
                            'Field' => 'stitch_comment',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'location' =>
                        array(
                            'Field' => 'location',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'review_notification' =>
                        array(
                            'Field' => 'review_notification',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_essay',
                        ),
                ),
            'xlas_ta_corr_assign' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_ass',
                        ),
                    'writer_id' =>
                        array(
                            'Field' => 'writer_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_ass',
                        ),
                    'corrector_id' =>
                        array(
                            'Field' => 'corrector_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_ass',
                        ),
                    'position' =>
                        array(
                            'Field' => 'position',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_ass',
                        ),
                    'task_id' =>
                        array(
                            'Field' => 'task_id',
                            'comment' => 'aus task_id von writer',
                        ),
                ),
            'xlas_ta_resource' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_resource',
                        ),
                    'task_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_resource',
                        ),
                    'title' =>
                        array(
                            'Field' => 'title',
                            'Type' => 'varchar(255)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_resource',
                        ),
                    'description' =>
                        array(
                            'Field' => 'description',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_resource',
                        ),
                    'url' =>
                        array(
                            'Field' => 'url',
                            'Type' => 'varchar(4000)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_resource',
                        ),
                    'type' =>
                        array(
                            'Field' => 'type',
                            'Type' => 'varchar(10)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_resource',
                        ),
                    'availability' =>
                        array(
                            'Field' => 'availability',
                            'Type' => 'varchar(10)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_resource',
                        ),
                    'file_id' =>
                        array(
                            'Field' => 'file_id',
                            'Type' => 'varchar(50)',
                            'Null' => 'YES',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'ist eine resourcen-id aus ILIAS ',
                            'src_table' => 'xlas_resource',
                        ),
                ),
            'xlas_ta_settings' =>
                array(
                    'task_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'Bisher gleich, jetzt mehrere Tasks pro Assessment',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'instructions' =>
                        array(
                            'Field' => 'instructions',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                    'solution' =>
                        array(
                            'Field' => 'solution',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                ),
            'xlas_ta_writer_comment' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_comment',
                        ),
                    'task_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_comment',
                        ),
                    'writer_id' =>
                        array(
                            'Field' => 'writer_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_comment',
                        ),
                    'comment' =>
                        array(
                            'Field' => 'comment',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_comment',
                        ),
                    'parent_number' =>
                        array(
                            'Field' => 'parent_number',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_comment',
                        ),
                    'start_position' =>
                        array(
                            'Field' => 'start_position',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_comment',
                        ),
                    'end_position' =>
                        array(
                            'Field' => 'end_position',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_comment',
                        ),
                ),
            'xlas_et_corr_comm' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_comment',
                        ),
                    'essay_id' =>
                        array(
                            'Field' => 'essay_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_comment',
                        ),
                    'comment' =>
                        array(
                            'Field' => 'comment',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_comment',
                        ),
                    'start_position' =>
                        array(
                            'Field' => 'start_position',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_comment',
                        ),
                    'end_position' =>
                        array(
                            'Field' => 'end_position',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_comment',
                        ),
                    'rating' =>
                        array(
                            'Field' => 'rating',
                            'Type' => 'varchar(16)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_comment',
                        ),
                    'corrector_id' =>
                        array(
                            'Field' => 'corrector_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_comment',
                        ),
                    'parent_number' =>
                        array(
                            'Field' => 'parent_number',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_comment',
                        ),
                    'marks' =>
                        array(
                            'Field' => 'marks',
                            'Type' => 'varchar(4000)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_comment',
                        ),
                ),
            'xlas_et_corr_prefs' =>
                array(
                    'corrector_id' =>
                        array(
                            'Field' => 'corrector_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_prefs',
                        ),
                    'essay_page_zoom' =>
                        array(
                            'Field' => 'essay_page_zoom',
                            'Type' => 'double',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_prefs',
                        ),
                    'essay_text_zoom' =>
                        array(
                            'Field' => 'essay_text_zoom',
                            'Type' => 'double',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_prefs',
                        ),
                    'summary_text_zoom' =>
                        array(
                            'Field' => 'summary_text_zoom',
                            'Type' => 'double',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_prefs',
                        ),
                    'include_comments' =>
                        array(
                            'Field' => 'include_comments',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_prefs',
                        ),
                    'include_comment_ratings' =>
                        array(
                            'Field' => 'include_comment_ratings',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_prefs',
                        ),
                    'include_comment_points' =>
                        array(
                            'Field' => 'include_comment_points',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_prefs',
                        ),
                    'include_criteria_points' =>
                        array(
                            'Field' => 'include_criteria_points',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_prefs',
                        ),
                ),
            'xlas_et_corr_points' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_points',
                        ),
                    'comment_id' =>
                        array(
                            'Field' => 'comment_id',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'Falls nicht bereits umbenannt, int null default null',
                            'src_table' => 'xlas_corrector_points',
                        ),
                    'criterion_id' =>
                        array(
                            'Field' => 'criterion_id',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'int null default null',
                            'src_table' => 'xlas_corrector_points',
                        ),
                    'essay_id' =>
                        array(
                            'Field' => 'essay_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'not null',
                            'src_table' => 'xlas_corrector_points',
                        ),
                    'corrector_id' =>
                        array(
                            'Field' => 'corrector_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'not null',
                            'src_table' => 'xlas_corrector_points',
                        ),
                    'points' =>
                        array(
                            'Field' => 'points',
                            'Type' => 'double',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'double not null',
                            'src_table' => 'xlas_corrector_points',
                        ),
                ),
            'xlas_et_corr_settings' =>
                array(
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'Für alles EssayTasks des Assessments gleich',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'criteria_mode' =>
                        array(
                            'Field' => 'criteria_mode',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => 'none',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'positive_rating' =>
                        array(
                            'Field' => 'positive_rating',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => 'Exzellent',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'negative_rating' =>
                        array(
                            'Field' => 'negative_rating',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => 'Kardinal',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'fixed_inclusions' =>
                        array(
                            'Field' => 'fixed_inclusions',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'include_comments' =>
                        array(
                            'Field' => 'include_comments',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '1',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'include_comment_ratings' =>
                        array(
                            'Field' => 'include_comment_ratings',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '1',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'include_comment_points' =>
                        array(
                            'Field' => 'include_comment_points',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '1',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'include_criteria_points' =>
                        array(
                            'Field' => 'include_criteria_points',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '1',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corr_setting',
                        ),
                ),
            'xlas_et_corr_summary' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'essay_id' =>
                        array(
                            'Field' => 'essay_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'corrector_id' =>
                        array(
                            'Field' => 'corrector_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'summary_text' =>
                        array(
                            'Field' => 'summary_text',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'points' =>
                        array(
                            'Field' => 'points',
                            'Type' => 'double',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'last_change' =>
                        array(
                            'Field' => 'last_change',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'include_comments' =>
                        array(
                            'Field' => 'include_comments',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'include_comment_ratings' =>
                        array(
                            'Field' => 'include_comment_ratings',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'include_comment_points' =>
                        array(
                            'Field' => 'include_comment_points',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'include_criteria_points' =>
                        array(
                            'Field' => 'include_criteria_points',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'corection_authorized' =>
                        array(
                            'Field' => 'correction_authorized',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'get/set DateTime',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                    'correction_authorized_by' =>
                        array(
                            'Field' => 'correction_authorized_by',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'ist eine user_id aus ILIAS (neues Datenmodell benötigt)',
                            'src_table' => 'xlas_corrector_summary',
                        ),
                ),
            'xlas_et_corr_task_prefs' =>
                array(
                    'task_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'Nur für eine Task',
                            'src_table' => 'xlas_corrector',
                        ),
                    'corrector_id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corrector',
                        ),
                    'criterion_copy' =>
                        array(
                            'Field' => 'criterion_copy',
                            'Type' => 'tinyint(4)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_corrector',
                        ),
                ),
            'xlas_et_essay' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'uuid' =>
                        array(
                            'Field' => 'uuid',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'writer_id' =>
                        array(
                            'Field' => 'writer_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'written_text' =>
                        array(
                            'Field' => 'written_text',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'raw_text_hash' =>
                        array(
                            'Field' => 'raw_text_hash',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'pdf_version' =>
                        array(
                            'Field' => 'pdf_version',
                            'Type' => 'varchar(50)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'task_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'last_change' =>
                        array(
                            'Field' => 'edit_ended',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'klarere Bezeichnung',
                            'src_table' => 'xlas_essay',
                        ),
                    'service_version' =>
                        array(
                            'Field' => 'service_version',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_essay',
                        ),
                    'first_change' =>
                        array(
                            'Field' => 'edit_started',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'klarere Bezeichnung',
                            'src_table' => 'xlas_essay',
                        ),
                ),
            'xlas_et_rating_crit' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_rating_crit',
                        ),
                    'title' =>
                        array(
                            'Field' => 'title',
                            'Type' => 'varchar(255)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_rating_crit',
                        ),
                    'description' =>
                        array(
                            'Field' => 'description',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_rating_crit',
                        ),
                    'points' =>
                        array(
                            'Field' => 'points',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_rating_crit',
                        ),
                    'corrector_id' =>
                        array(
                            'Field' => 'corrector_id',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_rating_crit',
                        ),
                    'task_id' =>
                        array(
                            'Field' => 'object_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_rating_crit',
                        ),
                    'general' =>
                        array(
                            'Field' => 'is_general',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_rating_crit',
                        ),
                ),
            'xlas_et_task_settings' =>
                array(
                    'task_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'Neue Tabelle für den Task-Typ EssayTask',
                            'src_table' => 'xlas_corr_setting',
                        ),
                    'max_points' =>
                        array(
                            'Field' => 'max_points',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'Bei der EssayTask von Hand festgelegt, bei QuestionsTask evtl. aus Fragen berechnet',
                            'src_table' => 'xlas_corr_setting',
                        ),
                ),
            'xlas_et_write_settings' =>
                array(
                    'headline_scheme' =>
                        array(
                            'Field' => 'headline_scheme',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'formatting_options' =>
                        array(
                            'Field' => 'formatting_options',
                            'Type' => 'varchar(250)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'notice_boards' =>
                        array(
                            'Field' => 'notice_boards',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'copy_allowed' =>
                        array(
                            'Field' => 'copy_allowed',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'add_paragraph_numbers' =>
                        array(
                            'Field' => 'add_paragraph_numbers',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '1',
                            'Extra' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'add_correction_margin' =>
                        array(
                            'Field' => 'add_correction_margin',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'left_correction_margin' =>
                        array(
                            'Field' => 'left_correction_margin',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'right_correction_margin' =>
                        array(
                            'Field' => 'right_correction_margin',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'allow_spellcheck' =>
                        array(
                            'Field' => 'allow_spellcheck',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'ass_id' =>
                        array(
                            'Field' => 'task_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_editor_settings',
                        ),
                    'writing_type' =>
                        array(
                            'Field' => 'task_type',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => 'essay_editor',
                            'Extra' => '',
                            'comment' => '',
                            'src_table' => 'xlas_task_settings',
                        ),
                ),
            'xlas_et_essay_image' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'essay_id' =>
                        array(
                            'Field' => 'essay_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'page_no' =>
                        array(
                            'Field' => 'page_no',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'width' =>
                        array(
                            'Field' => 'width',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'height' =>
                        array(
                            'Field' => 'height',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'mime' =>
                        array(
                            'Field' => 'mime',
                            'Type' => 'varchar(255)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'thumb_width' =>
                        array(
                            'Field' => 'thumb_width',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'thumb_height' =>
                        array(
                            'Field' => 'thumb_height',
                            'Type' => 'int(11)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'thumb_mime' =>
                        array(
                            'Field' => 'thumb_mime',
                            'Type' => 'varchar(255)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'file_id' =>
                        array(
                            'Field' => 'file_id',
                            'Type' => 'varchar(50)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'ist eine Resourcen-id aus ILIAS ',
                            'src_table' => 'xlas_essay_image',
                        ),
                    'thumb_id' =>
                        array(
                            'Field' => 'thumb_id',
                            'Type' => 'varchar(50)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'comment' => 'ist eine Resourcen-id aus ILIAS ',
                            'src_table' => 'xlas_essay_image',
                        ),
                ),
            'xlas_et_writer_history' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_history',
                        ),
                    'essay_id' =>
                        array(
                            'Field' => 'essay_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_history',
                        ),
                    'timestamp' =>
                        array(
                            'Field' => 'timestamp',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_history',
                        ),
                    'content' =>
                        array(
                            'Field' => 'content',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_history',
                        ),
                    'is_delta' =>
                        array(
                            'Field' => 'is_delta',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_history',
                        ),
                    'hash_before' =>
                        array(
                            'Field' => 'hash_before',
                            'Type' => 'varchar(50)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_history',
                        ),
                    'hash_after' =>
                        array(
                            'Field' => 'hash_after',
                            'Type' => 'varchar(50)',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_history',
                        ),
                ),
            'xlas_et_writer_notice' =>
                array(
                    'id' =>
                        array(
                            'Field' => 'id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_notice',
                        ),
                    'essay_id' =>
                        array(
                            'Field' => 'essay_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'MUL',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_notice',
                        ),
                    'note_no' =>
                        array(
                            'Field' => 'note_no',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_notice',
                        ),
                    'note_text' =>
                        array(
                            'Field' => 'note_text',
                            'Type' => 'longtext',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_notice',
                        ),
                    'last_change' =>
                        array(
                            'Field' => 'last_change',
                            'Type' => 'datetime',
                            'Null' => 'YES',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_notice',
                        ),
                ),
            'xlas_et_writer_prefs' =>
                array(
                    'writer_id' =>
                        array(
                            'Field' => 'writer_id',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => 'PRI',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_prefs',
                        ),
                    'instructions_zoom' =>
                        array(
                            'Field' => 'instructions_zoom',
                            'Type' => 'double',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_prefs',
                        ),
                    'editor_zoom' =>
                        array(
                            'Field' => 'editor_zoom',
                            'Type' => 'double',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => null,
                            'Extra' => '',
                            'src_table' => 'xlas_writer_prefs',
                        ),
                    'word_count_enabled' =>
                        array(
                            'Field' => 'word_count_enabled',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_writer_prefs',
                        ),
                    'word_count_characters' =>
                        array(
                            'Field' => 'word_count_characters',
                            'Type' => 'int(11)',
                            'Null' => 'NO',
                            'Key' => '',
                            'Default' => '0',
                            'Extra' => '',
                            'src_table' => 'xlas_writer_prefs',
                        ),
                ),
        );
    }
}
