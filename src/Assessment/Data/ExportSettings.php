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
use Edutiek\AssessmentService\Assessment\Data\ResultExportFormat;

#[Table(name: 'xlas_as_exp_settings')]
class ExportSettings extends \Edutiek\AssessmentService\Assessment\Data\ExportSettings
{
    #[Key]
    private int $ass_id = 0;
    private string $result_export_format = ResultExportFormat::EDUTIEK->value;

    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }

    public function getResultExportFormat(): ResultExportFormat
    {
        return ResultExportFormat::tryFrom($this->result_export_format) ?? ResultExportFormat::EDUTIEK;
    }

    public function setResultExportFormat(ResultExportFormat $format): self
    {
        $this->result_export_format = $format->value;
        return $this;
    }
}
