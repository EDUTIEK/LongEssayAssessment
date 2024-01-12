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
		'copy_allowed' => 'integer',
        'add_paragraph_numbers' => 'integer',
        'top_margin' => 'integer',
        'bottom_margin' => 'integer',
        'left_margin' => 'integer',
        'right_margin' => 'integer'
	];

    protected int $task_id;
    protected string $headline_scheme = self::HEADLINE_SCHEME_NONE;
    protected string $formatting_options = self::FORMATTING_OPTIONS_MEDIUM;
    protected int $notice_boards = 0;
    protected int $copy_allowed = 0;
    protected int $add_paragraph_numbers = 1;
    protected int $top_margin = 10;
    protected int $bottom_margin = 10;
    protected int $left_margin = 10;
    protected int $right_margin = 10;

	public function __construct(int $task_id)
	{
		$this->task_id = $task_id;
	}

	public static function model() {
		return new self(0);
	}

    public function getHeadlineScheme(): string
    {
        return $this->headline_scheme;
    }

    public function setHeadlineScheme(?string $headline_scheme): self
    {
        $this->headline_scheme = (string)$headline_scheme;
        return $this;
    }

    public function getFormattingOptions(): string
    {
        return $this->formatting_options;
    }

    public function setFormattingOptions(?string $formatting_options): self
    {
        $this->formatting_options = (string) $formatting_options;
        return $this;
    }

    public function getNoticeBoards(): int
    {
        return $this->notice_boards;
    }

    public function setNoticeBoards(?int $notice_boards): self
    {
        $this->notice_boards = $notice_boards;
        return $this;
    }

    public function isCopyAllowed(): bool
    {
        return (bool )$this->copy_allowed;
    }

    public function setCopyAllowed(?bool $copy_allowed): self
    {
        $this->copy_allowed = (int) $copy_allowed;
        return $this;
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function setTaskId(int $task_id): self
    {
        $this->task_id = $task_id;
        return $this;
    }

    public function getAddParagraphNumbers() : bool
    {
        return (bool) $this->add_paragraph_numbers;
    }

    public function setAddParagraphNumbers(bool $add_paragraph_numbers) : self
    {
        $this->add_paragraph_numbers = (int) $add_paragraph_numbers;
        return $this;
    }

    public function getTopMargin() : int
    {
        return $this->top_margin;
    }

    public function setTopMargin(int $top_margin) : self
    {
        $this->top_margin = $top_margin;
        return $this;
    }

    public function getBottomMargin() : int
    {
        return $this->bottom_margin;
    }

    public function setBottomMargin(int $bottom_margin) : self
    {
        $this->bottom_margin = $bottom_margin;
        return $this;
    }

    public function getLeftMargin() : int
    {
        return $this->left_margin;
    }

    public function setLeftMargin(int $left_margin) : self
    {
        $this->left_margin = $left_margin;
        return $this;
    }

    public function getRightMargin() : int
    {
        return $this->right_margin;
    }

    public function setRightMargin(int $right_margin) : self
    {
        $this->right_margin = $right_margin;
        return $this;
    }
}