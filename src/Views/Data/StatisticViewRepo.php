<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use Edutiek\AssessmentService\Assessment\Data\CorrectionStatus;
use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDataRepo;
use Edutiek\AssessmentService\Assessment\Data\PropertiesRepo;
use ILIAS\Plugin\LongEssayAssessment\Task\Data\CorrectorAssignment;

class StatisticViewRepo extends ViewRepo implements \Edutiek\AssessmentService\Views\Data\StatisticViewRepo
{
    public function __construct(
        private readonly ilDBInterface $db,
        private readonly RepositoryInterface $writer_repo,
        private readonly RepositoryInterface $corrector_repo,
        private readonly RepositoryInterface $grade_repo,
        private readonly RepositoryInterface $corrector_summary_repo,
        private readonly RepositoryInterface $correction_settings_repo,
        private readonly PropertiesRepo $properties_repo,
        private readonly UserDataRepo $user_data_repo,
    ) {

    }

    public function someCorrections(array $filter, bool $include_pre_graded = false): StatisticView
    {
        $sql = "SELECT w.ass_id as ass_id, cs.writer_id as writer_id, c.user_id as user_id, cs.points as points, ";
        $sql .= "cs.corection_authorized as authorized, cs.pre_graded as pre_graded, g.grade as grade, g.passed as passed ";
        $sql .= "FROM {$this->corrector_summary_repo->table()} AS cs ";
        $sql .= "LEFT JOIN {$this->writer_repo->table()} AS w ON (cs.writer_id = w.id) ";
        $sql .= "LEFT JOIN {$this->corrector_repo->table()} AS c ON (cs.corrector_id = c.id) ";
        $sql .= "LEFT JOIN {$this->grade_repo->table()} AS g ON (cs.points >= g.min_points AND w.ass_id = g.ass_id) ";
        $sql .= "LEFT JOIN usr_data AS u ON u.usr_id = c.user_id ";
        $sql .= "WHERE NOT EXISTS ( SELECT 1 FROM xlas_as_grade_level AS g2 WHERE g2.min_points > g.min_points AND cs.points >= g2.min_points AND w.ass_id = g2.ass_id) ";
        $sql .= "AND w.id IS NOT NULL ";
        $sql .= "AND " . ($this->where($filter) ?? "1") . " ";
        $sql .= "HAVING " . ($this->having($filter) ?? "1") . " ";

        $correctors = $this->corrector_repo->queryAllBy(['ass_id' => $filter['ass_id'] ?? []]);

        $query = $this->db->query($sql);
        $result_by_user = [];
        foreach($correctors as $corrector) {
            $result_by_user[$corrector->getUserId()] = [];
        }
        $result = [];

        while ($row = $this->db->fetchAssoc($query)) {
            $grading_obj = new GradingObject(
                $this->properties_repo->dehydratedInstance($row['ass_id']),
                $this->user_data_repo->dehydratedInstance($row['user_id']),
                $row['writer_id'] !== null,
                true,
                $row['authorized'] !== null  || ($row['pre_graded'] !== null && $include_pre_graded),
                $row['points'],
                $row['grade'],
                (bool) $row['passed']
            );

            $result[] = $grading_obj;
            $result_by_user[(int)$row['user_id']][] = $grading_obj;
        }

        $this->user_data_repo->hydrate();
        $this->properties_repo->hydrate();

        list($grades, $points, $grades_uniform, $max_points_uniform) = $this->queryCountListSettings($filter);
        $grade_counts = []; foreach($grades as $grade) {$grade_counts[$grade] = 0;}
        $point_counts = []; foreach($points as $point) {$point_counts[(string)$point] = 0;}

        //array_merge create a copy of the count template array
        return new StatisticView(null, $result, array_merge($point_counts), array_merge($grade_counts), $max_points_uniform, $grades_uniform);
    }

