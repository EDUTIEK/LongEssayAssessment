<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data;


/**
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class EditorSettings extends ActivePluginRecord
{
    const HEADLINE_SCHEME_NONE = 'none';
    const HEADLINE_SCHEME_NUMERIC = 'numeric';
    const HEADLINE_SCHEME_EDUTIEK = 'edutiek';

    const FORMATTING_OPTIONS_NONE = 'none';
    const FORMATTING_OPTIONS_MINIMAL = 'minimal';
    const FORMATTING_OPTIONS_MEDIUM = 'medium';
    const FORMATTING_OPTIONS_FULL = 'full';


    /**
     * @var string
     */
    protected $connector_container_name = 'xlas_editor_settings';


    /**
     * The task_id currently corresponds to the obj_id of the ILIAS object
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $task_id = 0;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           50
     */
    protected $headline_scheme = self::HEADLINE_SCHEME_NONE;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           50
     */
    protected $formatting_options = self::FORMATTING_OPTIONS_MEDIUM;


    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $notice_boards = 0;

    /**
     * @var bool
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $copy_allowed = false;

    /**
     * @return string
     */
    public function getHeadlineScheme(): string
    {
        return (string)$this->headline_scheme;
    }

    /**
     * @param ?string $headline_scheme
     */
    public function setHeadlineScheme(?string $headline_scheme): void
    {
        $this->headline_scheme = (string)$headline_scheme;
    }

    /**
     * @return string
     */
    public function getFormattingOptions(): string
    {
        return (string)$this->formatting_options;
    }

    /**
     * @param ?string $formatting_options
     */
    public function setFormattingOptions(?string $formatting_options): void
    {
        $this->formatting_options = (string)$formatting_options;
    }

    /**
     * @return int
     */
    public function getNoticeBoards(): int
    {
        return (int)$this->notice_boards;
    }

    /**
     * @param ?int $notice_boards
     */
    public function setNoticeBoards(?int $notice_boards): void
    {
        $this->notice_boards = $notice_boards;
    }

    /**
     * @return bool
     */
    public function isCopyAllowed(): bool
    {
        return (bool)$this->copy_allowed;
    }

    /**
     * @param ?bool $copy_allowed
     */
    public function setCopyAllowed(?bool $copy_allowed): void
    {
        $this->copy_allowed = (bool)$copy_allowed;
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
     * @return EditorSettings
     */
    public function setTaskId(int $task_id): EditorSettings
    {
        $this->task_id = $task_id;
        return $this;
    }
}