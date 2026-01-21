<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin;

use Edutiek\AssessmentService\Assessment\Data\CorrectionStatus;
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
            $this->plugin->txt('correction_admin_table_export_filename') . '_' . $this->task_info->getId()
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
            fn(CorrectionItem $item) => $item->getWriterName(),
            fn(CorrectionItem $item) => true,
            Action\Type::Standard,
        );
    }

    private function removeAuthorizations()
    {
        $writer_ids = $this->confirmationIds();
        $changed = [];
        $unchanged = [];

        foreach ($writer_ids as $writer_id) {
            if (($writer = $this->writer_service->oneByWriterId($writer_id)) !== null) {
                $user = $this->user_service->getUser($writer->getUserId());
                $name = ($user?->getListname(false) ?? $this->plugin->txt('unknown')) . ' (' . $writer->getPseudonym() . ')';
                $result = $this->correction_process->removeAuthorizations($this->task_info->getId(), $writer, $this->user->getId());
                if ($result->isOk()) {
                    $changed[] = $name;
                } else {
                    $unchanged[] = $name . ': ' . implode(', ', $result->messages());
                    ;
                }
            }
        }

        $messages = [
            $this->plugin->txt(count($changed) ? 'remove_authorizations_done' : 'remove_authorizations_failed'),
        ];

        if (count($changed)) {
            $messages[] = $this->renderer->render($this->ui_factory->listing()->unordered($changed));
        }
        if (count($unchanged)) {
            if (count($changed)) {
                $messages[] = $this->plugin->txt('remove_authorizations_unchanged');
            }
            $messages[] = $this->renderer->render($this->ui_factory->listing()->unordered($unchanged));
        }

        if (count($changed)) {
            $this->success(implode('<br>', $messages), true);
        } else {
            $this->failure(implode('<br>', $messages), true);
        }

        $this->ctrl->redirect($this);
    }

    private function exportStepsAction(): Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "export_steps",
            $this->plugin->txt('export_steps'),
            [$this, "exportSteps"],
            fn(CorrectionItem $item) => $item->getWriter()->canGetSight(),
            Action\Type::Single
        );
    }

    public function exportSteps(CorrectionItem $item): void
    {
        // TODO: implement download
    }

    private function changeCorrectorAction(): Action\Form
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "change_corrector",
            $this->plugin->txt('change_corrector'),
            $this->lng->txt("submit"),
            [$this, 'changeCorrectorFields'],
            [$this, 'changeCorrector'],
            fn(CorrectionItem $item) => true,
            Action\Type::Standard
        )->withContent([
            $this->ui_factory->messageBox()->info($this->plugin->txt("change_corrector_info"))
        ])->withTransformations([$this, "changeCorrectorCheck"]);
    }

    public function changeCorrectorCheck(array $items): array
    {
        $writer_ids = array_map(fn(CorrectionItem $item) => $item->getWriter()->getId(), $items);

        return [
            $this->refinery->custom()->constraint(
                function (array $var) use ($writer_ids) {
                    $result = $this->assignment_service->assignMultiple(
                        $this->task_info->getId(),
                        $var["first_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                        $var["second_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                        $var["stitch_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
                        $writer_ids,
                        true
                    );
                    return count($result['invalid']) === 0;
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
        $names = array_map(fn(UserData $u) => $u->getListname(true), $this->user_service->getUsersByIds($corrector_ids));

        foreach ($corrector_ids as $id => $user_id) {
            $corrector_list[$id] = $names[$user_id];
        }

        $fields = [];
        $fields["first_corrector"] = $this->ui_factory->input()->field()->select(
            $this->correction_settings->getRequiredCorrectors() > 1
                ? $this->plugin->txt("grading_pos_first")
                : $this->plugin->txt("assignment_pos_single"),
            $corrector_list
        )->withRequired(true)
                                                      ->withValue(CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT)
                                                      ->withAdditionalTransformation($this->refinery->kindlyTo()->int());

        if ($this->correction_settings->getRequiredCorrectors() > 1) {
            $fields["second_corrector"] = $this->ui_factory->input()->field()->select(
                $this->plugin->txt("grading_pos_second"),
                $corrector_list
            )->withRequired(true)
                                                           ->withValue(CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT)
                                                           ->withAdditionalTransformation($this->refinery->kindlyTo()->int());

            if (count($items) == 1 && $items[0]->getWriter()->getCombinedStatus() === CombinedStatus::STITCH_NEEDED) {
                $fields["stitch_corrector"] = $this->ui_factory->input()->field()->select(
                    $this->plugin->txt("grading_pos_stitch"),
                    $corrector_list
                )->withRequired(true)
                                                               ->withValue(CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT)
                                                               ->withAdditionalTransformation($this->refinery->kindlyTo()->int());
            }
        }

        if (count($items) == 1) { // Pre set the assigned correctors if its just one corrector
            $item = array_pop($items);
            $assignments = [];
            foreach ($this->assignment_service->allByWriterId($item->getWriter()->getId()) as $assignment) {
                $assignments[$assignment->getPosition()->value] = $assignment;
            }

            $fields["first_corrector"] = $fields["first_corrector"]->withValue(
                isset($assignments[0]) ?
                    $assignments[0]->getCorrectorId() :
                    CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT
            );

            if ($this->correction_settings->getRequiredCorrectors() > 1) {
                $fields["second_corrector"] = $fields["second_corrector"]->withValue(
                    isset($assignments[1]) ?
                        $assignments[1]->getCorrectorId() :
                        CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT
                );

                if ($item->getWriter()->getCombinedStatus() === CombinedStatus::STITCH_NEEDED) {
                    $fields["stitch_corrector"] = $fields["stitch_corrector"]->withValue(
                        isset($assignments[2]) ?
                            $assignments[2]->getCorrectorId() :
                            CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT
                    );
                }
            }
        }
        return $fields;
    }

    public function changeCorrector(array $items, array $data)
    {
        $this->assignment_service->assignMultiple(
            $this->task_info->getId(),
            $data["first_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
            $data["second_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
            $data["stitch_corrector"] ?? CorrectorAssignmentsService::UNCHANGED_CORRECTOR_ASSIGNMENT,
            array_map(fn(CorrectionItem $x) => $x->getWriter()->getId(), $items)
        );
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("corrector_assignment_changed"), true);
        $this->ctrl->redirect($this, 'showItems');
    }

    private function mailToWriterOrCorrectorAction(): Action\Modal
    {
        return $this->plugin_ui_factory->table()->action()->modal(
            "mail_to_writer_or_corrector",
            $this->plugin->txt('mail_to_writer_or_corrector'),
            [$this, "mailToWriterOrCorrectorModal"],
            fn(CorrectionItem $item) => true,
            Action\Type::Standard
        );
    }

    /**
     * @param CorrectionItem[] $items
     * @return \ILIAS\UI\Component\Modal\RoundTrip
     */
    public function mailToWriterOrCorrectorModal(array $items): \ILIAS\UI\Component\Modal\RoundTrip
    {
        $writer_ids = array_map(fn(CorrectionItem $item) => $item->getWriter()->getId(), $items);
        $writer_id_query = "&" . http_build_query(["wid" => $writer_ids]);

        $fields = [
            'writer' => $this->ui_factory->input()->field()->checkbox($this->plugin->txt('participant'))
        ];

        for ($i = 1; $i <= $this->correction_settings->getRequiredCorrectors(); $i++) {
            $fields['corrector' . $i] = $this->ui_factory->input()->field()->checkbox(
                sprintf($this->plugin->txt('corrector_x'), $i)
            );
        }

        return $this->ui_factory->modal()->roundtrip(
            $this->plugin->txt('mail_for_selected_essays'),
            [],
            $fields,
            $this->ctrl->getFormAction($this, "mailToWriterOrCorrector") . $writer_id_query
        )->withActionButtons([
            $this->ui_factory->button()->primary($this->plugin->txt('write_mail'), "#")
        ]);
    }

    public function mailToWriterOrCorrector()
    {
        $form = $this->mailToWriterOrCorrectorModal([]);
        if (!($data = $form->getData()) !== null) {
            $to_writer = false;
            $to_correctors = [];

            if (isset($data["writer"])) {
                $to_writer = true;
            }

            for ($i = 1; $i <= $this->correction_settings->getRequiredCorrectors(); $i++) {
                if (isset($data['corrector' . $i])) {
                    $to_correctors[$i - 1] = true;
                }
            }
            $writer_ids = [];
            if ($this->http->wrapper()->query()->has('wid')) {
                $writer_ids = $this->http->wrapper()->query()->retrieve(
                    'wid',
                    $this->refinery->kindlyTo()->listOf($this->refinery->to()->int())
                );
            }

            $assignments = $this->assignment_service->all();
            $correctors = $this->corrector_service->all();
            $writers = $this->writer_service->all();

            $user_ids = [];
            foreach ($assignments as $assignment) {
                if (in_array($assignment->getWriterId(), $writer_ids)) {
                    if ($to_writer
                        && !empty($writer = $writers[$assignment->getWriterId()])) {
                        $user_ids[] = $writer->getUserId();
                    }
                    if (isset($to_correctors[$assignment->getPosition()->value])
                        && !empty($corrector = $correctors[$assignment->getCorrectorId()])) {
                        $user_ids[] = $corrector->getUserId();
                    }
                }
            }

            $users = $this->user_service->getUsersByIds($user_ids);
            $logins = array_map(fn(UserData $u) => $u->getLogin(), $users);
            $this->openMailForm($logins, 'showItems');
        }
    }

    private function viewStitchDecisionAction(): Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "view_stitch_decision",
            $this->plugin->txt('view_stitch_comment'),
            [$this, "viewStitchDecision"],
            fn(CorrectionItem $item) => !empty($item->getWriter()->getStitchComment()),
            Action\Type::Single
        );
    }

    public function viewStitchDecision(CorrectionItem $item)
    {
        //TODO: implement open corrector
    }

    private function downloadCorrectedPdfAction(): Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "download_corrected_pdf",
            $this->plugin->txt('download_corrected_pdf'),
            [$this, "downloadCorrectedPdf"],
            fn(CorrectionItem $item) => $item->canDownloadCorrectionPdf(),
            Action\Type::Single
        );
    }

    public function downloadCorrectedPdf(CorrectionItem $item)
    {
        // TODO: implement download
    }

    private function downloadWrittenPdfAction(): Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "download_written_pdf",
            $this->plugin->txt('download_written_pdf'),
            [$this, "downloadWrittenPdf"],
            fn(CorrectionItem $item) => $item->getWriter()->canDownloadWrittenPdf(),
            Action\Type::Single
        );
    }

    public function downloadWrittenPdf(CorrectionItem $item)
    {
        // TODO: implement download
    }

    private function viewCorrectionAction(): Action\Direct
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "view_correction",
            $this->plugin->txt('view_correction'),
            [$this, "viewCorrections"],
            fn(CorrectionItem $item) => true,
            Action\Type::Single
        );
    }

    public function viewCorrections(CorrectionItem $item)
    {
        //TODO: implement open corrector
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
        // todo: add toolbar
        // $this->buildToolbar($this->toolbar);

        $location_avaiable = $this->hasLocations();
        $corrections = $this->getCorrectionSettings()->getRequiredCorrectors();
        if ($corrections > 1 && $this->getCorrectionSettings()->getStitchAfterProcedure()) {
            $corrections++;
        }
        $multi = $this->getSettings()->getMultiTasks();
        $has_started = $this->getSettings()->getWritingStart() !== null ? $this->getSettings()->getWritingStart() < new \DateTimeImmutable() : true;

        $table_parent = new CorrectionTableParent($this->dic, $this->plugin, [$this->object->getAssId()], $this->ctrl->getFormAction($this));
        $table_parent->setHasColumns(
            array_merge(
                ["image", "name", "login", "pseudonym", $location_avaiable ? "location" : null, "status", $multi ? "task" : null,
                         "writing_last_save", "word_count", "pdf_version", "result", "points", "grade", "finalized", "finalized_date", "finalized_name", "finalized_from_status"],
                ...array_map(fn($p) => ["corr_{$p}", "corr_{$p}_name", "corr_{$p}_status", "corr_{$p}_points", $multi ? "corr_{$p}_grade" : null, "corr_{$p}_authorized"], range(0, $corrections - 1)),
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
            } elseif (!empty($this->assignment_service->countMissing())) {
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

    public function getTableActions(): array
    {
        return [
//            $this->viewCorrectionAction(),
//            $this->downloadWrittenPdfAction(),
//            $this->downloadCorrectedPdfAction(),
//            $this->mailToWriterOrCorrectorAction(),
            $this->changeCorrectorAction(),
//            $this->exportStepsAction(),
            $this->removeAuthorizationsAction(),
            $this->exportTableAction(),
        ];
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
}
