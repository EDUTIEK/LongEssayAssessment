<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment;

use Edutiek\AssessmentService\Assessment\Data\ExportType;

class ExportOptionLog extends ExportOption
{
    public function getServiceExportType(): ExportType
    {
        return ExportType::LOG;
    }
}
