<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDataRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDisplayRepo;
use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\PropertiesRepo;

class CorrectionsViewRepo extends ViewRepo implements \Edutiek\AssessmentService\Views\Data\CorrectionsViewRepo
{
    public function __construct(
        private readonly ilDBInterface $db,
        private readonly RepositoryInterface $writer_repo,
        private readonly RepositoryInterface $task_repo,
        private readonly PropertiesRepo $properties_repo,
        private readonly RepositoryInterface $location_repo,
        private readonly RepositoryInterface $essay_repo,
        private readonly RepositoryInterface $corrector_assignment_repo,
        private readonly RepositoryInterface $corrector_summary_repo,
        private readonly RepositoryInterface $corrector_repo,
        private readonly UserDataRepo $user_data_repo,
        private readonly UserDisplayRepo $user_display_repo,
        private readonly \DateTimeZone $time_zone
    ) {
    }

    public function some(array $filter, ?int $limit = null, ?int $offset = null): array
    {
        $sub_sql = "SELECT GROUP_CONCAT(CONCAT('[', ca.position, ',', ca.id, ',', COALESCE(cs.id, 'null'), ',', COALESCE(cc.id, 'null'), ',', COALESCE(cc.user_id, 'null'), ']') SEPARATOR ',') ";
        $sub_sql .= "FROM {$this->corrector_assignment_repo->table()} AS ca ";
        $sub_sql .= "LEFT JOIN {$this->corrector_repo->table()} AS cc ON ca.corrector_id = cc.id ";
        $sub_sql .= "LEFT JOIN {$this->corrector_summary_repo->table()} AS cs ON ";
        $sub_sql .= "(ca.writer_id = cs.writer_id AND ca.corrector_id = cs.corrector_id AND ca.task_id = cs.task_id) ";
        $sub_sql .= "WHERE w.id = ca.writer_id AND t.task_id = ca.task_id ";
        $sub_sql .= "GROUP BY ca.writer_id, ca.task_id";

        $sql = "SELECT w.id AS writer_id, w.user_id AS user_id, w.location AS location_id, e.id  AS essay_id, ";
        $sql .= "w.writing_authorized_by as authorized_by, w.writing_excluded_by as excluded_by, ";
        $sql .= "w.correction_status_changed_by as finalized_by, t.task_id as task_id, w.ass_id as ass_id, ";
        $sql .= "({$sub_sql}) AS corrections ";
        $sql .= "FROM {$this->writer_repo->table()} AS w ";
        $sql .= "JOIN {$this->task_repo->table()} AS t ON w.ass_id = t.ass_id ";
        $sql .= "LEFT JOIN {$this->essay_repo->table()} AS e ON w.id = e.writer_id AND t.task_id = e.task_id ";
        $sql .= "LEFT JOIN usr_data AS u ON u.usr_id = w.user_id ";
        $sql .= "LEFT JOIN object_reference AS r ON w.ass_id = r.obj_id ";
        $sql .= "WHERE " . ($this->where($filter) ?? "1") . " ";
        $sql .= "GROUP BY writer_id, task_id, essay_id, user_id, location_id, authorized_by, excluded_by ";

        $query = $this->db->query($sql);
        $result = [];

        while ($row = $this->db->fetchAssoc($query)) {
            $corrections = [];
            $cor_result = json_decode('[' . $row['corrections'] . ']', true);
            foreach ($cor_result as list($position, $assignment_id, $summary_id, $corrector_id, $user_id)) {
                $corrections[$position] = new Correction(
                    $this->corrector_assignment_repo->dehydratedInstance((int) $assignment_id),
                    $summary_id !== null ? $this->corrector_summary_repo->dehydratedInstance((int) $summary_id) : null,
                    $this->corrector_repo->dehydratedInstance((int) $corrector_id),
                    $this->user_data_repo->dehydratedInstance((int) $user_id)
                );
            }

            $result[] = new CorrectionsView(
                $this->task_repo->dehydratedInstance($row['task_id']),
                $this->properties_repo->dehydratedInstance($row['ass_id']),
                $this->writer_repo->dehydratedInstance($row['writer_id']),
                $this->user_data_repo->dehydratedInstance($row['user_id']),
                $this->user_display_repo->dehydratedInstance($row['user_id']),
                $this->location_repo->dehydratedInstance($row['location_id']),
                $this->essay_repo->dehydratedInstance($row['essay_id']),
                $corrections,
                $this->user_data_repo->dehydratedInstance($row['finalized_by']),
                $this->user_data_repo->dehydratedInstance($row['authorized_by']),
                $this->user_data_repo->dehydratedInstance($row['excluded_by'])
            );
        }

        array_map(fn(\ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\HydrationInterface $r) => $r->hydrate(), [
            $this->writer_repo, $this->location_repo, $this->user_data_repo, $this->user_display_repo, $this->task_repo,
            $this->properties_repo, $this->corrector_repo, $this->corrector_assignment_repo, $this->corrector_summary_repo
        ]);

        //Apply backend filtering for composed complex values
        $result = array_filter($result, function (CorrectionsView $wv) use ($filter) {
            if (!empty($filter['status']) && !in_array($wv->getWriter()->getCombinedStatus()->value, $filter['status'])) {
                return false;
            }
            if (!empty($filter['min_words'] ?? null) && ($wv->getEssay()?->getWordCount() ?? 0) < (int) $filter['min_words']) {
                return false;
            }
            if (!empty($filter['max_words'] ?? null) && ($wv->getEssay()?->getWordCount() ?? 0) > (int) $filter['max_words']) {
                return false;
            }
            return true;
        });

        return $result;
    }

