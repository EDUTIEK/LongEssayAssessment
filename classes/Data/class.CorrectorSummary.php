<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorSummary extends ActivePluginRecord
{
    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_corrector_summary';

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
     * @con_fieldtype        integer
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
     * @return int
     */
    public function getPoints(): ?int
    {
        return $this->points;
    }

    /**
     * @param int $points
     * @return CorrectorSummary
     */
    public function setPoints(?int $points): CorrectorSummary
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
}