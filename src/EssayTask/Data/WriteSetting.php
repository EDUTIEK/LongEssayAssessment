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

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_write_setting')]
class WriteSetting extends \Edutiek\AssessmentService\EssayTask\Data\WriteSetting
{
    private string $headline_scheme;
    private string $formatting_options;
    private int $notice_boards;
    private int $copy_allowed;
    private int $add_paragraph_numbers;
    private int $add_correction_margin;
    private int $left_correction_margin;
    private int $right_correction_margin;
    private int $allow_spellcheck;
    #[Key]
    private int $ass_id;
    private string $writing_type;

    public function getHeadlineScheme(): string
    {
        return $this->headline_scheme;
    }
    public function setHeadlineScheme(string $headline_scheme): void
    {
        $this->headline_scheme = $headline_scheme;
    }
    public function getFormattingOptions(): string
    {
        return $this->formatting_options;
    }
    public function setFormattingOptions(string $formatting_options): void
    {
        $this->formatting_options = $formatting_options;
    }
    public function getNoticeBoards(): int
    {
        return $this->notice_boards;
    }
    public function setNoticeBoards(int $notice_boards): void
    {
        $this->notice_boards = $notice_boards;
    }
    public function getCopyAllowed(): int
    {
        return $this->copy_allowed;
    }
    public function setCopyAllowed(int $copy_allowed): void
    {
        $this->copy_allowed = $copy_allowed;
    }
    public function getAddParagraphNumbers(): int
    {
        return $this->add_paragraph_numbers;
    }
    public function setAddParagraphNumbers(int $add_paragraph_numbers): void
    {
        $this->add_paragraph_numbers = $add_paragraph_numbers;
    }
    public function getAddCorrectionMargin(): int
    {
        return $this->add_correction_margin;
    }
    public function setAddCorrectionMargin(int $add_correction_margin): void
    {
        $this->add_correction_margin = $add_correction_margin;
    }
    public function getLeftCorrectionMargin(): int
    {
        return $this->left_correction_margin;
    }
    public function setLeftCorrectionMargin(int $left_correction_margin): void
    {
        $this->left_correction_margin = $left_correction_margin;
    }
    public function getRightCorrectionMargin(): int
    {
        return $this->right_correction_margin;
    }
    public function setRightCorrectionMargin(int $right_correction_margin): void
    {
        $this->right_correction_margin = $right_correction_margin;
    }
    public function getAllowSpellcheck(): int
    {
        return $this->allow_spellcheck;
    }
    public function setAllowSpellcheck(int $allow_spellcheck): void
    {
        $this->allow_spellcheck = $allow_spellcheck;
    }
    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): void
    {
        $this->ass_id = $ass_id;
    }
    public function getWritingType(): string
    {
        return $this->writing_type;
    }
    public function setWritingType(string $writing_type): void
    {
        $this->writing_type = $writing_type;
    }
}
