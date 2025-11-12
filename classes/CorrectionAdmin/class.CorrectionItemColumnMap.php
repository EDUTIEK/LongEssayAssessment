<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin;

use ILIAS\UI\Component\Symbol\Symbol;
use ILIAS\UI\Factory;
use Edutiek\AssessmentService\Task\AssessmentStatus\CombinedStatus;
use Edutiek\AssessmentService\Task\Data\GradingStatus;
use ILIAS\UI\Renderer;
use Edutiek\AssessmentService\Assessment\AssessmentGrading\ReadService as GradingService;
use Edutiek\AssessmentService\Assessment\Format\Service as AssFormService;
use Edutiek\AssessmentService\Task\Format\Service as TaskFormService;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\ColumnMappingArray;

/**
 * Map from CorrectionItem to CorrectionAdminGUI table columns, which acts like an array.
 * This way each cell is renderer only if it is sorted by or visible and not all of them.every time.
 * It allso statically caches some field contents if the reoccour in the table
 */
class CorrectionItemColumnMap extends ColumnMappingArray
{
    private static array $correction_status = [];
    private static array $grading_status = [];
    private static ?string $unknown = null;

    public function __construct(
        private \ilLanguage $lng,
        private \ilPlugin $plng,
        private Factory $ui_factory,
        private \DateTimeZone $timezone,
        private GradingService $grading,
        private AssFormService $ass_format,
        private TaskFormService $task_format,
        private CorrectionItem $item
    ) {
    }

    private function correctionStatus(CombinedStatus $status): string
    {
        return self::$correction_status[$status->value] ??= match($status) {
            CombinedStatus::WRITING_NOT_STARTED => $this->plng->txt("status_writing_not_started"),
            CombinedStatus::WRITING_STARTED => $this->plng->txt("status_writing_started"),
            CombinedStatus::WRITING_EXCLUDED => $this->plng->txt("status_writing_excluded_from"),
            CombinedStatus::WRITING_AUTHORIZED => $this->plng->txt("status_writing_authorized_from"),
            CombinedStatus::STARTED => $this->plng->txt("correction_status_started"),
            CombinedStatus::APPROXIMATION => $this->plng->txt("correction_status_approximation"),
            CombinedStatus::CONSULTING => $this->plng->txt("correction_status_consulting"),
            CombinedStatus::STITCH_NEEDED => $this->plng->txt("correction_status_stitch_needed"),
            CombinedStatus::FINALIZED => $this->plng->txt("correction_finalized_from"),
        };
    }

    private function gradingStatus(?GradingStatus $status): string
    {
        return self::$grading_status[$status?->value] ??= match($status) {
            GradingStatus::OPEN => $this->plng->txt("grading_open"),
            GradingStatus::NOT_STARTED => $this->plng->txt("grading_not_started"),
            GradingStatus::PRE_GRADED => $this->plng->txt("grading_pre_graded"),
            GradingStatus::AUTHORIZED => $this->plng->txt("grading_authorized"),
            GradingStatus::REVISED => $this->plng->txt("grading_revised"),
            default => ""
        };
    }

    private function image(): Symbol
    {
        $writer_image = $this->item->getWriterImage();
        $login = $this->item->getWriterLogin() ?? "";
        if (!empty($writer_image)) {
            $avatar = $this->ui_factory->symbol()->avatar()->picture($writer_image, $login);
        } else {
            $avatar = $this->ui_factory->symbol()->avatar()->letter($login);
        }
        return $avatar;
    }

    private function unknown(): string
    {
        return self::$unknown ??= $this->lng->txt("unknown");
    }

    public function map(string $key): mixed
    {
        $item = $this->item;

        return match($key) {
            "image" => $this->image(),
            "name" => $item->getWriterName() ?? $this->unknown(),
            "login" => $item->getWriterLogin() ?? "",
            "pseudonym" => $item->getWriter()->getPseudonym(),
            "location" => $item->getLocation()?->getTitle() ?? "",
            "status" => $this->correctionStatus($item->getCorrectionStatus()),
            "writing_last_save" => $item->getEssay()?->getLastChange()?->setTimezone($this->timezone),
            "word_count" => $item->getEssay()?->getWordCount() ?? 0,
            "result" => $this->ass_format->finalResult($item->getWriter()),
            "points" => $item->getWriter()->getFinalPoints(),
            "grade" => $this->grading->getGradeLevel($item->getWriter()->getFinalGradeLevelId()) ?? "",
            "finalized" => $item->getWriter()->getCorrectionFinalized()?->setTimezone($this->timezone),
            "finalized_from" => $item->getFinalizedByName() ?? $this->unknown(),
            "stitch_needed" => $item->isStitchNeeded(),
            "pdf_version" => $item->getEssay()?->hasPDFVersion() ?? false,

            "corr_0" => $item->getCorrectorDataByPosition(0) !== null
                ? (($item->getCorrectorDataByPosition(0)?->getFullname(true) ?? $this->unknown()) . " - " . $this->task_format->correctionResult($item->getSummaryByPosition(0)))
                : "",
            "corr_0_name" => $item->getCorrectorDataByPosition(0)?->getFullname(true),
            "corr_0_status" => $this->gradingStatus($item->getSummaryByPosition(0)?->getGradingStatus()),
            "corr_0_points" => $item->getSummaryByPosition(0)?->getPoints(),
            "corr_0_grade" => $this->grading->getGradLevelForPoints($item->getSummaryByPosition(0)?->getPoints()) ?? "",
            "corr_0_authorized" => $item->getSummaryByPosition(0)?->isAuthorized() ?? false,
            "corr_1" => $item->getCorrectorDataByPosition(1) !== null
                ? (($item->getCorrectorDataByPosition(1)?->getFullname(true) ?? $this->unknown()) . " - " . $this->task_format->correctionResult($item->getSummaryByPosition(1)))
                : "",
            "corr_1_name" => $item->getCorrectorDataByPosition(1)?->getFullname(true),
            "corr_1_status" => $this->gradingStatus($item->getSummaryByPosition(1)?->getGradingStatus()),
            "corr_1_points" => $item->getSummaryByPosition(1)?->getPoints(),
            "corr_1_grade" => $this->grading->getGradLevelForPoints($item->getSummaryByPosition(1)?->getPoints()) ?? "",
            "corr_1_authorized" => $item->getSummaryByPosition(1)?->isAuthorized() ?? false,
            default => null
        };//explicit corrector for more calculation speed
    }
}
