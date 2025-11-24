<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use Edutiek\AssessmentService\Assessment\Format\FullService as FormatService;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as PermissionService;
use Edutiek\AssessmentService\Task\Data\AssignmentPosition;
use ILIAS\Data\ReferenceId;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use Generator;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\ColumnMappingClosure;
use Edutiek\AssessmentService\Assessment\Data\WritingStatus;
use Edutiek\AssessmentService\Task\AssessmentStatus\CombinedStatus;
use Edutiek\AssessmentService\Task\Data\CorrectorSummary;
use Edutiek\AssessmentService\System\Data\UserData;
use ILIAS\Plugin\LongEssayAssessment\Task\Data\CorrectorAssignment;
use Edutiek\AssessmentService\Assessment\AssessmentGrading\ReadService as AssessmentGradingService;
use Edutiek\AssessmentService\Task\Format\FullService as EssayTaskFormatService;
use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Task\CorrectorAssignments\FullService as AssignmentService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use Edutiek\AssessmentService\Assessment\Data\Corrector;
use Edutiek\AssessmentService\Assessment\Corrector\FullService as CorrectorService;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use Edutiek\AssessmentService\Task\AssessmentStatus\FullService as AssesmentStatusService;
use Edutiek\AssessmentService\System\Format\FullService as SystemFormatService;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\FilterParent;
use Edutiek\AssessmentService\Task\Data\GradingStatus;
use ILIAS\Plugin\LongEssayAssessment\UI\Table;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use DateTimeZone;
use ILIAS\StaticURL\Builder\StandardURIBuilder;

