<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class EditorSettings extends RecordData
{
    const HEADLINE_SCHEME_NONE = 'none';
    const HEADLINE_SCHEME_NUMERIC = 'numeric';
    const HEADLINE_SCHEME_EDUTIEK = 'edutiek';

    const FORMATTING_OPTIONS_NONE = 'none';
    const FORMATTING_OPTIONS_MINIMAL = 'minimal';
    const FORMATTING_OPTIONS_MEDIUM = 'medium';
    const FORMATTING_OPTIONS_FULL = 'full';


	protected const tableName = 'xlas_editor_settings';
	protected const hasSequence = false;
	protected const keyTypes = [
		'task_id' => 'integer',
	];
	protected const otherTypes = [
		'headline_scheme'=> 'text',
		'formatting_options' => 'text',
		'notice_boards' => 'integer',
		'copy_allowed' => 'integer'
	];

    protected int $task_id = 0;
    protected string $headline_scheme = self::HEADLINE_SCHEME_NONE;
    protected string $formatting_options = self::FORMATTING_OPTIONS_MEDIUM;
    protected int $notice_boards = 0;
    protected int $copy_allowed = 0;

	public static function model() {
		return new self();
	}

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
        $this->copy_allowed = (int)$copy_allowed;
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