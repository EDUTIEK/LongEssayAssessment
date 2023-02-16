<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Plugin\LongEssayAssessment\Data\ActivePluginRecord;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorSummary extends ActivePluginRecord
{
	const STATUS_BLOCKED= "blocked";
	const STATUS_DUE = "due";
	const STATUS_STARTED = "started";
	const STATUS_AUTHORIZED = "authorized";
	const STATUS_STITCH = "stitch";

    /**
     * @var string
     */
    protected $connector_container_name = 'xlas_corrector_summary';

    /**
     * Editor notice id
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
     * The Essay Id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $essay_id;

    /**
     * The Corrector Id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $corrector_id;

    /**
     * Summary Text (richtext)
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $summary_text = null;

    /**
     * @var int
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        float
     * @con_length           4
     */
    protected $points = 0;


    /**
     * Id of the grade level
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $grade_level_id = null;

    /**
     * Last change at the correction
     *
     * @var string|null
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $last_change = null;


    /**
     * Correction authorized (datetime)
     *
     * @var string|null
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    protected $correction_authorized = null;


    /**
     * ILIAS user id of the user that has finalized the correction
     * (this may be the corrector or an admin)
     *
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $correction_authorized_by = null;


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
}