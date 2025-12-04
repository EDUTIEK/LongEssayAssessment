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
use Edutiek\AssessmentService\Task\AssessmentStatus\CombinedStatus;
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

/**
 * Correction Admin GUI class
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin\CorrectionAdminGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectionAdminGUI extends BaseGUI implements DataTableParent, FilterParent
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

        $this->initForTask();

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
            fn(CorrectionItem $item) => !$item->getWriter()->isCorrectionFinalized(),
            Action\Type::Standard,
        );
    }

    private function removeAuthorizations()
    {
        $writer_ids = $this->confirmationIds();
        $valid = [];
        $invalid = [];

        foreach ($writer_ids as $writer_id) {
            if (($writer = $this->writer_service->oneByWriterId($writer_id)) !== null) {
                if ($this->correction_process->removeAuthorizations($this->task_info->getId(), $writer, $this->user->getId())) {
                    $valid[] = $writer;
                } else {
                    $invalid[] = $writer;
                }
            }
        }

        $users = $this->user_service->getUsersByIds(array_map(fn($x) => $x->getUserId(), array_merge($valid, $invalid)));

        if (count($invalid) > 0) {
            $names = [];
            foreach ($invalid as $writer) {
                $user = $users[$writer->getUserId()] ?? null;
                $names[] = ($user?->getFullname(true) ?? "unknown") . ' [' . $writer->getPseudonym() . ']';
            }
            $this->tpl->setOnScreenMessage("failure", sprintf($this->plugin->txt('remove_authorizations_for_failed'), implode(", ", $names)), true);
        }
        if (count($valid) > 0) {
            $names = [];
            foreach ($valid as $writer) {
                $user = $users[$writer->getUserId()] ?? null;
                $names[] = ($user?->getFullname(true) ?? "unknown") . ' [' . $writer->getPseudonym() . ']';
            }
            $this->tpl->setOnScreenMessage("success", sprintf($this->plugin->txt('remove_authorizations_for_done'), implode(", ", $names)), true);
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
        $names = array_map(fn(UserData $u) => $u->getFullname(true), $this->user_service->getUsersByIds($corrector_ids));

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

            if (count($items) == 1 && $items[0]->getCombinedStatus() === CombinedStatus::STITCH_NEEDED) {
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

                if ($item->getCombinedStatus() === CombinedStatus::STITCH_NEEDED) {
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
            "status" => $cf->status($this->plugin->txt("status"))->withIsOptional(true, true)->withIsSortable(true),
            "writing_last_save" => $cfp->nullableDate($this->plugin->txt("writing_last_save"), $date_with_seconds)->withIsOptional(true, $has_started)->withIsSortable(true)->withHighlight($multi),
            "word_count" => $cf->number($this->plugin->txt('word_count'))->withIsOptional(false, $has_started)->withIsSortable(true)->withHighlight($multi),
            "pdf_version" => $cf->boolean($this->plugin->txt("pdf_version"), $this->lng->txt("yes"), $this->lng->txt("no"))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi)
        ];

        foreach (range(0, $corrections - 1) as $p) {
            if ($corrections == 1) {
                $cor = $this->plugin->txt("assignment_pos_single");
            } else {
                switch ($p) {
                    case 0: $cor = $this->plugin->txt("grading_pos_first");
                        break;
                    case 1: $cor = $this->plugin->txt("grading_pos_second");
                        break;
                    default: $cor = $this->plugin->txt("assignment_pos_other");
                        break;
                }
            }
            $cor = $cor . " ";
            $columns += [
                "corr_{$p}" => $cf->text($cor)->withIsOptional(true, true)->withIsSortable(true)->withIsSortable(false)->withHighlight($multi),
                "corr_{$p}_name" => $cf->text($cor . $this->lng->txt("name"))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi),
                "corr_{$p}_status" => $cf->status($cor . $this->plugin->txt("status"))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi),
                "corr_{$p}_points" => $cfp->nullableNumber($cor . $this->plugin->txt("points"))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi),
            ];

            if (!$multi) {
                $columns["corr_{$p}_grade"] = $cf->text($cor . $this->lng->txt("grade"))->withIsOptional(true, false)->withIsSortable(true);
            }
            $columns["corr_{$p}_authorized"] = $cf->boolean($cor . $this->plugin->txt("grading_authorized"), $this->lng->txt('yes'), $this->lng->txt('no'))->withIsOptional(true, false)->withIsSortable(true)->withHighlight($multi);
        }

        $columns += [
            "result" => $cf->text($this->plugin->txt("result"))->withIsOptional(true, true)->withIsSortable(false),
            "points" => $cfp->nullableNumber($this->plugin->txt("final_points"))->withIsOptional(true, false)->withIsSortable(true),
            "grade" => $cf->text($this->plugin->txt("final_grade"))->withIsOptional(true, false)->withIsSortable(true),
            "finalized" => $cfp->nullableDate($this->plugin->txt("finalized_at"), $date_without_seconds)->withIsOptional(true, false)->withIsSortable(true),
            "finalized_from" => $cf->text($this->plugin->txt("finalized_from"))->withIsOptional(true, false)->withIsSortable(true),
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
//            $this->viewCorrectionAction(),
//            $this->viewStitchDecisionAction(),
//            $this->downloadWrittenPdfAction(),
//            $this->downloadCorrectedPdfAction(),
//            $this->mailToWriterOrCorrectorAction(),
            $this->changeCorrectorAction(),
//            $this->drawStitchDecisionAction(),
//            $this->exportStepsAction(),
//            $this->removeAuthorizationsAction(),
//            $this->exportTableAction(),
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
            $this->assessment_status->allWriterCombinedStatus(),
            $this->assignment_service->all(),
            $this->summary_service->allByTaskId($this->task_info->getId()),
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
        foreach ($this->summary_service->allByTaskIdAndWriterId($this->task_info->getId(), $writer->getId()) as $summary) {
            $summaries[$summary->getCorrectorId()][] = $summary;
        }


        $summary_by_pos = [];
        $corrector_by_pos = [];
        foreach ($this->assignment_service->all() as $a) {
            $summary = $summaries[$a->getCorrectorId()] ?? null;
            $corrector = $this->corrector_service->oneById($a->getCorrectorId());
            $summary_by_pos[$a->getPosition()->value] = $summary;
            $corrector_by_pos[$a->getPosition()->value] = $corrector;
        }

        return new CorrectionItem(
            $id,
            $writer,
            [$this->user_service->getUser($writer->getUserId())],
            $this->getLocation($writer->getLocation()),
            $this->essay_service->oneByWriterIdAndTaskId($writer->getId(), $this->task_info->getId()),
            $this->assessment_status->oneWriterCombinedStatus($writer),
            $summary_by_pos,
            $corrector_by_pos,
            $this->user_service->getUserDisplay($writer->getUserId(), null)
        );
    }

    public function getFilterInputs(): array
    {
        $status = [
            (string) CombinedStatus::WRITING_EXCLUDED->value => $this->plugin->txt(CombinedStatus::WRITING_EXCLUDED->langVar()),
            (string) CombinedStatus::WRITING_NOT_STARTED->value => $this->plugin->txt(CombinedStatus::WRITING_NOT_STARTED->langVar()),
            (string) CombinedStatus::WRITING_STARTED->value => $this->plugin->txt(CombinedStatus::WRITING_STARTED->langVar()),
            (string) CombinedStatus::WRITING_AUTHORIZED->value => $this->plugin->txt(CombinedStatus::WRITING_AUTHORIZED->langVar()),
            (string) CombinedStatus::OPEN->value => $this->plugin->txt(CombinedStatus::OPEN->langVar()),
            (string) CombinedStatus::APPROXIMATION->value => $this->plugin->txt(CombinedStatus::APPROXIMATION->langVar()),
            (string) CombinedStatus::CONSULTING->value => $this->plugin->txt(CombinedStatus::CONSULTING->langVar()),
            (string) CombinedStatus::STITCH_NEEDED->value => $this->plugin->txt(CombinedStatus::STITCH_NEEDED->langVar()),
            (string) CombinedStatus::FINALIZED->value => $this->plugin->txt(CombinedStatus::FINALIZED->langVar()),
        ];
        $locations = [];
        foreach ($this->getLocations() as $location) {
            $locations[$location->getId()] = $location->getTitle();
        }

        return [
            "name" => $this->ui_factory->input()->field()->text($this->plugin->txt("participants")),
            "location" => $this->ui_factory->input()->field()->multiselect($this->plugin->txt("locations"), $locations),
            "min_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("min_word_count"))
                                            ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0)),
            "max_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("max_word_count"))
                                            ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(1)),
            "status" => $this->ui_factory->input()->field()->multiSelect($this->plugin->txt("status"), $status),
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
