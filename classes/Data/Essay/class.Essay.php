<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Data\UUID\Factory as UUID;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Essay extends RecordData
{
    const WRITING_STATUS_NOT_WRITTEN = 'writing_status_not_written';
    const WRITING_STATUS_NOT_AUTHORIZED = 'writing_status_not_authorized';
    const WRITING_STATUS_EXCLUDED = 'writing_excluded';
    const WRITING_STATUS_AUTHORIZED = 'writing_status_authorized';

    const CORRECTION_STATUS_NOT_POSSIBLE = 'correction_status_not_possible';
    const CORRECTION_STATUS_FINISHED = 'correction_status_finished';
    const CORRECTION_STATUS_STITCH_NEEDED = 'correction_status_stitch_needed';
    const CORRECTION_STATUS_OPEN = 'correction_status_open';

    protected const tableName = 'xlas_essay';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'uuid' => 'text',
        'writer_id' => 'integer',
        'task_id' => 'integer',
        'written_text' => 'text',
        'raw_text_hash' => 'text',
        'service_version' => 'integer',
        'edit_started' => 'datetime',
        'edit_ended' => 'datetime',
        'writing_authorized' => 'datetime',
        'writing_authorized_by' => 'integer',
        'writing_excluded' => 'datetime',
        'writing_excluded_by' => 'integer',
        'pdf_version' => 'text',
        'correction_finalized' => 'datetime',
        'correction_finalized_by' => 'integer',
        'final_points' => 'float',
        'final_grade_level_id' => 'integer',
        'stitch_comment' => 'text',
        'location' => 'integer'
    ];

    protected int $id = 0;
    protected ?string $uuid = null;
    protected int $writer_id = 0;
    protected int $task_id = 0;
    protected ?string $written_text = null;
    protected ?string $raw_text_hash = null;
    protected int $service_version = 0;
    protected ?string  $edit_started = null;
    protected ?string $edit_ended = null;
    protected ?string $writing_authorized = null;
    protected ?int $writing_authorized_by = null;
    protected ?string $writing_excluded = null;
    protected ?int $writing_excluded_by = null;
    protected ?string $pdf_version = null;
    protected ?string $correction_finalized = null;
    protected ?int $correction_finalized_by = null;
    protected ?float $final_points = null;
    protected ?int $final_grade_level_id = null;
    protected ?string $stitch_comment = null;
    protected ?int $location = null;

    public static function model()
    {
        return new self();
    }


    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return Essay
     */
    public function setUuid(string $uuid): Essay
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    /**
     * @param int $task_id
     * @return Essay
     */
    public function setTaskId(int $task_id): Essay
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getWriterId(): int
    {
        return $this->writer_id;
    }

    /**
     * @param int $writer_id
     * @return Essay
     */
    public function setWriterId(int $writer_id): Essay
    {
        $this->writer_id = $writer_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getWrittenText(): ?string
    {
        return $this->written_text;
    }

    /**
     * @param string|null $written_text
     * @return Essay
     */
    public function setWrittenText(?string $written_text): Essay
    {
        $this->written_text = $written_text;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRawTextHash(): ?string
    {
        return $this->raw_text_hash;
    }

    /**
     * @param string|null $raw_text_hash
     * @return Essay
     */
    public function setRawTextHash(?string $raw_text_hash): Essay
    {
        $this->raw_text_hash = $raw_text_hash;
        return $this;
    }

    /**
     * @return int
     */
    public function getServiceVersion(): int
    {
        return $this->service_version;
    }

    /**
     * @param int $service_version
     * @return Essay
     */
    public function setServiceVersion(int $service_version): Essay
    {
        $this->service_version = $service_version;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEditStarted(): ?string
    {
        return $this->edit_started;
    }

    /**
     * @param string|null $edit_started
     * @return Essay
     */
    public function setEditStarted(?string $edit_started): Essay
    {
        $this->edit_started = $edit_started;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEditEnded(): ?string
    {
        return $this->edit_ended;
    }

    /**
     * @param string|null $edit_ended
     * @return Essay
     */
    public function setEditEnded(?string $edit_ended): Essay
    {
        $this->edit_ended = $edit_ended;
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getWritingAuthorized(): ?string
    {
        return $this->writing_authorized;
    }

    /**
     * @param string|null $writing_authorized
     */
    public function setWritingAuthorized(?string $writing_authorized): Essay
    {
        $this->writing_authorized = $writing_authorized;
        return $this;
    }

    /**
     * @return int
     */
    public function getWritingAuthorizedBy(): ?int
    {
        return $this->writing_authorized_by;
    }

    /**
     * @param int $writing_authorized_by
     */
    public function setWritingAuthorizedBy(?int $writing_authorized_by): Essay
    {
        $this->writing_authorized_by = $writing_authorized_by;
        return $this;
    }

    /**
     * @return int
     */
    public function getPdfVersion(): ?string
    {
        return $this->pdf_version;
    }

    /**
     * @param ?string $pdf_version
     * @return Essay
     */
    public function setPdfVersion(?string $pdf_version): Essay
    {
        $this->pdf_version = $pdf_version;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCorrectionFinalized(): ?string
    {
        return $this->correction_finalized;
    }

    /**
     * @param string|null $correction_finalized
     */
    public function setCorrectionFinalized(?string $correction_finalized): Essay
    {
        $this->correction_finalized = $correction_finalized;
        return $this;
    }

    /**
     * @return int
     */
    public function getCorrectionFinalizedBy(): ?int
    {
        return $this->correction_finalized_by;
    }

    /**
     * @param int $correction_finalized_by
     */
    public function setCorrectionFinalizedBy(?int $correction_finalized_by): Essay
    {
        $this->correction_finalized_by = $correction_finalized_by;
        return $this;
    }


    /**
     * @return ?float
     */
    public function getFinalPoints(): ?float
    {
        return $this->final_points;
    }

    /**
     * @param ?float $final_points
     * @return Essay
     */
    public function setFinalPoints(?float $final_points): Essay
    {
        $this->final_points = $final_points;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getFinalGradeLevelId(): ?int
    {
        return $this->final_grade_level_id;
    }

    /**
     * @param int|null $final_grade_level_id
     * @return Essay
     */
    public function setFinalGradeLevelId(?int $final_grade_level_id): Essay
    {
        $this->final_grade_level_id = $final_grade_level_id;
        return $this;
    }

    public function generateUUID4(): string
    {
        return (new UUID())->uuid4AsString();
    }

    /**
     * @return string|null
     */
    public function getWritingExcluded(): ?string
    {
        return $this->writing_excluded;
    }

    /**
     * @param string|null $writing_excluded
     * @return Essay
     */
    public function setWritingExcluded(?string $writing_excluded): Essay
    {
        $this->writing_excluded = $writing_excluded;
        return $this;
    }

    /**
     * @return int
     */
    public function getWritingExcludedBy(): ?int
    {
        return $this->writing_excluded_by;
    }

    /**
     * @param int $writing_excluded_by
     * @return Essay
     */
    public function setWritingExcludedBy(?int $writing_excluded_by): Essay
    {
        $this->writing_excluded_by = $writing_excluded_by;
        return $this;
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Essay
     */
    public function setId(int $id): Essay
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStitchComment(): ?string
    {
        return $this->stitch_comment;
    }

    /**
     * @param string|null $stitch_comment
     * @return Essay
     */
    public function setStitchComment(?string $stitch_comment): Essay
    {
        $this->stitch_comment = $stitch_comment;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLocation(): ?int
    {
        return $this->location;
    }

    /**
     * @param int|null $location
     * @return Essay
     */
    public function setLocation(?int $location): Essay
    {
        $this->location = $location;
        return $this;
    }

}
/**
protected function baseQuery(?int $essay_id) :string
{
$where = [];
if($essay_id !== null){
$where[] = "essay.id = ". $essay_id;
}


return "SELECT essay.id AS essay_id, sn.stitch_needed,
CASE
WHEN edit_started IS null THEN " . Essay::WRITING_STATUS_NOT_WRITTEN . "
WHEN NOT writing_excluded IS null THEN " . Essay::WRITING_STATUS_EXCLUDED . "
WHEN writing_authorized IS null THEN " . Essay::WRITING_STATUS_NOT_AUTHORIZED . "
ELSE " . Essay::WRITING_STATUS_AUTHORIZED . "
END AS writing_status,
CASE
WHEN writing_authorized IS null THEN " . Essay::CORRECTION_STATUS_NOT_POSSIBLE . "
WHEN NOT correction_finalized IS null THEN " . Essay::CORRECTION_STATUS_FINISHED . "
WHEN sn.stitch_needed = \"YES\" THEN " . Essay::CORRECTION_STATUS_STITCH_NEEDED . "
ELSE " . Essay::CORRECTION_STATUS_OPEN . "
END AS correction_status
FROM xlet_essay as essay
LEFT JOIN (" . $this->stitch_query() . ") AS sn ON (essay.id = sn.essay_id);";
}

protected function stitch_query()
{
$where = "";
return "SELECT essay.id AS essay_id,
CASE
WHEN stitch_when_distance = 1 THEN IF(ABS(MAX(csum.points) - MIN(csum.points)) > cset.max_auto_distance, \"YES\", \"NO\")
WHEN stitch_when_decimals = 1 AND NOT AVG(csum.points) IS null THEN IF(FLOOR(AVG(csum.points)) < AVG(csum.points), \"YES\", \"NO\")
ELSE \"NO\"
END AS stitch_needed
FROM xlet_essay as essay
LEFT JOIN xlet_corrector_summary AS csum ON (essay.id = csum.essay_id)
LEFT JOIN xlet_corr_setting AS cset ON (essay.task_id = cset.task_id)
" . $where . "
GROUP BY essay.id";
}
 */
