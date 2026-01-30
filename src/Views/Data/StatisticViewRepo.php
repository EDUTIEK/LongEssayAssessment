<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use Edutiek\AssessmentService\Assessment\Data\CorrectionStatus;
use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDataRepo;
use Edutiek\AssessmentService\Assessment\Data\PropertiesRepo;

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

    public function oneCorrector(int $corrector_id, array $filter): StatisticView
    {
        $filter['corrector_id'] = $corrector_id;
        return $this->someCorrections($filter)[0];
    }

    public function someCorrections(array $filter): array
    {

        $sql = "SELECT cs.task_id as task_id, cs.writer_id as writer_id, c.user_id as user_id, cs.points as points, cs.corection_authorized as authorized, g.grade as grade, g.passed as passed FROM {$this->corrector_summary_repo->table()} AS cs ";
        $sql .= "LEFT JOIN {$this->writer_repo->table()} AS w ON (cs.writer_id = w.id) ";
        $sql .= "LEFT JOIN {$this->corrector_repo->table()} AS c ON (cs.corrector_id >= c.id) ";
        $sql .= "LEFT JOIN {$this->grade_repo->table()} AS g ON (points >= g.min_points) ";
        $sql .= "LEFT JOIN usr_data AS u ON u.usr_id = c.user_id ";
        $sql .= "WHERE NOT EXISTS ( SELECT 1 FROM xlas_as_grade_level AS g2 WHERE cs.points >= g.min_points AND g2.min_points > g.min_points) ";
        $sql .= "AND " . ($this->where($filter) ?? "1") . " ";
        $sql .= "HAVING " . ($this->having($filter) ?? "1") . " ";

        $correctors = $this->corrector_repo->queryAllBy(['ass_id' => $filter['ass_id'] ?? []]);

        $query = $this->db->query($sql);
        $result_by_user = array_map(fn ($k, $v) => [], array_map(fn ($corrector) => $corrector->getUserId(), $correctors), $correctors);
        $result = [];

        while ($row = $this->db->fetchAssoc($query)) {
            if (!isset($result_by_user[$row['user_id']])) {
                $result_by_user[(int)$row['user_id']] = [];
            }

            $grading_obj = new GradingObject(
                $row['task_id'],
                $row['writer_id'],
                true,
                $row['authorized'] !== null,
                $row['points'],
                $row['grade'],
                (bool) $row['passed']
            );

            $result[] = $grading_obj;
            $result_by_user[$row['user_id']][] = $grading_obj;
        }

        list($grades, $points, $grades_uniform, $max_points_uniform) = $this->queryCountListSettings($filter);
        $grade_counts = array_map(fn ($k, $v) => 0, $grades, $grades);
        $point_counts = array_map(fn ($k, $v) => 0, $points, $points);

        //array_merge create a copy of the count template array
        $ret =  [
            new StatisticView(null, $result, array_merge($point_counts), array_merge($grade_counts), $max_points_uniform, $grades_uniform),
            array_map(
                fn ($user_id, array $res) => new StatisticView($this->user_data_repo->dehydratedInstance($user_id), $res, array_merge($point_counts), array_merge($grade_counts), $max_points_uniform, $grades_uniform),
                array_keys($result_by_user),
                $result_by_user
            )
        ];

        $this->user_data_repo->hydrate();

        return $ret;
    }

    public function oneAssessment(int $ass_id, array $filter): StatisticView
    {
        $filter['ass_id'] = $ass_id;
        return $this->byWriter($filter, true, false, false)['general'];
    }

    public function someAssessments(array $filter): array
    {
        return $this->byWriter($filter, true, false, true);
    }

    public function someWriter(array $filter): array
    {
        return $this->byWriter($filter, true, true, false);
    }

    public function byWriter(array $filter, bool $general, bool $by_user, bool $by_assesment): array
    {
        $sql = "SELECT w.ass_id as ass_id, w.user_id as user_id, w.final_points as points, g.grade as grade, g.passed as passed, w.correction_status, w.writing_authorized as authorized FROM {$this->writer_repo->table()} as w ";
        $sql .= "LEFT JOIN {$this->grade_repo->table()} AS g ON (w.final_points >= g.min_points) ";
        $sql .= "LEFT JOIN usr_data AS u ON u.usr_id = w.user_id ";
        $sql .= "WHERE NOT EXISTS ( SELECT 1 FROM xlas_as_grade_level AS g2 WHERE w.final_points >= g.min_points AND g2.min_points > g.min_points) ";
        $sql .= "AND " . ($this->where($filter) ?? "1") . " ";
        $sql .= "HAVING " . ($this->having($filter) ?? "1") . " ";


        $query = $this->db->query($sql);
        $result_by_user = [];
        $result_by_ass = [];
        $result = [];

        while ($row = $this->db->fetchAssoc($query)) {
            if (!isset($result_by_user[$row['user_id']])) {
                $result_by_user[(int)$row['user_id']] = [];
            }

            if (!isset($result_by_ass[$row['ass_id']])) {
                $result_by_user[(int)$row['ass_id']] = [];
            }

            $grading_obj = new GradingObject(
                $row['ass_id'],
                $row['user_id'],
                $row['authorized'] !== null,
                $row['correction_status'] == CorrectionStatus::FINALIZED->value,
                $row['points'],
                $row['grade'],
                (bool) $row['passed']
            );

            $result[] = $grading_obj;
            $result_by_user[$row['user_id']][] = $grading_obj;
            $result_by_user[$row['ass_id']][] = $grading_obj;
        }

        list($grades, $points, $grades_uniform, $max_points_uniform) = $this->queryCountListSettings($filter);
        $grade_counts = array_map(fn ($k, $v) => 0, $grades, $grades);
        $point_counts = array_map(fn ($k, $v) => 0, $points, $points);

        //array_merge create a copy of the count template array
        $ret = [];
        if ($general) {
            $ret['general'] = new StatisticView(null, $result, array_merge($point_counts), array_merge($grade_counts), $max_points_uniform, $grades_uniform);
        }
        if ($by_user) {
            $ret['by_user'] = array_map(
                fn ($user_id, array $res) => new StatisticView($this->user_data_repo->dehydratedInstance($user_id), $res, array_merge($point_counts), array_merge($grade_counts), $max_points_uniform, $grades_uniform),
                array_keys($result_by_user),
                $result_by_user
            );
        }
        if ($by_assesment) {
            $ret['by_assessment'] = array_map(
                fn ($ass_id, array $res) => new StatisticView($this->properties_repo->one($ass_id)?->getTitle(), $res, array_merge($point_counts), array_merge($grade_counts), $max_points_uniform, $grades_uniform),
                array_keys($result_by_ass),
                $result_by_ass
            );
        }

        $this->user_data_repo->hydrate();

        return $ret;
    }

    private function queryCountListSettings(array $filter)
    {
        $sql = "SELECT GROUP_CONCAT(DISTINCT gl.grades SEPARATOR ',') as grades, COUNT(DISTINCT gl.grades) as grades_uniform, ";
        $sql .= "MAX(cs.max_points) as max_points, COUNT(DISTINCT cs.max_points) as max_points_uniform ";
        $sql .= "FROM {$this->correction_settings_repo->table()} as cs ";
        $sql .= "LEFT JOIN (SELECT ass_id, GROUP_CONCAT(DISTINCT REPLACE(grade, ',', ' ') ORDER BY min_points SEPARATOR ',') AS grades FROM {$this->grade_repo->table()} GROUP BY ass_id) AS gl on (gl.ass_id = cs.ass_id) ";

        if (isset($filter['ass_id'])) {
            $sql .= "WHERE " .  (is_array($filter['ass_id']) ? $this->db->in("cs.ass_id", $filter['ass_id'], false, "integer") : "cs.ass_id = " . $this->db->quote($filter['ass_id'], "integer"));
        }

        $query = $this->db->query($sql);
        $result = [];
        $max_point = 0;
        $all_grades = [];

        $row = $this->db->fetchAssoc($query);

        $result = [$row['grades'] !== null ? explode(',', $row['grades']) : [],
                   $row['max_points'] !== null ? range(1, (int) $row['max_points']) : [],
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
            'finalized' => "w.correction_status = " . $this->db->quote(CorrectionStatus::FINALIZED->value, 'text'),
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
