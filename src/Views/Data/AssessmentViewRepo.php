<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use ilDBInterface;
use DateTimeZone;
use DateTimeImmutable;

class AssessmentViewRepo implements \Edutiek\AssessmentService\Views\Data\AssessmentViewRepo
{
    private DateTimeZone $time_zone;

    public function __construct(
        private readonly ilDBInterface $db,
    ) {
        $this->time_zone = new DateTimeZone(date_default_timezone_get());
    }

    public function correctorAssessments(int $user_id): array
    {
        $query = "
            SELECT c.ass_id AS ass_id, rd.ref_id AS context_id, p.title AS parent_title, d.title AS title,
            o.correction_start AS correction_start, o.correction_end AS correction_end,
            COUNT(a.id) AS assigned_gradings, COUNT(s.id) AS finished_gradings
            FROM xlas_as_corrector c
            JOIN xlas_as_orga_settings o ON o.ass_id = c.ass_id
            JOIN object_data d ON d.obj_id = c.ass_id
            JOIN object_reference rd ON rd.obj_id = d.obj_id
            JOIN tree tr ON tr.child = rd.ref_id
            JOIN object_reference rp ON rp.ref_id = tr.parent
            JOIN object_data p ON p.obj_id = rp.obj_id 
            LEFT JOIN xlas_ta_corr_assign a ON a.corrector_id = c.id 
            LEFT JOIN xlas_as_writer w ON w.id = a.writer_id
            LEFT JOIN xlas_ta_corr_summary s ON s.corrector_id = c.id 
                AND s.writer_id = w.id AND s.task_id = a.task_id 
                AND (s.revised IS NOT NULL OR (s.corection_authorized IS NOT NULL 
                    AND (w.correction_status = 'stitch' OR w.correction_status = 'finalized')))   
            WHERE c.user_id = %s
            AND o.online = 1 AND rd.deleted IS NULL
            GROUP BY ass_id, context_id, parent_title, title, correction_start, correction_end
        ";

        $views = [];
        $result = $this->db->queryF($query, ['integer'], [$user_id]);
        while ($row = $this->db->fetchAssoc($result)) {
            $views[] = new CorrectorAssessmentView(
                $row['ass_id'],
                $row['context_id'],
                $row['parent_title'],
                $row['title'],
                $row['correction_start'] ? new DateTimeImmutable($row['correction_start'], $this->time_zone) : null,
                $row['correction_end'] ? new DateTimeImmutable($row['correction_end'], $this->time_zone) : null,
                $row['assigned_gradings'],
                $row['finished_gradings']
            );
        }

        return $views;
    }
}
