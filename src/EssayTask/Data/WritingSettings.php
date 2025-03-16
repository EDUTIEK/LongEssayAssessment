<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\EssayTask\Data;

use Edutiek\AssessmentService\EssayTask\Data\HeadlineScheme;
use Edutiek\AssessmentService\EssayTask\Data\WritingType;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_write_settings')]
class WritingSettings extends \Edutiek\AssessmentService\EssayTask\Data\WritingSettings
{
    private string $headline_scheme = '';
    private string $formatting_options = '';
    private int $notice_boards = 0;
    private int $copy_allowed = 0;
    private int $add_paragraph_numbers = 0;
    private int $add_correction_margin = 0;
    private int $left_correction_margin = 0;
    private int $right_correction_margin = 0;
    private int $allow_spellcheck = 0;
    #[Key]
    private int $ass_id = 0;
    private string $writing_type = '';

    public function getHeadlineScheme(): HeadlineScheme
    {
        return HeadlineScheme::tryFrom($this->headline_scheme) ?? HeadlineScheme::NUMERIC;
    }
    public function setHeadlineScheme(HeadlineScheme $headline_scheme): self
    {
        $this->headline_scheme = $headline_scheme->value;
        return $this;
    }
    public function getFormattingOptions(): string
    {
        return $this->formatting_options;
    }
    public function setFormattingOptions(string $formatting_options): self
    {
        $this->formatting_options = $formatting_options;
        return $this;
    }
    public function getNoticeBoards(): int
    {
        return $this->notice_boards;
    }
    public function setNoticeBoards(int $notice_boards): self
    {
        $this->notice_boards = $notice_boards;
        return $this;
    }
    public function getCopyAllowed(): int
    {
        return $this->copy_allowed;
    }
    public function setCopyAllowed(int $copy_allowed): self
    {
        $this->copy_allowed = $copy_allowed;
        return $this;
    }
    public function getAddParagraphNumbers(): int
    {
        return $this->add_paragraph_numbers;
    }
    public function setAddParagraphNumbers(int $add_paragraph_numbers): self
    {
        $this->add_paragraph_numbers = $add_paragraph_numbers;
        return $this;
    }
    public function getAddCorrectionMargin(): int
    {
        return $this->add_correction_margin;
    }
    public function setAddCorrectionMargin(int $add_correction_margin): self
    {
        $this->add_correction_margin = $add_correction_margin;
        return $this;
    }
    public function getLeftCorrectionMargin(): int
    {
        return $this->left_correction_margin;
    }
    public function setLeftCorrectionMargin(int $left_correction_margin): self
    {
        $this->left_correction_margin = $left_correction_margin;
        return $this;
    }
    public function getRightCorrectionMargin(): int
    {
        return $this->right_correction_margin;
    }
    public function setRightCorrectionMargin(int $right_correction_margin): self
    {
        $this->right_correction_margin = $right_correction_margin;
        return $this;
    }
    public function getAllowSpellcheck(): int
    {
        return $this->allow_spellcheck;
    }
    public function setAllowSpellcheck(int $allow_spellcheck): self
    {
        $this->allow_spellcheck = $allow_spellcheck;
        return $this;
    }
    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }
    public function getWritingType(): WritingType
    {
        return WritingType::tryFrom($this->writing_type) ?? WritingType::ESSAY_EDITOR;
    }
    public function setWritingType(WritingType $writing_type): self
    {
        $this->writing_type = $writing_type->value;
        return $this;
    }
}
