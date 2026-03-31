<?php

namespace ILIAS\Plugin\LongEssayAssessment\GUI\Correction;

use ILIAS\UI\Component\Symbol\Symbol;
use ILIAS\UI\Factory;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\GradingStatus;
use ILIAS\UI\Renderer;
use Edutiek\AssessmentService\System\Format\Service as SysFormService;
use Edutiek\AssessmentService\Assessment\AssessmentGrading\ReadService as GradingService;
use Edutiek\AssessmentService\Assessment\Format\Service as AssFormService;
use Edutiek\AssessmentService\Task\Format\Service as TaskFormService;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\ColumnMappingArray;
use Edutiek\AssessmentService\Assessment\Data\CombinedStatus;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\Data\CorrectionStatus;
use Closure;
use Edutiek\AssessmentService\Assessment\Data\CorrectionProcedure;

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

    /**
     * @param Closure(int $task_id, int $writer_id): string $correction_link
     */
    public function __construct(
        private \ilLanguage $lng,
        private \ilLongEssayAssessmentPlugin $plugin,
        private Factory $ui_factory,
        private \DateTimeZone $timezone,
        private GradingService $grading,
        private SysFormService $sys_format,
        private AssFormService $ass_format,
        private TaskFormService $task_format,
        private CorrectionItem $item,
        private ?Closure $correction_link = null,
    ) {
    }

    private function finalized(CorrectionItem $item)
    {
        if ($item->getWriter()->isCorrectionFinalized()) {
            return  $this->sys_format->date($item->getWriter()->getCorrectionFinalized()?->setTimezone($this->timezone))
                . ' ' . $item->getFinalizedByName()
                . ' ' . $this->ass_format->finalizedFromStatus($item->getWriter());
        }
        return '';
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

        if (str_starts_with($key, 'corr_')) {
            $pos = intval(substr($key, 5));

            return match($key) {
                "corr_{$pos}" => $item->getCorrectorDataByPosition($pos) !== null
                    ? (($item->getCorrectorDataByPosition($pos)?->getListname(true) ?? $this->unknown()) . " - "
                        . $this->task_format->correctionResult($item->getSummaryByPosition($pos), false))
                    : "",
                "corr_{$pos}_name" => $item->getCorrectorDataByPosition($pos)?->getListname(true),
                "corr_{$pos}_status" => $this->task_format->gradingStatus($item->getSummaryByPosition($pos)?->getGradingStatus(), false),
                "corr_{$pos}_points" => $item->getSummaryByPosition($pos)?->isAuthorized()
                    ? $item->getSummaryByPosition($pos)?->getEffectivePoints()
                    : null,
                "corr_{$pos}_grade" => $item->getSummaryByPosition($pos)?->isAuthorized()
                    ? $this->grading->getGradLevelForPoints($item->getSummaryByPosition($pos)?->getEffectivePoints())?->getGrade() ?? ""
                    : "",
                "corr_{$pos}_authorized" => $item->getSummaryByPosition($pos)?->isAuthorized() ?? false,
            };
        }

        return match($key) {
            "image" => $this->image(),
            'name' => isset($this->correction_link)
                ? $this->ui_factory->link()->standard(
                    $item->getWriterName() ?? $this->unknown(),
                    ($this->correction_link)($item->getTaskSettings()->getTaskId(), $item->getWriter()->getId())
                )
                : $item->getWriterName() ?? $this->unknown(),
            "login" => $item->getWriterLogin() ?? "",
            "pseudonym" => $item->getWriter()->getPseudonym(),
            "location" => $item->getLocation()?->getTitle() ?? "",
            "assessment" => $item->getAssessmentProperties()->getTitle(),
            "task" => $item->getTaskSettings()->getTitle(),
            "status" => $this->ass_format->combinedStatus($item->getWriter()),
            "writing_last_save" => $item->getEssay()?->getLastChange()?->setTimezone($this->timezone),
            "word_count" => $item->getEssay()?->getWordCount() ?? 0,
            "result" => $this->ass_format->finalResult($item->getWriter()),
            "points" => $item->getWriter()->getFinalPoints(),
            "grade" => $this->grading->getGradLevelForPoints($item->getWriter()->getFinalPoints())?->getGrade() ?? "",
            "finalized" => $this->finalized($item),
            "finalized_date" => $item->getWriter()->getCorrectionFinalized()?->setTimezone($this->timezone),
            "finalized_name" => $item->getWriter()->isCorrectionFinalized() ? ($item->getFinalizedByName() ?? "") : '',
            "finalized_from_status" => $item->getWriter()->isCorrectionFinalized() ? ($this->ass_format->finalizedFromStatus($item->getWriter())) : '',
            "pdf_version" => $item->getEssay()?->hasPdfVersion() ?? false,
            default => null
        };
    }
}
