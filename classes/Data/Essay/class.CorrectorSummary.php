<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorSummary extends RecordData
{
	const STATUS_BLOCKED= "blocked";
	const STATUS_DUE = "due";
	const STATUS_STARTED = "started";
	const STATUS_AUTHORIZED = "authorized";
	const STATUS_STITCH = "stitch";

    const INCLUDE_NOT = 0;          // don't conclude to documentation
    const INCLUDE_INFO = 1;         // include to documentation as info
    const INCLUDE_RELEVANT = 2;     // include to documentation as relevant for the result

    protected const tableName = 'xlas_corrector_summary';
	protected const hasSequence = true;
	protected const keyTypes = [
		'id' => 'integer',
	];
	protected const otherTypes = [
		'essay_id' => 'integer',
		'corrector_id' => 'integer',
		'summary_text' => 'text',
		'points' => 'float',
		'grade_level_id' => 'integer',
		'last_change' => 'datetime',
		'correction_authorized' => 'datetime',
		'correction_authorized_by' => 'integer',
        'include_comments' => 'integer',
        'include_comment_ratings' => 'integer',
        'include_comment_points' => 'integer',
        'include_criteria_points' => 'integer',
        'include_writer_notes' => 'integer',
	];
    
    protected int $id = 0;
    protected int $essay_id = 0;
    protected int $corrector_id = 0;
    protected ?string $summary_text = null;
    protected ?float $points = null;
    protected ?int $grade_level_id = null;
    protected ?string $last_change = null;
    protected ?string $correction_authorized = null;
    protected ?int $correction_authorized_by = null;
    protected ?int $include_comments = null;
    protected ?int $include_comment_ratings = null;
    protected ?int $include_comment_points = null;
    protected ?int $include_criteria_points = null;
    protected ?int $include_writer_notes = null;


    public static function model() {
		return new self();
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
     * @return CorrectorSummary
     */
    public function setId(int $id): CorrectorSummary
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getEssayId(): int
    {
        return $this->essay_id;
    }

    /**
     * @param int $essay_id
     * @return CorrectorSummary
     */
    public function setEssayId(int $essay_id): CorrectorSummary
    {
        $this->essay_id = $essay_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }

    /**
     * @param int $corrector_id
     * @return CorrectorSummary
     */
    public function setCorrectorId(int $corrector_id): CorrectorSummary
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSummaryText(): ?string
    {
        return $this->summary_text;
    }

    /**
     * @param string|null $summary_text
     * @return CorrectorSummary
     */
    public function setSummaryText(?string $summary_text): CorrectorSummary
    {
        $this->summary_text = $summary_text;
        return $this;
    }

    /**
     * @return float
     */
    public function getPoints(): ?float
    {
        return $this->points;
    }

    /**
     * @param float $points
     * @return CorrectorSummary
     */
    public function setPoints(?float $points): CorrectorSummary
    {
        $this->points = $points;
        return $this;
    }

    /**
     * @return ?int
     */
    public function getGradeLevelId(): ?int
    {
        return $this->grade_level_id;
    }

    /**
     * @param ?int $grade_level_id
     * @return CorrectorSummary
     */
    public function setGradeLevelId(?int $grade_level_id): CorrectorSummary
    {
        $this->grade_level_id = $grade_level_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastChange(): ?string
    {
        return $this->last_change;
    }

    /**
     * @param string|null $last_change
     * @return CorrectorSummary
     */
    public function setLastChange(?string $last_change): CorrectorSummary
    {
        $this->last_change = $last_change;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCorrectionAuthorized(): ?string
    {
        return $this->correction_authorized;
    }

    /**
     * @param string|null $correction_authorized
     * @return CorrectorSummary
     */
    public function setCorrectionAuthorized(?string $correction_authorized): CorrectorSummary
    {
        $this->correction_authorized = $correction_authorized;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCorrectionAuthorizedBy(): ?int
    {
        return $this->correction_authorized_by;
    }

    /**
     * @param int|null $correction_authorized_by
     * @return CorrectorSummary
     */
    public function setCorrectionAuthorizedBy(?int $correction_authorized_by): CorrectorSummary
    {
        $this->correction_authorized_by = $correction_authorized_by;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getIncludeComments(): ?int
    {
        return $this->include_comments;
    }

    /**
     * @param int|null $include_comments
     */
    public function setIncludeComments(?int $include_comments): void
    {
        $this->include_comments = $include_comments;
    }

    /**
     * @return int|null
     */
    public function getIncludeCommentRatings(): ?int
    {
        return $this->include_comment_ratings;
    }

    /**
     * @param int|null $include_comment_ratings
     */
    public function setIncludeCommentRatings(?int $include_comment_ratings): void
    {
        $this->include_comment_ratings = $include_comment_ratings;
    }

    /**
     * @return int|null
     */
    public function getIncludeCommentPoints(): ?int
    {
        return$this->include_comment_points;
    }

    /**
     * @param int|null $include_comment_points
     */
    public function setIncludeCommentPoints(?int $include_comment_points): void
    {
        $this->include_comment_points = $include_comment_points;
    }

    /**
     * @return int|null
     */
    public function getIncludeCriteriaPoints(): ?int
    {
        return $this->include_criteria_points;
    }

    /**
     * @param int|null $include_criteria_points
     */
    public function setIncludeCriteriaPoints(?int $include_criteria_points): void
    {
        $this->include_criteria_points = $include_criteria_points;
    }


    /**
     * @return int|null
     */
    public function getIncludeWriterNotes(): ?int
    {
        return $this->include_writer_notes;
    }

    /**
     * @param int|null $include_writer_notes
     */
    public function setIncludeWriterNotes(?int $include_writer_notes): void
    {
        $this->include_writer_notes = $include_writer_notes;
    }
}

/**
SELECT cs.id as id, cs.corrector_id as corrector_id, cs.essay_id as essay_id,
CASE
WHEN NOT cs.correction_authorized IS null THEN "STATUS_AUTHORIZED"
WHEN NOT cs.id IS null AND NOT cs.last_change IS null THEN "STATUS_STARTED"
WHEN ca.position = 0 OR NOT predecessor_cs.correction_authorized IS null THEN "STATUS_DUE"
WHEN predecessor_cs.correction_authorized IS null  THEN "STATUS_BLOCKED"
ELSE "STATUS_UNKNOWN"
END as CORRECTION_STATUS
FROM xlet_corrector_summary AS cs
LEFT JOIN xlet_essay AS essay ON (essay.id = cs.essay_id)
LEFT JOIN xlet_corrector_ass AS ca ON (cs.corrector_id = ca.corrector_id AND essay.writer_id = ca.writer_id)
LEFT JOIN xlet_corrector_ass AS predecessor_ca ON (essay.writer_id = predecessor_ca.writer_id AND predecessor_ca.position = ca.position-1)
LEFT JOIN xlet_corrector_summary as predecessor_cs ON (essay.id = predecessor_cs.essay_id AND predecessor_cs.corrector_id = predecessor_ca.corrector_id);
 *
 *
SELECT cs.id, essay.id, ca.corrector_id, ca.writer_id,
CASE
WHEN NOT cs.correction_authorized IS null THEN "STATUS_AUTHORIZED"
WHEN NOT cs.id IS null AND NOT cs.last_change IS null THEN "STATUS_STARTED"
WHEN ca.position = 0 OR NOT predecessor_cs.correction_authorized IS null THEN "STATUS_DUE"
WHEN predecessor_cs.correction_authorized IS null  THEN "STATUS_BLOCKED"
ELSE "STATUS_UNKNOWN"
END as correction_status
FROM xlet_corrector_ass AS ca
LEFT JOIN xlet_essay AS essay ON (ca.writer_id = essay.writer_id)
LEFT JOIN xlet_corrector_summary AS cs ON (ca.corrector_id = cs.corrector_id AND essay.id = cs.essay_id)
LEFT JOIN xlet_corrector_ass AS predecessor_ca ON (essay.writer_id = predecessor_ca.writer_id AND predecessor_ca.position = ca.position-1)
LEFT JOIN xlet_corrector_summary as predecessor_cs ON (essay.id = predecessor_cs.essay_id AND predecessor_cs.corrector_id = predecessor_ca.corrector_id);
 */