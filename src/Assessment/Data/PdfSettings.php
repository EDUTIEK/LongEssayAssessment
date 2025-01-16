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

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_pdf_settings')]
class PdfSettings extends \Edutiek\AssessmentService\Assessment\Data\PdfSettings
{
    private int $add_header = 0;
    private int $add_footer = 0;
    private int $top_margin = 0;
    private int $bottom_margin = 0;
    private int $left_margin = 0;
    private int $right_margin = 0;
    #[Key]
    private int $ass_id = 0;

    public function getAddHeader(): int
    {
        return $this->add_header;
    }
    public function setAddHeader(int $add_header): void
    {
        $this->add_header = $add_header;
    }
    public function getAddFooter(): int
    {
        return $this->add_footer;
    }
    public function setAddFooter(int $add_footer): void
    {
        $this->add_footer = $add_footer;
    }
    public function getTopMargin(): int
    {
        return $this->top_margin;
    }
    public function setTopMargin(int $top_margin): void
    {
        $this->top_margin = $top_margin;
    }
    public function getBottomMargin(): int
    {
        return $this->bottom_margin;
    }
    public function setBottomMargin(int $bottom_margin): void
    {
        $this->bottom_margin = $bottom_margin;
    }
    public function getLeftMargin(): int
    {
        return $this->left_margin;
    }
    public function setLeftMargin(int $left_margin): void
    {
        $this->left_margin = $left_margin;
    }
    public function getRightMargin(): int
    {
        return $this->right_margin;
    }
    public function setRightMargin(int $right_margin): void
    {
        $this->right_margin = $right_margin;
    }
    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): void
    {
        $this->ass_id = $ass_id;
    }
}
