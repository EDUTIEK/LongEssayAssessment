<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin;

use Edutiek\AssessmentService\Assessment\Data\CorrectionStatus;
use Edutiek\AssessmentService\Assessment\Data\WritingTask;
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
use Edutiek\AssessmentService\Assessment\TaskInterfaces\GradingStatus;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaService;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use Edutiek\AssessmentService\EssayTask\Essay\ClientService as EssayService;
use Edutiek\AssessmentService\Task\AssessmentStatus\FullService as AssessmentStatus;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Task\CorrectorSummary\FullService as SummaryService;
use Edutiek\AssessmentService\Task\CorrectorAssignments\FullService as CorrectorAssignmentsService;
use Edutiek\AssessmentService\Assessment\Corrector\FullService as CorrectorService;
use Edutiek\AssessmentService\Assessment\Data\Location;
use Edutiek\AssessmentService\Assessment\AssessmentGrading\ReadService as GradingService;
use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings;
use ILIAS\UI\Implementation\Component\Modal\RoundTrip;
use Edutiek\AssessmentService\System\Data\UserData;
use ILIAS\Refinery\Transformation;
use Edutiek\AssessmentService\Task\CorrectionProcess\FullService as CorrectionProcess;
use ILIAS\Plugin\LongEssayAssessment\GUI\Correction\CorrectionTableParent;
use ILIAS\Plugin\LongEssayAssessment\GUI\Correction\CorrectionItem;
use Edutiek\AssessmentService\Assessment\Data\CombinedStatus;
use ILIAS\UI\Component\Input\Input;
use ILIAS\Plugin\LongEssayAssessment\Jump;

