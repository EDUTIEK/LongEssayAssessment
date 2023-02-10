<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data;

use ILIAS\Data\UUID\Factory as UUID;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Essay extends ActivePluginRecord
{
	const WRITING_STATUS_NOT_WRITTEN = 'writing_status_not_written';
	const WRITING_STATUS_NOT_AUTHORIZED = 'writing_status_not_authorized';
	const WRITING_STATUS_EXCLUDED = 'writing_excluded';
	const WRITING_STATUS_AUTHORIZED = 'writing_status_authorized';

	const CORRECTION_STATUS_NOT_POSSIBLE = 'correction_status_not_possible';
	const CORRECTION_STATUS_FINISHED = 'correction_status_finished';
	const CORRECTION_STATUS_STITCH_NEEDED = 'correction_status_stitch_needed';
	const CORRECTION_STATUS_OPEN = 'correction_status_open';

    /**
     * @var string
     */
    protected $connector_container_name = 'xlas_essay';

    /**
     * Essay id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_sequence         true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $id;

    /**
     * UUID
     *
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           50
     */
    protected $uuid;

    /**
     * The writer id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $writer_id;

    /**
     * The task id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $task_id;

    /**
     * Written Text (richtext)
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $written_text = null;

    /**
     * Rawtext Hash
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           50
     */
    protected $raw_text_hash = null;


    /**
     * Edit Started (datetime)
     *
     * @var string|null
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $edit_started = null;


    /**
     * Edit Ended (datetime)
     *
     * @var string|null
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $edit_ended = null;


    /**
     * Processed Text (html)
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $processed_text = null;


    /**
     * Writing authorized (datetime)
     *
     * @var string|null
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $writing_authorized = null;


    /**
     * ILIAS user id of the user that has authorized the written text
     * (this may be the writer or an admin)
     *
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $writing_authorized_by = null;

	/**
	 * Writing excluded (datetime)
	 *
	 * @var string|null
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        timestamp
	 */
	protected $writing_excluded = null;


	/**
	 * ILIAS user id of the user that has excluded the written text
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected $writing_excluded_by = null;

    /**
     * PDF version as il file id
     *
     * @var null|integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $pdf_version;

    /**
     * Correction finalized (datetime)
     *
     * @var string|null
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $correction_finalized = null;


    /**
     * ILIAS user id of the user that has finalized the correction
     * (this may be a corrector or an admin)
     *
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $correction_finalized_by = null;


    /**
     * Final Points (float)
     *
     * @var null|float
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       false
     * @con_fieldtype        float
     * @con_length           4
     */
    protected $final_points;


    /**
     * Final Grade Level (id)
     *
     * @var null|integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $final_grade_level_id = null;


    /**
     * Comment from a stitch decision
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $stitch_comment = null;


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
    public function getProcessedText(): ?string
    {
        return $this->processed_text;
    }

    /**
     * @param string|null $processed_text
     * @return Essay
     */
    public function setProcessedText(?string $processed_text): Essay
    {
        $this->processed_text = $processed_text;
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
    public function getPdfVersion(): int
    {
        return $this->pdf_version;
    }

    /**
     * @param int $pdf_version
     * @return Essay
     */
    public function setPdfVersion(int $pdf_version): Essay
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
}