    public function whereCondition(string $key, mixed $value): ?string
    {
        return match($key) {
            "writer_id" => is_array($value) ? $this->db->in("w.id", $value, false, "integer") : "w.id = " . $this->db->quote($value, "integer"),
            "essay_id" => is_array($value) ? $this->db->in("e.id", $value, false, "integer") : "e.id = " . $this->db->quote($value, "integer"),
            "ass_id", "obj_id" => is_array($value) ? $this->db->in("w.ass_id", $value, false, "integer") : "w.ass_id = " . $this->db->quote($value, "integer"),
            "ref_id" => is_array($value) ? $this->db->in("r.ref_id", $value, false, "integer") : "r.ref_id = " . $this->db->quote($value, "integer"),
            "task_id", "task" => is_array($value) ? $this->db->in("t.task_id", $value, false, "integer") : "t.task_id = " . $this->db->quote($value, "integer"),
            "name" => $this->db->like("CONCAT(u.firstname, u.lastname, u.login, u.email,w.pseudonym)", "text", "%" . $value . "%", true),
            "time_limit_changed" => ($value == "1" ? "NOT" : "") . "(w.earliest_start IS NULL AND w.latest_end IS NULL AND w.time_limit_minutes IS NULL)",
            "location" => "writer.location = " . $this->db->quote($value, "integer"),
            default => null,
        };
    }

    public function havingCondition(string $key, mixed $value): ?string
    {
        return match($key) {
            "pdf_version" => "MAX(e.pdf_version) IS " . ($value == "2" ? "" : "NOT") . " NULL",
            default => null,
        };
    }

    public function count(array $filter): int
    {
        // todo: use the full condition from some() whenn all filters can ve applied by SQL

        $sql = "SELECT count(*) AS count ";
        $sql .= "FROM {$this->writer_repo->table()} AS w ";
        $sql .= "JOIN {$this->task_repo->table()} AS t ON w.ass_id = t.ass_id ";
        $sql .= "LEFT JOIN {$this->essay_repo->table()} AS e ON w.id = e.writer_id ";
        $sql .= "LEFT JOIN usr_data AS u ON u.usr_id = w.user_id ";
        $sql .= "LEFT JOIN object_reference AS r ON w.ass_id = r.obj_id ";
        $sql .= "WHERE " . ($this->where($filter) ?? "1") . " ";

        $query = $this->db->query($sql);
        $row = $this->db->fetchAssoc($query);
        return (int) $row['count'] ?? 0;

        return 2;
    }

    public function locations(array $ass_ids): array
    {
        return $this->location_repo->queryAllBy(['ass_id' => $ass_ids]);
    }

    public function tasks(array $ass_ids): array
    {
        return $this->task_repo->queryAllBy(['ass_id' => $ass_ids]);
    }

    public function assessments(array $ass_ids): array
    {
        $assessments = [];

        foreach ($ass_ids as $ass_id) {
            $property = $this->properties_repo->one($ass_id);
            $assessments[$ass_id] = $property->getTitle();
        }
        return $assessments;
    }

    public function hasMultiTasks(array $ass_ids): bool
    {
        $query = $this->db->query(
            'SELECT MAX(multi_tasks) as max_multi_tasks 
                    FROM xlas_as_orga_settings WHERE '
            . $this->db->in('ass_id', $ass_ids, false, 'integer')
        );
        $row = $this->db->fetchAssoc($query);

        return (bool) ($row['max_multi_tasks'] ?? 0);
    }

    public function visibleCorrectors(array $ass_ids): int
    {
        $query = $this->db->query(
            'SELECT MAX(required_correctors) as max_required_correctors, 
                    MAX(stitch_after_procedure) as max_stitch_after_procedure
                    FROM xlas_as_corr_settings WHERE '
            . $this->db->in('ass_id', $ass_ids, false, 'integer')
        );
        $row = $this->db->fetchAssoc($query);

        $required = (int) ($row['max_required_correctors'] ?? 1);
        if ($required > 1 && ($row['max_stitch_after_procedure'] ?? 0)) {
            $required++;
        }
        return $required;
    }
}
