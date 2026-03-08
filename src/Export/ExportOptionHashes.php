<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment;

use Edutiek\AssessmentService\Assessment\Data\ExportType;

class ExportOptionHashes extends ExportOption
{
    public function getServiceExportType(): ExportType
    {
        return ExportType::HASHES;
    }

    /**
     * Label in the Export dropdown
     * Prevent this option in the dropdown by returning an empty label
     */
    public function getLabel(): string
    {
        return  '';
    }

}
