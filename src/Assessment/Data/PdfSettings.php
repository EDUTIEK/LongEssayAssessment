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

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_pdf_settings')]
class PdfSettings extends \Edutiek\AssessmentService\Assessment\Data\PdfSettings
{
    private bool $add_header = false;
    private bool $add_footer = false;
    private int $top_margin = 0;
    private int $bottom_margin = 0;
    private int $left_margin = 0;
    private int $right_margin = 0;
    #[Key]
    private int $ass_id = 0;

    public function getAddHeader(): bool
    {
        return $this->add_header;
    }
    public function setAddHeader(bool $add_header): self
    {
        $this->add_header = $add_header;
        return $this;
    }
    public function getAddFooter(): bool
    {
        return $this->add_footer;
    }
    public function setAddFooter(bool $add_footer): self
    {
        $this->add_footer = $add_footer;
        return $this;
    }
    public function getTopMargin(): int
    {
        return $this->top_margin;
    }
    public function setTopMargin(int $top_margin): self
    {
        $this->top_margin = $top_margin;
        return $this;
    }
    public function getBottomMargin(): int
    {
        return $this->bottom_margin;
    }
    public function setBottomMargin(int $bottom_margin): self
    {
        $this->bottom_margin = $bottom_margin;
        return $this;
    }
    public function getLeftMargin(): int
    {
        return $this->left_margin;
    }
    public function setLeftMargin(int $left_margin): self
    {
        $this->left_margin = $left_margin;
        return $this;
    }
    public function getRightMargin(): int
    {
        return $this->right_margin;
    }
    public function setRightMargin(int $right_margin): self
    {
        $this->right_margin = $right_margin;
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
}
