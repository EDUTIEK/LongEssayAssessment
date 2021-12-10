<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

use ILIAS\Data\UUID\Factory as UUID;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class Essay extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_essay';

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
     * Rawtext Hash (sha1)
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
     * Is Authorized (bool)
     *
     * @var bool
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $is_authorized = 0;


    /**
     * PDF version as il file id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $pdf_version;


    /**
     * Final Points (integer)
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $final_points;


    /**
     * Final Grade Level (text)
     *
     * @var null|string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           255
     */
    protected $final_grade_level = null;

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
     * @return bool
     */
    public function isIsAuthorized()
    {
        return $this->is_authorized;
    }

    /**
     * @param bool $is_authorized
     * @return Essay
     */
    public function setIsAuthorized($is_authorized)
    {
        $this->is_authorized = $is_authorized;
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
     * @return int
     */
    public function getFinalPoints(): int
    {
        return $this->final_points;
    }

    /**
     * @param int $final_points
     * @return Essay
     */
    public function setFinalPoints(int $final_points): Essay
    {
        $this->final_points = $final_points;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFinalGradeLevel(): ?string
    {
        return $this->final_grade_level;
    }

    /**
     * @param string|null $final_grade_level
     * @return Essay
     */
    public function setFinalGradeLevel(?string $final_grade_level): Essay
    {
        $this->final_grade_level = $final_grade_level;
        return $this;
    }

    public function generateUUID4(): string
    {
        return (new UUID())->uuid4AsString();
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
}