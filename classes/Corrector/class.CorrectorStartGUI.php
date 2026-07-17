<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use Edutiek\AssessmentService\Assessment\Format\FullService as FormatService;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as PermissionService;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\GradingPosition;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use Generator;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\ColumnMappingClosure;
use Edutiek\AssessmentService\Assessment\Data\WritingStatus;
use Edutiek\AssessmentService\Assessment\Data\CombinedStatus;
use Edutiek\AssessmentService\Task\Data\CorrectorSummary;
use Edutiek\AssessmentService\System\Data\UserData;
use ILIAS\Plugin\LongEssayAssessment\Task\Data\CorrectorAssignment;
use Edutiek\AssessmentService\Assessment\AssessmentGrading\ReadService as AssessmentGradingService;
use Edutiek\AssessmentService\Task\Format\FullService as TaskFormatService;
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
use Edutiek\AssessmentService\Assessment\TaskInterfaces\GradingStatus;
use ILIAS\Plugin\LongEssayAssessment\UI\Table;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use DateTimeZone;
use Edutiek\AssessmentService\Assessment\Data\CorrectionProcedure;
use Edutiek\AssessmentService\Task\CorrectionProcess\FullService as CorrectionProcess;
use ILIAS\Plugin\LongEssayAssessment\Jump;
use Edutiek\AssessmentService\Assessment\Data\WritingTask;
use ILIAS\UI\Implementation\Component\Input\Input;

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
    private TaskFormatService $task_format;
    private OrgaSettings $orga_settings;
    private AssignmentService $assignment_service;
    private UserService $user_service;
    private Corrector $corrector;
    private CorrectorService $corrector_service;
    private WriterService $writer_service;
    private AssesmentStatusService $assessment_status_service;
    private ?int $row_count = null;
    private SystemFormatService $system_format;
    private FormatService $assessment_format;
    private PermissionService $perms;
    private CorrectionProcess $correction_process;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->perms = $this->assessment_api->permissions($this->object->getContextId());
        $this->orga_settings = $this->assessment_api->orgaSettings()->get();
        $this->settings = $this->assessment_api->correctionSettings()->get();
        $this->grading_service = $this->assessment_api->assessmentGrading();

        $this->assessment_format = $this->assessment_api->format();
        $this->task_format = $this->task_api->format();
        $this->system_format = $this->system_api->format($this->user->getId(), new DateTimeZone($this->user->getTimeZone()));

        $this->assignment_service = $this->task_api->correctorAssignments();
        $this->user_service = $this->system_api->user();
        $this->corrector = $this->assessment_api->corrector()->oneByUserId($this->user->getId());
        $this->corrector_service = $this->assessment_api->corrector();
        $this->writer_service = $this->assessment_api->writer();
        $this->assessment_status_service = $this->task_api->assessmentStatus();
        $this->correction_process = $this->task_api->correctionProcess();
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
        $other_corrector = function (?UserData $user_data, ?CorrectorSummary $summary, ?CorrectorAssignment $assignment) {
            if (empty($user_data) || empty($assignment)) {
                return $this->plugin->txt('grading_pos_empty');
            }
            return $this->plugin->txt($assignment->getPosition()->languageVariable()) . ": "
                . $user_data->getListname(false) . ' - ' . $this->task_format->correctionResult($summary, false);
        };

        $grading_service = $this->grading_service;
        $assessment_format = $this->assessment_format;
        $essay_format = $this->task_format;

        return new ColumnMappingClosure(function ($key) use ($item, $grading_service, $other_corrector, $assessment_format, $essay_format) {
            return match($key) {
                'pseudonym' => $this->ui_factory->link()->standard($item->getWriter()->getPseudonym(), $this->itemLinkTarget($item)),
                'position' => $this->plugin->txt($item->getAssignment()->getPosition()->languageVariable()),
                'task' => $item->getTaskTitle(),
                'combined_status' => $this->assessment_format->combinedStatus($item->getWriter()),
                'own_status' => $this->task_format->gradingStatus($item->getSummary()?->getGradingStatus(), true),
                'own_points' => $item->getSummary()?->getEffectivePoints(),
                'own_grade' => $item->getSummary() ? $grading_service->getGradLevelForPoints($item->getSummary()->getPoints())?->getGrade() : null,
                'result' => $assessment_format->finalResult($item->getWriter()),
                'other_correction' => $other_corrector($item->getOtherCorrector(), $item->getOtherSummary(), $item->getOtherAssignment()),
                'final_points' => $item->getWriter()->getFinalPoints(),
                'final_grade' => $grading_service->getGradLevelForPoints($item->getWriter()->getFinalPoints())?->getGrade(),
                default => null
            };
        });
    }

    private function itemLinkTarget(CorrectorStartItem $item): ?string
    {
        $this->ctrl->setParameter($this, 'task_id', $item->getAssignment()->getTaskId());
        $this->ctrl->setParameter($this, 'writer_id', $item->getAssignment()->getWriterId());
        return $this->ctrl->getLinkTarget($this, 'startCorrector');
    }

    public function getColumns(?array $additional_parameters): array
    {
        $cf = $this->ui_factory->table()->column();
        $cfp = $this->plugin_ui_factory->table()->column();
        $other_corrections = $this->settings->getRequiredCorrectors() > 1;
        $multi_task = $this->orga_settings->getMultiTasks();

        return [
          'pseudonym' => $cf->link($this->plugin->txt('pseudonym'))->withIsOptional(false, true),
          'position' => !$multi_task && $other_corrections ? $cf->status($this->plugin->txt('own_position'))->withIsOptional(true, true) : null,
          'task' => $multi_task ? $cf->text($this->plugin->txt('task'))->withIsOptional(true, true) : null,
          'combined_status' => $cf->status($this->plugin->txt('correction_status'))->withIsOptional(true, true),
          'own_status' => $cf->status($this->plugin->txt('own_status'))->withIsOptional(true, true),
          'own_points' => $cfp->decimal($this->plugin->txt('own_points'))->withIsOptional(true, true),
          'own_grade' => !$multi_task ? $cf->text($this->plugin->txt('own_grade'))->withIsOptional(true, true) : null,
          'other_correction' => $other_corrections ? $cf->text($this->plugin->txt('other_corrections'))->withIsOptional(true, false) : null,
          'result' => $cf->status($this->plugin->txt('result'))->withIsOptional(true, true)->withIsSortable(true),
          'final_points' => $cfp->decimal($this->plugin->txt("result") . ': ' . $this->plugin->txt('points'))->withIsOptional(true, false),
          'final_grade' => !$multi_task ? $cf->text($this->plugin->txt("result") . ': ' . $this->plugin->txt('grade'))->withIsOptional(true, false) : null,
        ];
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        if (isset($filter_data)) {
            // filter is already saved
            return count($this->assignment_service->allByCorrectorIdFiltered($this->corrector->getId(), true));
        } else {
            return count($this->assignment_service->allByCorrectorId($this->corrector->getId(), true));
        }
    }

    public function getTableActions(): array
    {
        $actions = [];
        if ($this->settings->getDownloadWriting()) {
            $actions[] = $this->downloadWrittenPdfAction();
        }
        if ($this->settings->getDownloadCorrection()) {
            $actions[] = $this->downloadCorrectedPdfAction();
        }
        $actions[] = $this->authorizeCorrectionAction();

        if ($this->settings->getUndoAuthorization()) {
            $actions[] = $this->removeAuthorizationAction();
        }

        if ($this->settings->getUndoFirstAuthorization()) {
            $actions[] = $this->removeFirstAuthorizationAction();
        }

        return $actions;
    }

    private function downloadWrittenPdfAction(): Table\Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "download_written_pdf",
            $this->plugin->txt("download_written_pdf"),
            [$this, "downloadWrittenPdf"],
            fn(CorrectorStartItem $x) => $this->settings->getDownloadWriting(),
            Table\Action\Type::Standard
        );
    }

    private function downloadCorrectedPdfAction(): Table\Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "download_corrected_pdf",
            $this->plugin->txt("download_corrected_pdf"),
            [$this, "downloadCorrectedPdf"],
            fn(CorrectorStartItem $x) => $this->settings->getDownloadCorrection(),
            Table\Action\Type::Standard
        );
    }

    private function authorizeCorrectionAction(): Table\Action\Confirmation
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "authorizeCorrection",
            $this->plugin->txt('authorize_correction'),
            $this->plugin->txt('authorize_correction'),
            $this->plugin->txt('confirm_authorize_correction'),
            $this->ctrl->getFormAction($this, 'authorizeCorrection'),
            fn(CorrectorStartItem $x) => $x->getWriter()->getPseudonym() . ': '
                . $this->task_format->correctionResult($x->getSummary(), true),
            fn(CorrectorStartItem $x) =>
                $this->correction_process->canAuthorizeOwnCorrection($x->getAssignment())
                && $x->getSummary()?->isComplete(),
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
                . $this->task_format->correctionResult($x->getSummary(), true),
            fn(CorrectorStartItem $x) => $this->correction_process->canRemoveOwnAuthorization($x->getAssignment()),
            Table\Action\Type::Standard
        );
    }

    private function removeFirstAuthorizationAction(): Table\Action\Form
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "remove_first_authorization",
            $this->plugin->txt('remove_first_authorization'),
            $this->plugin->txt('remove_first_authorization'),
            $this->getRemoveFirstAuthorizationConfirmFields(...),
            $this->removeFirstAuthorization(...),
            fn(CorrectorStartItem $x) => $this->correction_process->canRemoveFirstAuthorization($x->getAssignment()),
            Table\Action\Type::Single
        );
    }

    /**
     * @param CorrectorStartItem[] $items
     */
    private function getRemoveFirstAuthorizationConfirmFields(array $items): array
    {
        $item = reset($items);

        $fields = [
            'info' => $this->plugin_ui_factory->field()->info($this->plugin->txt('participant'))
            ->withInfo($this->ui_factory->listing()->unordered([$item->getWriter()->getPseudonym()])),
            'reason' => $this->ui_factory->input()->field()->textarea(
                $this->plugin->txt('remove_first_authorization_reason'),
            )->withAdditionalTransformation($this->refinery->string()->hasMinLength(5))
            ->withRequired(true)
        ];

        return $fields;
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null): Generator
    {
        if (isset($filter_data)) {
            // filter is already saved
            $own_assignments = $this->assignment_service->allByCorrectorIdFiltered($this->corrector->getId(), true);
        } else {
            $own_assignments = $this->assignment_service->allByCorrectorId($this->corrector->getId(), true);
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
        $title = $settings = $this->task_api->settings($assignment->getTaskId())->get()->getTitle();
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
            $writer->getCombinedStatus(),
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

        if ($this->get->string('cmdFilter') == 'reset') {
            $status = null;
            $position = null;
        } else {
            $status = null;
            if (is_array($filter_data['status'] ?? null)) {
                $status = [];
                foreach ($filter_data['status'] as $value) {
                    $status[] = GradingStatus::tryFrom($value);
                }
            }
            $position = (isset($filter_data['position']) && $filter_data['position'] !== '') ? (int) $filter_data['position'] : null;
        }

        $this->assignment_service->saveCorrectorFilter($this->corrector->getId(), $status, $position);
        $this->ctrl->redirect($this, 'showStartPage');
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
        $is_empty_before_filter = empty($this->getTotalRowCount(null, null));
        $is_empty_after_filter = empty($this->getTotalRowCount($filter_data, null));

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
            $table->executeAction();
            $this->tpl->setContent($this->renderer->render($table->getComponents()));
            if ($this->orga_settings->getCorrectionStart() || $this->orga_settings->getCorrectionEnd()) {
                $period = $this->system_format->dateRange($this->orga_settings->getCorrectionStart(), $this->orga_settings->getCorrectionEnd());
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
            \ilObjLongEssayAssessmentGUI::_link($this->object->getRefId(), Jump::CORRECTOR, true),
            $this->get->integer('task_id'),
            $this->get->integer('writer_id'),
        );
    }

    protected function authorizeCorrection()
    {
        $assignment_ids = $this->confirmationIds();
        $changed = [];
        $unchanged = [];

        foreach ($assignment_ids as $assignment_id) {
            $assignment = $this->assignment_service->oneById($assignment_id);
            $writer = $this->writer_service->oneByWriterId($assignment?->getWriterId() ?? 0);
            if ($writer !== null && $assignment !== null) {
                $result = $this->correction_process->authorizeOwnCorrection($assignment);
                if ($result->isOk()) {
                    $changed[] = $writer->getPseudonym();
                } else {
                    $unchanged[] = $writer->getPseudonym() . ': ' . implode(', ', $result->failures());
                }
            }
        }

        $this->multiFeedback(
            $changed,
            $unchanged,
            $this->plugin->txt('authorize_correction_done'),
            $this->plugin->txt('authorize_correction_failed')
        );

        $this->ctrl->redirect($this);
    }

    /**
     * @param CorrectorStartItem[] $items
     */
    public function downloadWrittenPdf(array $items)
    {
        if ($this->settings->getDownloadWriting()) {
            $writings = array_map(fn(CorrectorStartItem $item) =>
            new WritingTask($item->getAssignment()->getWriterId(), $item->getAssignment()->getTaskId()), $items);

            $background = $this->assessment_api->export($this->object->getContextId())->downloadWritings($writings, true);
            if ($background) {
                $this->info($this->plugin->txt('download_in_background_started'));
            }
        }
    }
    /**
     * @param CorrectorStartItem[] $items
     */
    public function downloadCorrectedPdf(array $items)
    {
        if ($this->settings->getDownloadCorrection()) {
            $writings = array_map(fn(CorrectorStartItem $item) =>
            new WritingTask($item->getAssignment()->getWriterId(), $item->getAssignment()->getTaskId()), $items);

            $background = $this->assessment_api->export($this->object->getContextId())->downloadCorrections($writings, true, false);
            if ($background) {
                $this->info($this->plugin->txt('download_in_background_started'));
            }
        }
    }

    protected function removeAuthorization()
    {
        $assignment_ids = $this->confirmationIds();
        $changed = [];
        $unchanged = [];

        foreach ($assignment_ids as $assignment_id) {
            $assignment = $this->assignment_service->oneById($assignment_id);
            $writer = $this->writer_service->oneByWriterId($assignment?->getWriterId() ?? 0);
            if ($writer !== null && $assignment !== null) {
                $result = $this->correction_process->removeOwnAuthorization($assignment);
                if ($result->isOk()) {
                    $changed[] = $writer->getPseudonym();
                } else {
                    $unchanged[] = $writer->getPseudonym() . ': ' . implode(', ', $result->failures());
                }
            }
        }

        $this->multiFeedback(
            $changed,
            $unchanged,
            $this->plugin->txt('remove_own_authorization_done'),
            $this->plugin->txt('remove_own_authorization_failed')
        );

        $this->ctrl->redirect($this);
    }

    /**
     * @param CorrectorStartItem[] $items
     */
    protected function removeFirstAuthorization(array $items, array $data)
    {
        $item = reset($items);
        $result = $this->correction_process->removeFirstAuthorization($item->getAssignment(), $data['reason'] ?? '');

        if ($result->isOk()) {
            $this->success($this->plugin->txt('remove_first_authorization_done'), true);
        } else {
            $this->failure(implode('< br />', $result->failures()));
        }

        $this->ctrl->redirect($this);
    }

    public function getFilterInputs(): array
    {

        $multiple_correctors = $this->settings->getRequiredCorrectors() > 1;
        [$stat_value, $pos_value] = $this->assignment_service->getCorrectionFilter($this->corrector->getId());

        return  [
            "status" => $this->ui_factory->input()->field()->multiSelect(
                $this->plugin->txt('own_correction'),
                $this->task_format->gradingStatusOptions()
            )
                ->withValue($stat_value),
            "position" => $multiple_correctors
                ? $this->ui_factory->input()->field()->select(
                    $this->plugin->txt('own_position'),
                    $this->task_format->gradingPositionOptions()
                )
                    ->withValue($pos_value ?? '')
                : null
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

}