    public function someAssessments(array $filter): StatisticView
    {
        $sql = "SELECT w.id as writer_id, w.ass_id as ass_id, w.user_id as user_id, w.final_points as points, g.grade as grade, g.passed as passed, w.correction_status, w.writing_authorized as authorized FROM {$this->writer_repo->table()} as w ";
        $sql .= "LEFT JOIN {$this->grade_repo->table()} AS g ON (w.final_points >= g.min_points AND w.ass_id = g.ass_id) "; // all possible grade_level above the min_points requirement
        $sql .= "LEFT JOIN usr_data AS u ON u.usr_id = w.user_id ";
        $sql .= "WHERE NOT EXISTS ( SELECT 1 FROM xlas_as_grade_level AS g2 WHERE g2.min_points > g.min_points AND w.final_points >= g2.min_points AND w.ass_id = g2.ass_id) "; // select the bigges possible grade_level
        $sql .= "AND w.correction_status = " . $this->db->quote(CorrectionStatus::FINALIZED->value, 'text');
        $sql .= "AND " . ($this->where($filter) ?? "1") . " ";
        $sql .= "HAVING " . ($this->having($filter) ?? "1") . " ";


        $query = $this->db->query($sql);
        $result_by_user = [];
        $result_by_ass = [];
        $result = [];

        while ($row = $this->db->fetchAssoc($query)) {
            $grading_obj = new GradingObject(
                $this->properties_repo->dehydratedInstance($row['ass_id']),
                $this->user_data_repo->dehydratedInstance($row['user_id']),
                $row['writer_id'] !== null,
                $row['authorized'] !== null,
                $row['correction_status'] == CorrectionStatus::FINALIZED->value,
                $row['points'],
                $row['grade'],
                (bool) $row['passed']
            );

            $result[] = $grading_obj;
            $result_by_user[(int)$row['user_id']][] = $grading_obj;
            $result_by_ass[(int)$row['ass_id']][] = $grading_obj;
        }

        $this->user_data_repo->hydrate();
        $this->properties_repo->hydrate();

        list($grades, $points, $grades_uniform, $max_points_uniform) = $this->queryCountListSettings($filter);
        $grade_counts = []; foreach($grades as $grade) {$grade_counts[$grade] = 0;}
        $point_counts = []; foreach($points as $point) {$point_counts[(string)$point] = 0;}

        return new StatisticView(null, $result, $point_counts, $grade_counts, $max_points_uniform, $grades_uniform);
    }

    private function queryCountListSettings(array $filter)
    {
        $sql = "SELECT GROUP_CONCAT(DISTINCT gl.grades SEPARATOR ',') as grades, COUNT(DISTINCT gl.grades) as grades_uniform, ";
        $sql .= "MAX(cs.max_points) as max_points, COUNT(DISTINCT cs.max_points) as max_points_uniform ";
        $sql .= "FROM {$this->correction_settings_repo->table()} as cs ";
        $sql .= "LEFT JOIN (SELECT ass_id, GROUP_CONCAT(DISTINCT REPLACE(grade, ',', ' ') ORDER BY min_points DESC SEPARATOR ',') AS grades FROM {$this->grade_repo->table()} GROUP BY ass_id) AS gl on (gl.ass_id = cs.ass_id) ";

        if (isset($filter['ass_id'])) {
            $sql .= "WHERE " .  (is_array($filter['ass_id']) ? $this->db->in("cs.ass_id", $filter['ass_id'], false, "integer") : "cs.ass_id = " . $this->db->quote($filter['ass_id'], "integer"));
        }

        $query = $this->db->query($sql);
        $result = [];
        $max_point = 0;
        $all_grades = [];

        $row = $this->db->fetchAssoc($query);

        $result = [$row['grades'] !== null ? explode(',', $row['grades']) : [],
                   $row['max_points'] !== null ? range((int) ($row['max_points']), 0) : [],
                   ((int)$row['grades_uniform']) === 1,
                   ((int)$row['max_points_uniform']) === 1,
        ];

        return $result;
    }

    public function whereCondition(string $key, mixed $value): ?string
    {
        return match($key) {
            'user_id' => is_array($value) ? $this->db->in("u.usr_id", $value, false, "integer") : "u.usr_id = " . $this->db->quote($value, "integer"),
            'corrector_id' => is_array($value) ? $this->db->in("c.id", $value, false, "integer") : "c.id = " . $this->db->quote($value, "integer"),
            'ass_id' => is_array($value) ? $this->db->in("w.ass_id", $value, false, "integer") : "w.ass_id = " . $this->db->quote($value, "integer"),
            'name' => $this->db->like("CONCAT(u.firstname, u.lastname, u.login, u.email,w.pseudonym)", "text", "%". $value . "%", true),
            default => null,
        };
    }

    public function havingCondition(string $key, mixed $value): ?string
    {
        return match($key) {
            default => null,
        };
    }
}
