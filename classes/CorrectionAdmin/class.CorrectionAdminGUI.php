<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Test\Participants\TableAction;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\FilterParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use Edutiek\AssessmentService\Task\Data\Settings;
use Edutiek\AssessmentService\Task\Settings\FullService as SettingsService;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Task\AssessmentStatus\CorrectionStatus;
use Edutiek\AssessmentService\Task\Data\GradingStatus;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaService;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use Edutiek\AssessmentService\EssayTask\Essay\FullService as EssayService;
use Edutiek\AssessmentService\Task\AssessmentStatus\FullService as AssessmentStatus;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Task\CorrectorSummary\FullService as SummaryService;
use Edutiek\AssessmentService\Task\CorrectorAssignments\FullService as CorrectorAssignmentsService;
use Edutiek\AssessmentService\Assessment\Corrector\FullService as CorrectorService;
use Edutiek\AssessmentService\Assessment\Data\Location;
use Edutiek\AssessmentService\Assessment\AssessmentGrading\ReadService as GradingService;
use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings;

/**
 * Correction Admin GUI class
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin\CorrectionAdminGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectionAdminGUI extends BaseGUI implements DataTableParent, FilterParent
{
    use ConfirmationIds;

    const FILTER_YES= "1";
    const FILTER_NO = "2";
    private OrgaSettings $settings;
    private WriterService $writer_service;
    private UserService $user_service;
    private ?array $location = null;
    private EssayService $essay_service;
    private AssessmentStatus $assessment_status;
    private SummaryService $summary_service;
    private CorrectorAssignmentsService $assignment_service;
    private CorrectorService $corrector_service;
    private GradingService $grading_service;
    private CorrectionSettings $correction_settings;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->initForTask();

        $this->writer_service = $this->assessment_api->writer();
        $this->user_service = $this->system_api->user();
        $this->essay_service = $this->essay_task_api->essay();
        $this->assessment_status = $this->task_api->assessmentStatus();
        $this->summary_service = $this->task_api->summary($this->task_info->getId());
        $this->assignment_service = $this->task_api->correctorAssignments();
        $this->corrector_service = $this->assessment_api->corrector();
        $this->grading_service = $this->assessment_api->assessment_grading();
    }

    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                $cmd = $this->ctrl->getCmd('showItems');
                switch ($cmd) {
                    case 'showItems':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
                break;
        }
    }
    public function showItems()
    {
        $this->buildToolbar($this->toolbar);

        $table = $this->plugin_ui_factory->table()->dataTable('correction_admin_table', $this);
        $table->executeAction();
        $this->tpl->setContent($this->renderer->render($table));
    }

    public function buildToolbar(\ilToolbarGUI $toolbar)
    {
        $authorized_essay_exists = false;

        if ($authorized_essay_exists) {
            if (empty($correctors)) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt('info_missing_correctors'), false);
            } elseif(!empty($this->assignment_service->countMissingCorrectors())) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt('info_missing_assignments'), false);
            }
        }

        $toolbar->setFormAction($this->ctrl->getFormAction($this));

        $this->toolbar->addComponent($this->ui_factory->button()->primary(
            $this->plugin->txt('assign_writers'),
            $this->ctrl->getLinkTarget($this, "confirmAssignWriters")
        ));

        //Todo: use table actions
//        $this->toolbar->addComponent($this->ui_factory->button()->standard(
//            $this->plugin->txt("assignment_excel_export"),
//            $this->ctrl->getLinkTarget($this, "correctorAssignmentSpreadsheetExport")
//        ));

        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->plugin->txt("assignment_excel_import"),
            $this->ctrl->getLinkTarget($this, "correctorAssignmentSpreadsheetImport")
        ));

//        $this->toolbar->addComponent($this->ui_factory->button()->toggle(
//            $this->plugin->txt("assignment_excel_export_auth"),
//            "#",
//            "#",
//            $this->spreadsheetAssignmentToggle()
//        )->withAdditionalOnLoadCode(
//            function ($id) {
//                return "$('#{$id}').on( 'click', function() {  document.cookie = 'xlas_exass=' + ($( this ).hasClass('on') ? 'on' : 'off'); } );";
//            }
//        ));

        $this->toolbar->addSeparator();

        if ($this->getCorrectionSettings()->isStitchPossible()) {
            $toolbar->addComponent($this->ui_factory->button()->standard(
                $this->plugin->txt("do_stich_decision"),
                $this->ctrl->getLinkTarget($this, "stitchDecision")
            )->withUnavailableAction($this->writer_service->hasStitchDecisions()));
        }

        $toolbar->addComponent($this->ui_factory->button()->standard(
            $this->plugin->txt("export_corrections"),
            $this->ctrl->getLinkTarget($this, "exportCorrections")
        ));

        $toolbar->addComponent($this->ui_factory->button()->standard(
            $this->plugin->txt("export_results"),
            $this->ctrl->getLinkTarget($this, "exportResults")
        ));

        $toolbar->addSeparator();

        if ($this->getCorrectionSettings()->getReportsEnabled()) {
            $toolbar->addComponent($this->ui_factory->button()->standard(
                $this->plugin->txt("download_correction_reports"),
                $this->ctrl->getLinkTarget($this, "downloadReportsPdf")
            ));
        }
    }

    public function getColumnMapping(
        CorrectionItem|\ILIAS\Plugin\LongEssayAssessment\UI\Table\Item $item,
        ?array $additional_parameters
    ): \ArrayAccess {
        return new CorrectionItemColumnMap(
            $this->lng,
            $this->plugin,
            $this->ui_factory,
            new \DateTimeZone($this->user->getTimeZone()),
            $this->grading_service,
            $this->assessment_api->format($this->getSettings()),
            $this->task_api->format(),
            $item
        );
    }

    public function getColumns(?array $additional_parameters): array
    {
        $cf = $this->ui_factory->table()->column();
        $cfp = $this->plugin_ui_factory->table()->column();
        $settings = $this->getSettings();

        $df = new \ILIAS\Data\Factory();
        $location_avaiable = $this->hasLocations();
        $duration_avaiable = !empty($settings->getWritingLimitMinutes());
        $has_started = $settings->getWritingStart() !== null ? $settings->getWritingStart() < new \DateTimeImmutable() : true;
        $date_without_seconds = $this->user->getDateTimeFormat();
        $date_with_seconds = $df->dateFormat()->amend($date_without_seconds)->colon()->seconds()->get();
        if (!empty($settings->getWritingLimitMinutes())) {
            $long_exam = $settings->getWritingLimitMinutes() > 1440;
        } elseif ($settings->getWritingStart() !== null) {
            $long_exam = date_diff($settings->getWritingStart(), $working_end ?? new \DateTimeImmutable('now'))->d > 0;
        } else {
            $long_exam = true;
        }

        $corrections = $this->getCorrectionSettings()->getRequiredCorrectors();
        $multi = $this->getSettings()->getMultiTasks();
        $stitch_possible = $this->getCorrectionSettings()->isStitchPossible();

        $columns = [
            "image" => $cfp->image($this->lng->txt("image"))->withIsOptional(true, false)->withIsSortable(false),
            "name" => $cf->text($this->lng->txt("name"))->withIsOptional(false, true)->withIsSortable(true),
            "login" => $cf->text($this->lng->txt("login"))->withIsOptional(false, true)->withIsSortable(true),
            "pseudonym" => $cf->text($this->plugin->txt("pseudonym"))->withIsOptional(true, true)->withIsSortable(true),
            "location" => $location_avaiable ? $cf->text($this->plugin->txt("location"))->withIsOptional(true, $location_avaiable)->withIsSortable(true) : null,
            "status" => $cf->status($this->plugin->txt("essay_status"))->withIsOptional(true, true)->withIsSortable(true),
            "writing_last_save" => $cfp->nullableDate($this->plugin->txt("writing_last_save"), $date_with_seconds)->withIsOptional(true, $has_started)->withIsSortable(true)->withHighlight($multi),
            "word_count" => $cf->number($this->plugin->txt('word_count'))->withIsOptional(false, $has_started)->withIsSortable(true)->withHighlight($multi),
            "pdf_version" => $cf->boolean($this->plugin->txt("pdf_version"), $this->lng->txt("yes"), $this->lng->txt("no"))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi)
        ];

        foreach(range(0,$corrections-1) as $p)
        {
            if ($corrections == 1) {
                $cor = $this->plugin->txt("assignment_pos_single");
            } else {
                switch($p) {
                    case 0: $cor = $this->plugin->txt("assignment_pos_first");
                        break;
                    case 1: $cor = $this->plugin->txt("assignment_pos_second");
                        break;
                    default: $cor = $this->plugin->txt("assignment_pos_other");
                        break;
                }
            }
            $cor = $cor . " ";
            $columns += [
                "corr_{$p}" => $cf->text($cor)->withIsOptional(false, true)->withIsSortable(true)->withIsSortable(false)->withHighlight($multi),
                "corr_{$p}_name" => $cf->text($cor. $this->lng->txt("name"))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi),
                "corr_{$p}_status" => $cf->status($cor . $this->plugin->txt("status"))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi),
                "corr_{$p}_points" => $cfp->nullableNumber($cor . $this->plugin->txt("points"))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi),
            ];

            if(!$multi) {
                $columns["corr_{$p}_grade"] = $cf->text($cor. $this->lng->txt("grade"))->withIsOptional(true, false)->withIsSortable(true);
            }
            $columns["corr_{$p}_authorized"] = $cf->boolean($cor. $this->lng->txt("authorized"), $this->lng->txt('yes'), $this->lng->txt('no'))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi);
        }

        $columns += [
            "result" => $cf->text($this->lng->txt("result"))->withIsOptional(true, true)->withIsSortable(false),
            "points" => $cfp->nullableNumber($this->lng->txt("final_points"))->withIsOptional(true, false)->withIsSortable(true),
            "grade" => $cf->text($this->plugin->txt("final_grade"))->withIsOptional(true, false)->withIsSortable(true),
            "finalized" => $cfp->nullableDate($this->plugin->txt("finalized_at"), $date_without_seconds)->withIsOptional(true, false)->withIsSortable(true),
            "finalized_from" => $cf->text($this->plugin->txt("finalized_from"))->withIsOptional(true, false)->withIsSortable(true),
            "stitch_needed" => $stitch_possible ? $cf->boolean($this->lng->txt("stitch"), $this->lng->txt('yes'), $this->lng->txt('no'))->withIsOptional(false, true)->withIsSortable(true) : null,
        ];

        return $columns;

    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        return count($this->writer_service->all());
    }

    public function getTableActions(): array
    {
        return [
        ];
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null): \Generator
    {
        $writer = array_filter($this->writer_service->all(), fn(Writer $writer) => empty($ids) || in_array($writer->getId(), $ids));
        $collection = new CorrectionItemCollection(
            $writer,
            $this->system_api->user()->getUserDisplaysByIds(array_map(fn(Writer $w) => $w->getUserId(), $writer), null),
            $this->corrector_service->all(),
            $this->essay_service->allByTaskId($this->task_info->getId()),
            $this->assessment_status->allWriterCorrectionStatus(),
            $this->assignment_service->all(),
            $this->summary_service->all(),
            $this->getLocations(),
            $this->getCorrectionSettings()->getRequiredCorrectors()
        );
        $user_ids = $collection->getUserIds();
        $collection->setUserData($this->system_api->user()->getUsersByIds($user_ids));

        $collection->applyFilter($filter_data);
        yield from $collection;

    }

    public function getTableItem(int $id): \ILIAS\Plugin\LongEssayAssessment\UI\Table\Item
    {
        $writer = $this->writer_service->oneByWriterId($id);
        $summaries = [];
        foreach($this->summary_service->allByWriterId($writer->getId()) as $summary)
        {
            $summaries[$summary->getCorrectorId()][] = $summary;
        }


        $summary_by_pos = [];
        $corrector_by_pos = [];
        foreach($this->assignment_service->all() as $a)
        {
            $summary = $summaries[$a->getCorrectorId()] ?? null;
            $corrector = $this->corrector_service->oneById($a->getCorrectorId());
            $summary_by_pos[$a->getPosition()] = $summary;
            $corrector_by_pos[$a->getPosition()] = $corrector;
        }

        return new CorrectionItem(
            $id,
            $writer,
            [$this->user_service->getUser($writer->getUserId())],
            $this->getLocation($writer->getLocation()),
            $this->essay_service->oneByWriterIdAndTaskId($writer->getId(), $this->task_info->getId()),
            $this->assessment_status->oneWriterCorrectionStatus($writer),
            $summary_by_pos,
            $corrector_by_pos,
            $this->user_service->getUserDisplay($writer->getUserId(), null)
        );
    }

    public function getFilterInputs(): array
    {
        $status = [
            (string)CorrectionStatus::WRITING_NOT_STARTED->value => $this->plugin->txt("status_writing_not_started"),
            (string)CorrectionStatus::WRITING_STARTED->value => $this->plugin->txt("status_writing_started"),
            (string)CorrectionStatus::WRITING_EXCLUDED->value => $this->plugin->txt("status_writing_excluded"),
            (string)CorrectionStatus::WRITING_EXCLUDED->value => $this->plugin->txt("status_writing_authorized"),
            (string)CorrectionStatus::STARTED->value => $this->plugin->txt("correction_status_started"),
            (string)CorrectionStatus::STITCH_NEEDED->value => $this->plugin->txt("correction_status_stitch_needed"),
            (string)CorrectionStatus::FINALIZED->value => $this->plugin->txt("correction_finalized_from"),
        ];
        $locations = [];
        foreach($this->getLocations() as $location) {
            $locations[$location->getId()] = $location->getTitle();
        }

        return [
            "name" => $this->ui_factory->input()->field()->text($this->plugin->txt("participants")),
            "location" =>  $this->ui_factory->input()->field()->multiselect($this->plugin->txt("locations"), $locations),
            "min_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("min_word_count"))
                                            ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0)),
            "max_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("max_word_count"))
                                            ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(1)),
            "status" => $this->ui_factory->input()->field()->multiSelect($this->plugin->txt("correction_status"), $status),
            "assigned" => $this->ui_factory->input()->field()->select(
                $this->plugin->txt("filter_assigned"),
                [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
            ),
            "pdf_version" => $this->ui_factory->input()->field()->select(
                $this->plugin->txt("filter_pdf_version"),
                [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
            )];
    }

    public function getFilterInputActivation(): ?array
    {
        return [true, true, true, true, true, true, true];
    }

    public function getFilterBaseAction(): string
    {
        return $this->ctrl->getLinkTarget($this, "showItems");
    }

    protected function hasLocations() : bool
    {
        return !empty($this->getLocations());
    }

    protected function getLocations(): array
    {
        return $this->location ??= $this->assessment_api->location()->all();
    }

    protected function getLocation(?int $id) : ?Location
    {
        if ($id === null) {
            return null;
        }

        $location = $this->getLocations();
        return $location[$id] ?? null;
    }

    protected function getSettings() : OrgaSettings
    {
        return $this->settings ??= $this->assessment_api->orgaSettings()->get();
    }

    protected function getCorrectionSettings() : CorrectionSettings
    {
        return $this->correction_settings ??= $this->assessment_api->correctionSettings()->get();
    }
}