/**
 *Start page for correctors
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStartGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorStartGUI extends BaseGUI implements DataTableParent, FilterParent
{
    use ConfirmationIds;

    private int $ready_items = 0;
    private CorrectionSettings $settings;
    private AssessmentGradingService $grading_service;
    private EssayTaskFormatService $format_service;
    private OrgaSettings $orga_settings;
    private AssignmentService $assignment_service;
    private UserService $user_service;
    private Corrector $corrector;
    private CorrectorService $corrector_service;
    private WriterService $writer_service;
    private AssesmentStatusService $assessment_status_service;
    private ?int $row_count = null;
    private SystemFormatService $system_format_service;
    private FormatService $assessment_format_service;
    private PermissionService $perms;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->perms = $this->assessment_api->permissions($this->object->getContextId());
        $this->orga_settings = $this->assessment_api->orgaSettings()->get();
        $this->settings = $this->assessment_api->correctionSettings()->get();
        $this->grading_service = $this->assessment_api->assessmentGrading();
        $this->format_service = $this->task_api->format();
        $this->system_format_service = $this->system_api->format($this->user->getId(), new DateTimeZone($this->user->getTimeZone()));

        $this->assignment_service = $this->task_api->correctorAssignments();
        $this->user_service = $this->system_api->user();
        $this->corrector = $this->assessment_api->corrector()->oneByUserId($this->user->getId());
        $this->corrector_service = $this->assessment_api->corrector();
        $this->writer_service = $this->assessment_api->writer();
        $this->assessment_status_service = $this->task_api->assessmentStatus();
        $this->assessment_format_service = $this->assessment_api->format($this->orga_settings);
    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showStartPage');
        switch ($cmd) {
            case 'applyFilter':
            case 'showStartPage':
            case 'startCorrector':
            case 'removeAuthorization':
            case 'removeAuthorizationConfirmationAsync':
            case 'authorizationConfirmationAsync':
            case 'authorizeCorrection':
            case 'downloadWrittenPdf':
            case 'downloadCorrectedPdf':
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    public function getColumnMapping(CorrectorStartItem|Item $item, ?array $additional_parameters): array|\ArrayAccess
    {
        $writing_status = fn(WritingStatus $x) => match($x) {
            WritingStatus::NOT_STARTED => $this->plugin->txt("writing_status_not_written"),
            WritingStatus::STARTED => $this->plugin->txt("writing_status_not_authorized"),
            WritingStatus::EXCLUDED => $this->plugin->txt("writing_status_excluded"),
            WritingStatus::AUTHORIZED => $this->plugin->txt("writing_status_authorized")
        };

        $correction_status = fn(CombinedStatus $x) => match ($x) {
            CombinedStatus::WRITING_NOT_STARTED,
            CombinedStatus::WRITING_STARTED,
            CombinedStatus::WRITING_EXCLUDED => $this->plugin->txt('correction_status_not_possible'),
            CombinedStatus::WRITING_AUTHORIZED,
            CombinedStatus::STARTED => $this->plugin->txt("correction_status_open"),
            CombinedStatus::APPROXIMATION => $this->plugin->txt("correction_status_approximation"),
            CombinedStatus::CONSULTING => $this->plugin->txt("correction_status_consulting"),
            CombinedStatus::STITCH_NEEDED => $this->plugin->txt("correction_status_stitch_needed"),
            CombinedStatus::FINALIZED => $this->plugin->txt("correction_status_finished")
        };

        $grading_status = fn(GradingStatus $x) => match($x) {
            GradingStatus::NOT_STARTED => $this->plugin->txt('grading_not_started'),
            GradingStatus::OPEN => $this->plugin->txt('grading_open'),
            GradingStatus::PRE_GRADED => $this->plugin->txt('grading_pre_graded'),
            GradingStatus::AUTHORIZED => $this->plugin->txt('grading_authorized'),
            GradingStatus::REVISED => $this->plugin->txt('grading_revised')
        };

        $other_corrector = function (?UserData $user_data, ?CorrectorSummary $summary, ?CorrectorAssignment $assignment) {
            if (empty($user_data) || empty($assignment)) {
                return $this->plugin->txt('assignment_pos_empty');
            }
            return $this->plugin->txt($assignment->getPosition()->languageVariable()) . ": " . $user_data->getFullname(false) . ' - ' . $this->format_service->correctionResult($summary, false, true);
        };

        $grading_service = $this->grading_service;
        $assessment_format = $this->assessment_format_service;
        $essay_format = $this->format_service;

        return new ColumnMappingClosure(function ($key) use ($item, $writing_status, $correction_status, $grading_service, $other_corrector, $grading_status, $assessment_format, $essay_format) {
            return match($key) {
                'pseudonym' => $item->getWriter()->getPseudonym(),
                'position' => $this->plugin->txt($item->getAssignment()->getPosition()->languageVariable()),
                'task' => $item->getTaskTitle(),
                'writing_status' => $writing_status($item->getWriter()->getWritingStatus()),
                'correction_status' => $correction_status($item->getCorrectionStatus()),
                'own_status' => $grading_status($item->getCorrectionStatus()),
                'own_points' => $item->getSummary()?->getPoints(),
                'own_grade' => $grading_service->getGradLevelForPoints($item->getSummary()->getPoints())->getGrade(),
                'own_grading' => $essay_format->correctionResult($item->getSummary()),
                'result' => $assessment_format->finalResult($item->getWriter()),
                'other_correction' => $other_corrector($item->getOtherCorrector(), $item->getOtherSummary(), $item->getOtherAssignment()),
                'final_points' => $item->getWriter()->getFinalPoints(),
                'final_grade' => $grading_service->getGradeLevel($item->getWriter()->getFinalGradeLevelId())->getGrade(),
                default => null
            };
        });
    }

    public function getColumns(?array $additional_parameters): array
    {
        $cf = $this->ui_factory->table()->column();
        $cfp = $this->plugin_ui_factory->table()->column();
        $other_corrections = $this->settings->getRequiredCorrectors() > 1;
        $multi_task = $this->orga_settings->getMultiTasks();

        return [
          'pseudonym' => $cf->text($this->plugin->txt('pseudonym'))->withIsOptional(false, true),
          'position' => !$multi_task && $other_corrections ? $cf->status($this->plugin->txt('position'))->withIsOptional(true, true) : null,
          'task' => $multi_task ? $cf->text($this->plugin->txt('task'))->withIsOptional(false, true) : null,
          'writing_status' => $cf->status($this->plugin->txt('writing_status'))->withIsOptional(false, true),
          'correction_status' => $cf->status($this->plugin->txt('correction_status'))->withIsOptional(false, true),
          'own_status' => $cf->status($this->plugin->txt('own_status'))->withIsOptional(true, false),
          'own_points' => $cfp->nullableNumber($this->plugin->txt('own_points'))->withIsOptional(true, false),
          'own_grade' => !$multi_task ? $cf->text($this->plugin->txt('own_grade'))->withIsOptional(true, false) : null,
          'own_grading' => $cf->status($this->plugin->txt('own_grading'))->withIsOptional(true, true)->withIsSortable(false),
          'other_corrections' => $other_corrections ? $cf->text($this->plugin->txt('other_corrections')) : null,
          'result' => $cf->status($this->plugin->txt('result'))->withIsOptional(false, true)->withIsSortable(false),
          'final_points' => $cfp->nullableNumber($this->plugin->txt('final_points'))->withIsOptional(true, false),
          'final_grade' => !$multi_task ? $cf->text($this->plugin->txt('final_grade'))->withIsOptional(true, false) : null,
        ];
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        if (isset($filter_data)) {
            // filter is already saved
            return count($this->assignment_service->allByCorrectorIdFiltered($this->corrector->getId()));
        } else {
            return count($this->assignment_service->allByCorrectorId($this->corrector->getId()));
        }
    }

    public function getTableActions(): array
    {
        return [
            $this->downloadWrittenPDFAction(),
            $this->downloadCorrectedPdfAction(),
            $this->authorizeCorrectionAction(),
            $this->removeAuthorizationAction()
        ];
    }

    private function downloadWrittenPDFAction(): Table\Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "download_written_pdf",
            $this->plugin->txt("download_written_pdf"),
            [$this, "downloadWrittenPdf"],
            fn(CorrectorStartItem $x) => true,
            Table\Action\Type::Single
        );
    }

    private function downloadCorrectedPdfAction(): Table\Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "download_corrected_pdf",
            $this->plugin->txt("download_corrected_pdf"),
            [$this, "downloadCorrectedPdf"],
            fn(CorrectorStartItem $x) => true,
            Table\Action\Type::Single
        );
    }

    private function authorizeCorrectionAction(): Table\Action\Confirmation
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "authorize_correction",
            $this->plugin->txt('authorize_correction'),
            $this->plugin->txt('authorize_correction'),
            $this->plugin->txt('confirm_authorize_correction'),
            $this->ctrl->getFormAction($this, 'authorizeCorrection'),
            fn(CorrectorStartItem $x) => $x->getWriter()->getPseudonym() . ': '
                . $this->format_service->correctionResult($x->getSummary()),
            fn(CorrectorStartItem $x) =>
                empty($x->getSummary()?->getCorrectionAuthorized())
                && $x->getWriter()->isAuthorized()
                && !empty($x->getSummary()?->getPoints()),
            Table\Action\Type::Standard
        );
    }

    private function removeAuthorizationAction(): Table\Action\Confirmation
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "remove_own_authorization",
            $this->plugin->txt('remove_own_authorization'),
            $this->plugin->txt('remove_own_authorization'),
            $this->plugin->txt('confirm_remove_own_authorization'),
            $this->ctrl->getFormAction($this, 'removeAuthorization'),
            fn(CorrectorStartItem $x) => $x->getWriter()->getPseudonym() . ': '
                . $this->format_service->correctionResult($x->getSummary()),
            fn(CorrectorStartItem $x) => !empty($x->getSummary()?->getCorrectionAuthorized()),
            Table\Action\Type::Standard
        );
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null): Generator
    {
        if (isset($filter_data)) {
            // filter is already saved
            $own_assignments = $this->assignment_service->allByCorrectorIdFiltered($this->corrector->getId());
        } else {
            $own_assignments = $this->assignment_service->allByCorrectorId($this->corrector->getId());
        }

        /**
         * @var CorrectorAssignment $own_assignment
         */
        foreach ($own_assignments as $own_assignment) {
            if (!empty($ids) && !in_array($own_assignment->getId(), $ids)) {
                continue;
            }

            $item = $this->buildCorrectorStartItem($own_assignment);
            yield $item;
        }
    }

    public function getTableItem(int $id): Item
    {
        $assignment = $this->assignment_service->oneById($id);
        return $this->buildCorrectorStartItem($assignment);
    }

    private function buildCorrectorStartItem(CorrectorAssignment $assignment): CorrectorStartItem
    {
        $writer = $this->writer_service->oneByWriterId($assignment->getWriterId());
        $title = $settings = $this->task_api->settings($assignment->getId())->get()->getTitle();
        $correction_status = $this->assessment_status_service->oneWriterCombinedStatus($writer);
        $co_assignment = $co_user_data = $co_summary = $own_summary = null;

        if ($this->settings->getRequiredCorrectors() > 1) {

            foreach ($this->assignment_service->allByWriterId($assignment->getWriterId()) as $a) {
                if ($a->getWriterId() === $assignment->getWriterId()
                    && $a->getTaskId() === $assignment->getTaskId()
                    && $a->getCorrectorId() !== $assignment->getCorrectorId()) {
                    $co_assignment = $a;
                }
            }

            if ($co_assignment !== null) {
                $co_corrector = $this->corrector_service->oneById($co_assignment->getCorrectorId());
                $co_user_data = $this->user_service->getUser($co_corrector?->getUserId() ?? -1);
            }
        }

        foreach ($this->task_api->correctorSummary()->allByTaskIdAndWriterId(
            $assignment->getTaskId(),
            $assignment->getWriterId()
        ) as $summary) {
            if ($summary->getCorrectorId() === $assignment->getCorrectorId()) {
                $own_summary = $summary;
            }
            if ($summary->getCorrectorId() === $co_assignment?->getCorrectorId()) {
                $co_summary = $summary;
            }
        }
        return new CorrectorStartItem(
            $assignment->getId(),
            $writer,
            $correction_status,
            $assignment,
            $own_summary,
            $title,
            $co_summary,
            $co_user_data,
            $co_assignment
        );
    }

    /**
     * Apply the filter settings
     * These are saved in the service to be available when the corrctor app loads its items
     */
    public function applyFilter()
    {
        $table = $this->plugin_ui_factory->table()->dataTable("corrector_start_table", $this);

        $filter_data = $table->getFilterData();
        $status = null;
        if (is_array($filter_data['status'])) {
            $status = [];
            foreach ($filter_data['status'] as $value) {
                $status[] = GradingStatus::tryFrom($value);
            }
        }
        $position = !empty($filter_data['position']) ? (int) $filter_data['position'] : null;
        $this->assignment_service->saveCorrectorFilter($this->corrector->getId(), $status, $position);

        $this->showStartPage();
    }

    /**
     * Show the items
     */
    protected function showStartPage()
    {
        $toolbar = [];
        $modals = [];

        $table = $this->plugin_ui_factory->table()->dataTable("corrector_start_table", $this);

        $filter_data = $table->getFilterData();
        $is_empty_after_filter = empty($this->getTotalRowCount($filter_data, null));
        $is_empty_before_filter = empty($this->getTotalRowCount(null, null));

        if ($this->perms->canCorrect()) {
            $this->ctrl->clearParameters($this);
            $button = $this->ui_factory->button()->primary(
                $this->plugin->txt('start_correction'),
                !$is_empty_after_filter ? $this->ctrl->getLinkTarget($this, "startCorrector") : "#"
            );

            if ($is_empty_after_filter) {
                $this->toolbar->addComponent($button->withUnavailableAction());
            } else {
                $this->toolbar->addComponent($button);
            }
        }

        if (!$is_empty_before_filter) {
            $this->tpl->setContent($this->renderer->render($table->getComponents()));
            if (!empty($period = $this->system_format_service->dateRange($this->orga_settings->getCorrectionStart(), $this->orga_settings->getCorrectionEnd()))) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt("correction_period") . ': ' . $period, false);
            }
        } else {
            $this->tpl->setOnScreenMessage("info", $this->plugin->txt("message_no_correction_items"), false);
        }
    }

    /**
     * Start the Corrector Web app
     */
    protected function startCorrector()
    {
        if (!$this->perms->canCorrect()) {
            $this->raisePermissionError();
        }
        $this->assessment_api->appService()->openCorrector(
            $this->object->getContextId(),
            $this->getReturnUrl(),
            null,
            null
        );
    }

    protected function authorizeCorrection()
    {
        $valid = false;

        //        foreach ($this->getWriterIds() as $writer_id) {
        //            $essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($writer_id, $this->settings->getTaskId());
        //
        //            if (empty($essay)) {
        //                continue;
        //            }
        //            $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId($essay->getId(), $corrector->getId());
        //
        //            if (empty($summary)) {
        //                continue;
        //            }
        //            $valid = true;
        //            $this->service->authorizeCorrection($summary, $corrector->getUserId());
        //            if ($this->service->tryFinalisation($essay, $corrector->getUserId())) {
        //                $this->service->sendReviewNotification($this->object->getRefId(), $writer_id);
        //            }
        //        }

        if ($valid) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("authorize_correction_done"), true);
            $this->ctrl->redirect($this);
        } else {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("no_corrections_to_authorize"), true);
            $this->ctrl->redirect($this);
        }
    }

    protected function downloadWrittenPdf()
    {
        //        $params = $this->request->getQueryParams();
        //        $writer_id = (int) ($params['writer_id'] ?? 0);
        //
        //        $service = $this->localDI->getWriterAdminService($this->object->getId());
        //        $repoWriter = $this->localDI->getWriterRepo()->getWriterById($writer_id);
        //
        //        $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-writing.pdf';
        //        $this->common_services->fileHelper()->deliverData($service->getWritingAsPdf($this->object, $repoWriter, true), $filename, 'application/pdf');
    }

    protected function downloadCorrectedPdf()
    {
        //        $params = $this->request->getQueryParams();
        //        $writer_id = (int) ($params['writer_id'] ?? 0);
        //
        //        $service = $this->localDI->getCorrectorAdminService($this->object->getId());
        //        $repoWriter = $this->localDI->getWriterRepo()->getWriterById($writer_id);
        //        $repoCorrector = $this->localDI->getCorrectorRepo()->getCorrectorByUserId($this->dic->user()->getId(), $this->settings->getTaskId());
        //
        //        $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-correction.pdf';
        //        $this->common_services->fileHelper()->deliverData($service->getCorrectionAsPdf($this->object, $repoWriter, $repoCorrector, true), $filename, 'application/pdf');
    }



    protected function removeAuthorization()
    {
        $success = false;

        //        foreach ($this->getWriterIds() as $writer_id) {
        //            $writer = $this->localDI->getWriterRepo()->getWriterById($writer_id);
        //            if (empty($writer)) {
        //                continue;
        //            }
        //            if ($this->service->removeOwnAuthorization($writer, $corrector)) {
        //                $success = true;
        //            } else {
        //                $this->tpl->setOnScreenMessage("failure", sprintf($this->plugin->txt('remove_own_authorization_failed'), $writer->getPseudonym()), true);
        //            }
        //        }

        if ($success) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt('remove_own_authorization_done'), true);
        }

        $this->ctrl->redirect($this);
    }

    public function getFilterInputs(): array
    {
        $correction_actions = [
            GradingStatus::NOT_STARTED->value => $this->plugin->txt('grading_not_started'),
            GradingStatus::OPEN->value => $this->plugin->txt('grading_open'),
            GradingStatus::PRE_GRADED->value => $this->plugin->txt('grading_pre_graded'),
            GradingStatus::AUTHORIZED->value => $this->plugin->txt('grading_authorized'),
            GradingStatus::REVISED->value => $this->plugin->txt('grading_revised'),
        ];
        $multiple_correctors = $this->settings->getRequiredCorrectors() > 1;
        $position = [
            AssignmentPosition::FIRST->value => $this->plugin->txt('assignment_pos_first'),
            AssignmentPosition::SECOND->value => $this->plugin->txt('assignment_pos_second'),
            AssignmentPosition::STITCH->value => $this->plugin->txt('assignment_pos_stitch'),
        ];

        return  [
            "status" => $this->ui_factory->input()->field()->multiSelect($this->plugin->txt('own_correction'), $correction_actions),
            "position" => $multiple_correctors ? $this->ui_factory->input()->field()->select($this->plugin->txt('own_position'), $position) : null
        ];
    }

    public function getFilterInputActivation(): ?array
    {
        return null;
    }

    public function getFilterBaseAction(): string
    {
        return $this->ctrl->getLinkTarget($this, 'applyFilter');
    }

    private function getReturnUrl(): string
    {
        $builder = new StandardURIBuilder(ILIAS_HTTP_PATH, false);

        return (string) $builder->build(
            'xlas',
            new ReferenceId($this->object->getRefId()),
            ['corrector']
        );
    }
}
