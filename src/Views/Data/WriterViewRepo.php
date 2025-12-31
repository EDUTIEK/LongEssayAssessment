<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\HydrationInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDataRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDisplayRepo;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\Data\Location;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use ilDBInterface;
use DateTimeZone;
use DateTimeImmutable;

class WriterViewRepo extends ViewRepo implements \Edutiek\AssessmentService\Views\Data\WriterViewRepo
{
    /**
     * @param ilDBInterface       $db
     * @param RepositoryInterface<Writer> $writer_repo
     * @param RepositoryInterface<Location> $location_repo
     * @param RepositoryInterface<Essay> $essay_repo
     * @param UserDataRepo        $user_data_repo
     * @param UserDisplayRepo     $user_display_repo
     */
    public function __construct(
        private readonly ilDBInterface $db,
        private readonly RepositoryInterface $writer_repo,
        private readonly RepositoryInterface $location_repo,
        private readonly RepositoryInterface $essay_repo,
        private readonly UserDataRepo $user_data_repo,
        private readonly UserDisplayRepo $user_display_repo,
        private readonly DateTimeZone $time_zone
    ) {

    }

    /**
     * @inheritDoc
     */
    public function some(array $filter, ?int $limit = null, ?int $offset = null): array
    {
        $limit = $limit !== null ? 'LIMIT ' . $limit : '';
        $offset = $offset !== null ? 'OFFSET ' . $offset : '';

        $sql = "SELECT w.id AS writer_id, w.user_id AS user_id, w.location AS location_id, GROUP_CONCAT(e.id SEPARATOR ',') AS essay_ids, ";
        $sql .= "w.writing_authorized_by as authorized_by, w.writing_excluded_by as excluded_by, ";
        $sql .= "MAX(e.last_change) AS newest_last_change, ";
        $sql .= "SUM(CASE
            WHEN e.written_text IS NULL OR TRIM(e.written_text) = '' THEN 0
            ELSE
                LENGTH(TRIM(e.written_text))
                - LENGTH(REPLACE(TRIM(e.written_text), ' ', ''))
                + 1
            END) AS total_word_count, ";
        $sql .= "CASE WHEN MAX(e.pdf_version) IS NOT NULL THEN 1 ELSE 0 END AS has_pdf_version ";
        $sql .= "FROM {$this->writer_repo->table()} AS w ";
        $sql .= "LEFT JOIN {$this->essay_repo->table()} AS e ON w.id = e.writer_id ";
        $sql .= "LEFT JOIN usr_data AS u ON u.usr_id = w.user_id ";
        $sql .= "LEFT JOIN object_reference AS r ON w.ass_id = r.obj_id ";
        $sql .= "WHERE " . ($this->where($filter) ?? "1") . " ";
        $sql .= "GROUP BY writer_id, user_id, location_id, authorized_by, excluded_by ";
        $sql .= "HAVING " . ($this->having($filter) ?? "1") . " ";
        $sql .= "{$limit} {$offset}";

        $query = $this->db->query($sql);
        $result = [];

        while ($row = $this->db->fetchAssoc($query)) {
            $result[] = new WriterView(
                $this->writer_repo->dehydratedInstance($row['writer_id']),
                $this->user_data_repo->dehydratedInstance($row['user_id']),
                $this->user_display_repo->dehydratedInstance($row['user_id']),
                $this->location_repo->dehydratedInstance($row['location_id']),
                new EssayTaskSummary($row['newest_last_change'] !== null ? new DateTimeImmutable($row['newest_last_change'], $this->time_zone) : null, (bool)$row['has_pdf_version'], (int)$row['total_word_count']),
                $this->user_data_repo->dehydratedInstance($row['authorized_by']),
                $this->user_data_repo->dehydratedInstance($row['excluded_by'])
            );
        }

        //Hydrate all objects
        array_map(fn (HydrationInterface $r) => $r->hydrate(), [
            $this->writer_repo, $this->location_repo, $this->user_data_repo, $this->user_display_repo
        ]);

        //Apply backend filtering for composed complex values
        $result = array_filter($result, function (WriterView $wv) use ($filter) {
            $status = $wv->getWriter()->getWritingStatus();
            if (!empty($filter['status']) && !in_array($wv->getWriter()->getWritingStatus()->value, $filter['status'])) {
                return false;
            }
            if (!empty($filter['min_words'] ?? null) && ($wv->getEssayTaskSummary()?->getWords() ?? 0) < (int) $filter['min_words']) {
                return false;
            }
            if (!empty($filter['max_words'] ?? null) && ($wv->getEssayTaskSummary()?->getWords() ?? 0) > (int) $filter['max_words']) {
                return false;
            }
            return true;
        });

        return $result;
    }

    public function filter(string $key, mixed $value): ?string
    {
        return match($key) {
            "id" => is_array($value) ? $this->db->in("w.id", $value, false, "integer") : "w.id = " . $this->db->quote($value, "integer"),
            "ass_id", "obj_id" => is_array($value) ? $this->db->in("w.ass_id", $value, false, "integer") : "w.ass_id = " . $this->db->quote($value, "integer"),
            "ref_id" => is_array($value) ? $this->db->in("r.ref_id", $value, false, "integer") : "r.ref_id = " . $this->db->quote($value, "integer"),
            "name" => $this->db->like("CONCAT(u.firstname, u.lastname, u.login, w.pseudonym)", "text", $value, true),
            "time_limit_changed" => ($value == "1" ? "NOT" : "") . "(w.earliest_start IS NULL AND w.latest_end IS NULL AND w.time_limit_minutes IS NULL)",
            "location" => "writer.location = " . $this->db->quote($value, "integer"),
            default => null,
        };
    }

    public function having(array $filter): ?string
    {
        $filter = array_filter($filter);
        $parts = array_filter(array_map([$this, 'groupingFilter'], array_keys($filter), $filter));

        return !empty($parts) ? implode(' AND ', $parts) : null;
    }

    public function groupingFilter(string $key, mixed $value): ?string
    {
        return match($key) {
            "pdf_version" => "MAX(e.pdf_version) IS " . ($value == "2" ? "" : "NOT") . " NULL",
            default => null,
        };
    }
}
