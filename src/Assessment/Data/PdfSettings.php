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
use Edutiek\AssessmentService\Assessment\Data\PdfFormat;
use Edutiek\AssessmentService\Assessment\Data\PdfFeedbackMode;

#[Table(name: 'xlas_as_pdf_settings')]
class PdfSettings extends \Edutiek\AssessmentService\Assessment\Data\PdfSettings
{
    #[Key]
    private int $ass_id = 0;
    private string $format = PdfFormat::EDUTIEK->value;
    private string $feedback_mode = PdfFeedbackMode::SIDE_BY_SIDE->value;

    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }

    public function getFormat(): PdfFormat
    {
        return PdfFormat::tryFrom($this->format) ?? PdfFormat::EDUTIEK;
    }

    public function setFormat(PdfFormat $format): self
    {
        $this->format = $format->value;
        return $this;
    }

    public function getFeedbackMode(): PdfFeedbackMode
    {
        return PdfFeedbackMode::tryFrom($this->feedback_mode) ?? PdfFeedbackMode::SIDE_BY_SIDE;
    }

    public function setFeedbackMode(PdfFeedbackMode $mode): self
    {
        $this->feedback_mode = $mode->value;
        return $this;
    }

}
