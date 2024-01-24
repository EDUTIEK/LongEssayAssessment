<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class PdfSettings extends RecordData
{
	protected const tableName = 'xlas_pdf_settings';
	protected const hasSequence = false;
	protected const keyTypes = [
		'task_id' => 'integer',
	];
	protected const otherTypes = [
		'add_header' => 'integer',
        'add_footer' => 'integer',
        'top_margin' => 'integer',
        'bottom_margin' => 'integer',
        'left_margin' => 'integer',
        'right_margin' => 'integer'
	];

    protected int $task_id;
    protected int $add_header = 1;
    protected int $add_footer = 1;
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

    public function getAddHeader() : bool
    {
        return (bool) $this->add_header;
    }

    public function setAddHeader(bool $add_header) : self
    {
        $this->add_header = (int) $add_header;
        return $this;
    }

    public function getAddFooter() : bool
    {
        return (bool) $this->add_footer;
    }

    public function setAddFooter(bool $add_footer) : self
    {
        $this->add_footer = (int) $add_footer;
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