/**
 * Correction Admin GUI class
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin\CorrectionAdminGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectionAdminGUI extends BaseGUI
{
    use ConfirmationIds;

    public const FILTER_YES = "1";
    public const FILTER_NO = "2";
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
    private CorrectionProcess $correction_process;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->writer_service = $this->assessment_api->writer();
        $this->user_service = $this->system_api->user();
        $this->essay_service = $this->essay_task_api->essay(true);
        $this->assessment_status = $this->task_api->assessmentStatus();
        $this->summary_service = $this->task_api->correctorSummary();
        $this->assignment_service = $this->task_api->correctorAssignments();
        $this->corrector_service = $this->assessment_api->corrector();
        $this->grading_service = $this->assessment_api->assessmentGrading();
        $this->correction_process = $this->task_api->correctionProcess();
    }

    private function exportTableAction(): Action\Export
    {
        return $this->plugin_ui_factory->table()->action()->export(
            "export",
            $this->plugin->txt('correction_admin_table_export'),
            $this->plugin->txt('correction_admin_table_export_filename')
        );
    }

    private function removeAuthorizationsAction(): Action\Confirmation
    {
        $label = $this->correction_settings->getRequiredCorrectors() == 1
            ? $this->plugin->txt('remove_authorization')
            : $this->plugin->txt('remove_authorizations');
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "removeAuthorization",
            $label,
            $label,
            $this->plugin->txt("remove_authorizations_confirmation"),
            $this->ctrl->getFormAction($this, "removeAuthorizations"),
            fn (CorrectionItem $item) => $item->getWriterName()
                . ($this->object->getMultiTasks() ? ', ' . $item->getTaskSettings()->getTitle() : ''),
            fn (CorrectionItem $item) => true,
            Action\Type::Standard,
        );
    }

    private function removeAuthorizations()
    {
        $essays = $this->essay_service->some($this->confirmationIds());
        $changed = [];
        $unchanged = [];

        foreach ($essays as $essay) {
            $writer = $this->writer_service->oneByWriterId($essay->getWriterId());
            $user = $this->user_service->getUser($writer->getUserId());
            $task = $this->task_api->manager()->one($essay->getTaskId());
            $name = ($user?->getListname(false) ?? $this->plugin->txt('unknown')) . ' (' . $writer->getPseudonym() . ')'
                . ($this->object->getMultiTasks() ? ' - ' . $task->getTitle() : '');

            $result = $this->correction_process->removeAuthorizations($essay->getTaskId(), $writer);
            if ($result->isOk()) {
                $changed[] = $name;
            } else {
                $unchanged[] = $name . ': ' . implode(', ', $result->failures());
            }
        }

        $this->multiFeedback(
            $changed,
            $unchanged,
            $this->plugin->txt('remove_authorizations_done'),
            $this->plugin->txt('remove_authorizations_failed')
        );

        $this->ctrl->redirect($this);
    }

    private function changeCorrectorAction(): Action\Form
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "change_corrector",
            $this->plugin->txt('change_corrector'),
            $this->lng->txt("submit"),
            $this->changeCorrectorFields(...),
            $this->changeCorrector(...),
            fn (CorrectionItem $item) => true,
            Action\Type::Standard
        )->withContent([
            $this->ui_factory->messageBox()->info($this->plugin->txt("change_corrector_info"))
        ])->withTransformations([$this, "changeCorrectorCheck"]);
    }

    /**
     * @param CorrectionItem[] $items
     */
    public function changeCorrectorCheck(array $items): array
    {
        $writer_ids = array_map(fn (CorrectionItem $item) => $item->getWriter()->getId(), $items);

        return [
            $this->refinery->custom()->constraint(
                function (array $var) use ($items) {
                    $valid = true;
                    foreach ($items as $item) {
                        $result = $this->assignment_service->assignMultiple(
                            $item->getTaskSettings()->getTaskId(),
                            $data["first_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                            $data["second_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                            $data["stitch_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                            [$item->getWriter()->getId()],
                            true
                        );
                        $valid = $valid && empty($result['invalid']);
                    }
                    return $valid;
                },
                $this->plugin->txt("invalid_assignment_combinations_error")
            )
        ];
    }

    /**
     * @param CorrectionItem[] $items
     * @return array
     */
    public function changeCorrectorFields(array $items): array
    {
        $corrector_list = [
            CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT => $this->plugin->txt("unchanged"),
            CorrectorAssignmentsService::BLANK_CORRECTOR_ASSIGNMENT => $this->lng->txt("remove")
        ];

        $corrector_ids = [];
        foreach ($this->corrector_service->all() as $corrector) {
            $corrector_ids[$corrector->getId()] = $corrector->getUserId();
        }
        $names = array_map(fn (UserData $u) => $u->getListname(true), $this->user_service->getUsersByIds($corrector_ids));

        foreach ($corrector_ids as $id => $user_id) {
            $corrector_list[$id] = $names[$user_id];
        }

        $assigned = [];
        if (count($items) == 1) { // Pre set the assigned correctors if its just one corrector
            $item = reset($items);
            foreach ($this->assignment_service->allByTaskIdAndWriterId(
                $item->getTaskSettings()->getTaskId(),
                $item->getWriter()->getId()
            ) as $assignment) {
                $assigned[$assignment->getPosition()->value] = $assignment->getCorrectorId();
            }
        }

        $fields = [];
        $fields["first_corrector"] = $this->ui_factory->input()->field()->select(
            $this->correctorLabel(0),
            $corrector_list
        )->withRequired(true)->withValue(
            $assigned[0] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT
        )->withAdditionalTransformation($this->refinery->kindlyTo()->int());

        if ($this->correction_settings->getRequiredCorrectors() > 1) {
            $fields["second_corrector"] = $this->ui_factory->input()->field()->select(
                $this->correctorLabel(1),
                $corrector_list
            )->withRequired(true)->withValue(
                $assigned[1] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT
            )->withAdditionalTransformation($this->refinery->kindlyTo()->int());

            if (count($items) == 1 && $items[0]->getWriter()->getCombinedStatus() === CombinedStatus::STITCH_NEEDED) {
                $fields["stitch_corrector"] = $this->ui_factory->input()->field()->select(
                    $this->correctorLabel(2),
                    $corrector_list
                )->withRequired(true)->withValue(
                    $assigned[2] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT
                )->withAdditionalTransformation($this->refinery->kindlyTo()->int());
            }
        }

        return $fields;
    }

    private function correctorLabel(int $assignment_position)
    {
        return match($assignment_position) {
            0 => $this->correction_settings->getRequiredCorrectors() > 1
                    ? $this->plugin->txt("assignment_pos_first")
                    : $this->plugin->txt("assignment_pos_single"),
            1 => $this->plugin->txt("assignment_pos_second"),
            2 => $this->plugin->txt("assignment_pos_stitch"),
            default => sprintf($this->plugin->txt("assignment_pos_x"), $assignment_position + 1)
        };
    }

    /**
     * @param CorrectionItem[] $items
     */
    public function changeCorrector(array $items, array $data)
    {
        foreach ($items as $item) {
            $this->assignment_service->assignMultiple(
                $item->getTaskSettings()->getTaskId(),
                $data["first_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                $data["second_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                $data["stitch_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                [$item->getWriter()->getId()]
            );
        }

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("corrector_assignment_changed"), true);
        $this->ctrl->redirect($this, 'showItems');
    }

    private function mailToWriterOrCorrectorAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "mail_to_writer_or_corrector",
            $this->plugin->txt('mail_to_writer_or_corrector'),
            $this->plugin->txt('mail_to_writer_or_corrector'),
            $this->mailToWriterOrCorrectorFields(...),
            $this->mailToWriterOrCorrector(...),
            fn (CorrectionItem $item) => true,
            Action\Type::Standard
        );
    }

    /**
     * @param CorrectionItem[] $items
     */
    private function mailToWriterOrCorrectorFields(array $items): array
    {
        $fields = [
            'info' => $this->getTableActionInfoField($items),
            'writer' => $this->ui_factory->input()->field()->checkbox($this->plugin->txt('participant'))
        ];

        $num = array_reduce($items, fn (int $n, CorrectionItem $i) => $n = max($n, $i->getAssignedCorrectorsCount()), 0);

        if ($num > 0) {
            for ($i = 0; $i < $num; $i++) {
                $fields['corrector' . $i] = $this->ui_factory->input()->field()->checkbox(
                    $this->correctorLabel($i)
                );
            }
        }
        return $fields;
    }

    /**
     * @param CorrectionItem[] $items
     */
    public function mailToWriterOrCorrector(array $items, array $data)
    {
        $logins = [];
        foreach ($items as $item) {
            if ($data['writer'] ?? 0) {
                $logins[] = $item->getWriterLogin();
            }
            for ($p = 0; $p <= $item->getAssignedCorrectorsCount(); $p++) {
                $corrector = $item->getCorrectorDataByPosition($p);
                if ($corrector && $data['corrector' . $p] ?? 0) {
                    $logins[] = $corrector->getLogin();
                }
            }
        }

        $this->openMailForm(array_unique($logins), 'showItems');
    }

    private function downloadCorrectedPdfAction(): Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "download_corrected_pdf",
            $this->plugin->txt('download_corrected_pdf'),
            [$this, "downloadCorrectedPdf"],
            fn(CorrectionItem $item) => $item->canDownloadCorrectionPdf(),
            Action\Type::Standard
        );
    }

    /**
     * @param CorrectionItem[] $items
     */
    public function downloadCorrectedPdf(array $items)
    {
        $writings = array_map(fn(CorrectionItem $item) =>
        new WritingTask($item->getWriter()->getId(), $item->getTaskSettings()->getTaskId()), $items);

        $this->assessment_api->export()->downloadCorrections($writings, false, false);
    }

    private function downloadWrittenPdfAction(): Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "download_written_pdf",
            $this->plugin->txt('download_written_pdf'),
            [$this, "downloadWrittenPdf"],
            fn (CorrectionItem $item) => $item->getWriter()->canDownloadWrittenPdf(),
            Action\Type::Standard
        );
    }

    /**
     * @param CorrectionItem[] $items
     */
    public function downloadWrittenPdf(array $items)
    {
        $writings = array_map(fn (CorrectionItem $item) =>
            new WritingTask($item->getWriter()->getId(), $item->getTaskSettings()->getTaskId()), $items);

        $this->assessment_api->export()->downloadWritings($writings, false);
    }

    public function viewCorrection()
    {
        $this->assessment_api->appService()->openCorrector(
            $this->object->getContextId(),
            \ilObjLongEssayAssessmentGUI::_link($this->object->getRefId(), Jump::CORRECTOR_ADMIN, true),
            $this->get->integer('task_id'),
            $this->get->integer('writer_id'),
            true
        );
    }

    public function viewCorrectionLink(int $task_id, int $writer_id): string
    {
        $this->ctrl->setParameter($this, 'task_id', $task_id);
        $this->ctrl->setParameter($this, 'writer_id', $writer_id);
        return $this->ctrl->getLinkTarget($this, 'viewCorrection');
    }

    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                $cmd = $this->ctrl->getCmd('showItems');
                switch ($cmd) {
                    case 'showItems':
                    case 'removeAuthorizations':
                    case 'mailToWriterOrCorrector':
                    case 'viewCorrection':
                    case 'correctorAssignmentSpreadsheetExport':
                    case 'correctorAssignmentSpreadsheetImport':
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

        $location_avaiable = $this->hasLocations();
        $corrections = $this->getCorrectionSettings()->getRequiredCorrectors();
        if ($corrections > 1 && $this->getCorrectionSettings()->getStitchAfterProcedure()) {
            $corrections++;
        }
        $multi = $this->getSettings()->getMultiTasks();
        $has_started = $this->getSettings()->getWritingStart() !== null ? $this->getSettings()->getWritingStart() < new \DateTimeImmutable() : true;

        $table_parent = new CorrectionTableParent(
            $this->dic,
            $this->plugin,
            [$this->object->getAssId()],
            $this->ctrl->getFormAction($this),
            $this->viewCorrectionLink(...)
        );
        $table_parent->setHasColumns(
            array_merge(
                ["image", "name", "login", "pseudonym", $location_avaiable ? "location" : null, "status", $multi ? "task" : null,
                         "writing_last_save", "word_count", "pdf_version", "result", "points", "grade", "finalized", "finalized_date", "finalized_name", "finalized_from_status"],
                ...array_map(fn ($p) => ["corr_{$p}", "corr_{$p}_name", "corr_{$p}_status", "corr_{$p}_points", $multi ? "corr_{$p}_grade" : null, "corr_{$p}_authorized"], range(0, $corrections - 1)),
            )
        )->setInitialVisibleColumns(["name", "login", "pseudonym", "location", "status", $has_started ? "writing_last_save" : null, $has_started ? "word_count" : null, "corr_1", "corr_2", "result"])
         ->setHasFilterFields(["name", $multi ? "task" : null, "location", "min_words", "max_words", "status", "assigned", "pdf_version"])
        ->setTableActions($this->getTableActions());

        $table_parent->setInitialVisibleColumns([]);
        $table = $this->plugin_ui_factory->table()->dataTable('correction_admin_table', $table_parent);
        $table->executeAction();
        $this->tpl->setContent($this->renderer->render($table));
    }

    public function buildToolbar(\ilToolbarGUI $toolbar)
    {
        $authorized_essay_exists = false;

        if ($authorized_essay_exists) {
            if (empty($correctors)) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt('info_missing_correctors'), false);
            } elseif (!empty($this->assignment_service->countMissingCorrectors())) {
                $this->tpl->setOnScreenMessage("info", $this->plugin->txt('info_missing_assignments'), false);
            }
        }

        $toolbar->setFormAction($this->ctrl->getFormAction($this));

        $this->toolbar->addComponent($this->ui_factory->button()->primary(
            $this->plugin->txt('assign_writers'),
            $this->ctrl->getLinkTarget($this, "confirmAssignWriters")
        ));

        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->plugin->txt("assignment_excel_export"),
            $this->ctrl->getLinkTarget($this, "correctorAssignmentSpreadsheetExport")
        ));

        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->plugin->txt("assignment_excel_import"),
            $this->ctrl->getLinkTarget($this, "correctorAssignmentSpreadsheetImport")
        ));

        $this->toolbar->addComponent($this->ui_factory->button()->toggle(
            $this->plugin->txt("assignment_excel_export_auth"),
            "#",
            "#",
            $this->spreadsheetAssignmentToggle()
        )->withAdditionalOnLoadCode(
            function ($id) {
                return "$('#{$id}').on( 'click', function() {  document.cookie = 'xlas_exass=' + ($( this ).hasClass('on') ? 'on' : 'off'); } );";
            }
        ));

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

    private function correctorAssignmentSpreadsheetExport(): void
    {
        $this->assignment_service->exportAssignmentSpreadsheet($this->spreadsheetAssignmentToggle());
    }

    private function correctorAssignmentSpreadsheetImport(): void
    {
        $upload_handler = new \ilLongEssayAssessmentUploadHandlerGUI(
            $this->system_api->tempStorage(),
            $this->plugin->dic()->uploadTempFile()
        );

        $form = $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, "correctorAssignmentSpreadsheetImport"),
            ["excel" => $this->ui_factory->input()->field()->file(
                $upload_handler,
                $this->plugin->txt("assignment_excel_import"),
                $this->plugin->txt("assignment_excel_import_info")
            )->withRequired(true)]
        );

        if ($this->request->getMethod() === "POST") {
            $form = $form->withRequest($this->request);

            if ($data = $form->getData()) {
                $upload_id = (string) current($data['excel']);
                $stored = $this->system_api->tempStorage()->saveFile(
                    $upload_handler->getApiStream($upload_id),
                    $upload_handler->getApiInfo($upload_id)
                );

                try {
                    $data = $this->assignment_service->importSpreadsheet($stored->getId());
                    $possible_errors = $this->assignment_service->assignSpreadsheetData($data, true);
                    if (!empty($possible_errors)) {
                        $possible_errors = array_merge([$this->lng->txt('corrector_assignment_change_file_failure')], $possible_errors);

                        $error = $this->lng->txt('corrector_assignment_change_file_failure') .
                            '<p class="small">' .
                            nl2br(implode("\n", $possible_errors)) .
                            '</p>';

                        $this->tpl->setOnScreenMessage("failure", implode("\n&nbsp;", $possible_errors), false);
                    } else {
                        $this->tpl->setOnScreenMessage("success", $this->lng->txt('corrector_assignment_change_file_success'), true);
                        $this->assignment_service->assignSpreadsheetData($data, false);
                        $this->ctrl->redirect($this);
                    }

                    $this->system_api->tempStorage()->deleteFile($stored->getId());
                } catch (\Exception $exception) {
                    $this->system_api->tempStorage()->deleteFile($stored->getId());
                    $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("corrector_assignment_change_file_failure") . $exception->getMessage(), false);
                }
            }
        }

        $this->tpl->setContent($this->renderer->render($form));
    }

    public function getTableActions(): array
    {
        return [
            $this->downloadWrittenPdfAction(),
            $this->downloadCorrectedPdfAction(),
            $this->mailToWriterOrCorrectorAction(),
            $this->changeCorrectorAction(),
            $this->removeAuthorizationsAction(),
            $this->exportTableAction(),
        ];
    }

    /**
     * @param CorrectionItem[] $items
     */
    private function getTableActionInfoField(array $items): Input
    {
        return $this->plugin_ui_factory->field()->info($this->plugin->txt('writing_parts'))
             ->withInfo($this->ui_factory->listing()->unordered(
                 array_map(fn (CorrectionItem $item) => $item->getWriterName()
                  . ($this->object->getMultiTasks() ? ', ' . $item->getTaskSettings()->getTitle() : ''), $items)
             ));
    }

    protected function hasLocations(): bool
    {
        return !empty($this->getLocations());
    }

    protected function getLocations(): array
    {
        return $this->location ??= $this->assessment_api->location()->all();
    }

    protected function getLocation(?int $id): ?Location
    {
        if ($id === null) {
            return null;
        }

        $location = $this->getLocations();
        return $location[$id] ?? null;
    }

    protected function getSettings(): OrgaSettings
    {
        return $this->settings ??= $this->assessment_api->orgaSettings()->get();
    }

    protected function getCorrectionSettings(): CorrectionSettings
    {
        return $this->correction_settings ??= $this->assessment_api->correctionSettings()->get();
    }

    protected function spreadsheetAssignmentToggle()
    {
        $cookie = $this->request->getCookieParams();
        if (isset($cookie['xlas_exass'])) {
            return $cookie['xlas_exass'] === "on";
        } else {
            return false;
        }
    }
}
