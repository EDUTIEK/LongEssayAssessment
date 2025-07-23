<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Protocol;

use Edutiek\AssessmentService\Assessment\Data\LogEntry;
use Edutiek\AssessmentService\Assessment\Data\Alert;
use Edutiek\AssessmentService\Assessment\LogEntry\Category as LogEntryCategory;

enum EntryType : string
{
    case ALL = "all";
    case AUTHORIZE = "authorize";
    case NOTE = "note";
    case WORKING_TIME = "working_time";
    case EXCLUSION = "exclusion";

    case ALERT = "alert";

    public static function fromCategory(LogEntryCategory $category) : self
    {
        return self::from($category->value);
    }
}
