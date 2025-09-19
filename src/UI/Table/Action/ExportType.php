<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ILIAS\FileUpload\MimeType;

enum ExportType
{
    case CSV;
    case EXCEL;

    public function extension(): string
    {
        return match($this) {
            self::CSV => '.csv',
            self::EXCEL => '.xlsx'
        };
    }

    public function mimetype(): string
    {
        return match($this) {
            self::CSV => 'text/csv',
            self::EXCEL => MimeType::APPLICATION__EXCEL
        };
    }
}
