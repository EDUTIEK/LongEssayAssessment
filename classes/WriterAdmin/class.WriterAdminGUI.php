<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Test\Participants\TableAction;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\FilterParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\System\Data\UserData;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDisplay;
use Edutiek\AssessmentService\Assessment\Data\WritingStatus;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\CorrectionSettings;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;

/**
 * Writer Admin GUI class
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI: ilRepositorySearchGUI
 */
class WriterAdminGUI extends BaseGUI implements DataTableParent, FilterParent
{
    use ConfirmationIds;

    const FILTER_YES= "1";
    const FILTER_NO = "2";
    private OrgaService $orga_service;
    private OrgaSettings $settings;
    private WriterService $writer_service;
    private UserService $user_service;
    private ?array $location = null;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->writer_service = $this->assessment_api->writer();
        $this->user_service = $this->system_api->user();
        $this->orga_service = $this->assessment_api->orgaSettings();
    }

    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            case 'ilrepositorysearchgui':
                $rep_search = new \ilRepositorySearchGUI();
                $rep_search->addUserAccessFilterCallable([$this, 'filterUserIdsByParticipants']);
                $rep_search->setCallback($this, "assignWriters");
                $this->ctrl->setReturn($this, 'showItems');
                $ret = $this->ctrl->forwardCommand($rep_search);
                break;
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
        $this->toolbar->setFormAction($this->ctrl->getFormAction($this));

        \ilRepositorySearchGUI::fillAutoCompleteToolbar(
            $this,
            $this->toolbar,
            array()
        );

        // search button
        $delete_writer_data_button = $this->ui_factory->button()->standard(
            $this->plugin->txt("search_participants"),
            $this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI', 'start')
        );
        $this->toolbar->addComponent($delete_writer_data_button);

        // spacer
        $this->toolbar->addSeparator();

        #$delete_writer_data_modal = $this->buildDeleteWriterDataModal();
        $delete_writer_data_button = $this->ui_factory->button()->standard($this->plugin->txt("delete_writer_data"), "#");
        #                                             ->withOnClick($delete_writer_data_modal->getShowSignal());
        $this->toolbar->addComponent($delete_writer_data_button);

        $table = $this->plugin_ui_factory->table()->dataTable('writer_admin_table', $this);
        $table->executeAction();
        $this->tpl->setContent($this->renderer->render($table));
    }

    public function assignWriters(array $a_usr_ids, $a_type = null)
    {
        if (count($a_usr_ids) <= 0) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt('no_writer_set'), true);
            $this->ctrl->redirect($this, "showStartPage");
        }

        foreach($a_usr_ids as $id) {
            $this->writer_service->getByUserId($id);
        }

        if(count($a_usr_ids) == 1) {
            $anchor =  "user_" . $a_usr_ids[0];
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt('assign_writer_success'), true);
        $this->ctrl->redirect($this, "showItems", $anchor ?? "");
    }

    public function filterUserIdsByParticipants($a_user_ids)
    {
        $writers = array_map(fn ($row) => $row->getUserId(), $this->writer_service->all());
        return array_filter($a_user_ids, fn($user_id) => !in_array((int)$user_id, $writers));
    }

    public function viewProcessing(WriterItem $writer)
    {

    }
    public function exportSteps(WriterItem $writer)
    {

    }
    public function addLogEntry(WriterItem $writer)
    {

    }

    /**
     * @param WriterItem[] $writer
     * @return void
     */
    public function mailToWriter(array $writer)
    {
        $logins = [];
        foreach ($writer as $w) {
            if (!empty($w->getUserData())) {
                $user_ids[] = $w->getUserData()->getLogin();
            }
        }

        $this->openMailForm($logins, 'showStartPage');
    }
    public function authorizeWriting(WriterItem $writer)
    {

    }
    public function unauthorizeWriting(WriterItem $writer)
    {

    }
    /**
     * @param WriterItem[] $writer
     * @param array $data
     * @return void
     */
    public function workingTimeChange(array $writer, array $data)
    {

    }
    /**
     * @param WriterItem[] $writer
     * @param array $data
     * @return void
     */
    public function changeLocation(array $writer, array $data)
    {
        $location = $data['location'];
        foreach($writer as $w) {
            $w->getWriter()->setLocation($location);
        }
    }
    public function excludeParticipant(WriterItem $writer)
    {

    }
    public function repealExcludeParticipant(WriterItem $writer)
    {

    }
    public function removeWriter(WriterItem $writer)
    {

    }

    public function getColumnMapping(
        WriterItem|\ILIAS\Plugin\LongEssayAssessment\UI\Table\Item $item,
        ?array $additional_parameters
    ): array {
        $writer = $item->getWriter();
        $user_data = $item->getUserData();
        $user_display = $item->getUserDisplay();
        $renderer = $this->renderer;
        $unknown = $this->lng->txt('unknown');

        $image = function() use($renderer, $user_data, $user_display, $unknown) {
            if (!empty($user_display?->getImageUrl())) {
                $avatar = $this->ui_factory->symbol()->avatar()->picture($user_display->getImageUrl(), $user_data->getLogin());
            } else {
                $avatar = $this->ui_factory->symbol()->avatar()->letter($user_data?->getFullname(false)??$unknown);
            }

            return $renderer->render($avatar);
        };

        $status = match($writer->getStatus()) {
            WritingStatus::NOT_STARTED => $this->plugin->txt("status_writing_not_started"),
            WritingStatus::STARTED => $this->plugin->txt("status_writing_started"),
            WritingStatus::EXCLUDED => $this->plugin->txt("status_writing_excluded_from") . " " .
                ($item->getExecludedFromFullname()??$unknown),
            WritingStatus::AUTHORIZED => $this->plugin->txt("status_writing_authorized_from") . " " .
                ($writer->getUserId() === $writer->getWritingAuthorizedBy()
                    ? $this->plugin->txt("participant")
                    : ($item->getAuthorizedFromFullname()??$unknown))
        };

        $working_start = $writer->getWorkingStart();
        $working_end = $writer->getWritingAuthorized();
        $exam_start = $writer->getEarliestStart() ?? $this->getSettings()->getWritingStart();
        $exam_end = $writer->getLatestEnd() ?? $this->getSettings()->getWritingEnd();
        $assessment_duration = $writer->getTimeLimitMinutes() ?? $this->getSettings()->getWritingLimitMinutes();
        if(empty($assessment_duration) && $exam_start !== null && $exam_end !== null) {
            $assessment_duration = $exam_start->diff($exam_end)->i;
        } else {
            $assessment_duration = null;
        }

        $exam_limit_changed = $writer->getEarliestStart() !== null || $writer->getLatestEnd() !== null || !($writer->getTimeLimitMinutes() === null || $writer->getTimeLimitMinutes() === 0);

        return [
            "image" => $image,
            "name" => $user_data->getListname(false),
            "login" => $user_data->getLogin(),
            "pseudonym" => $writer->getPseudonym(),
            "location" => $this->getLocation($writer->getLocation()),
            "status" => $status,
//            "writing_last_save" => $writer->,
            "working_period" => [$working_start, $working_end],
            "working_duration" => $writer->getWorkingStart() !== null
                ? date_diff($working_start, $working_end ?? new \DateTimeImmutable('now'))
                : null,
            "assessment_period" => [$exam_start, $exam_end],
            "assessment_duration" => $assessment_duration ?? "",
            "time_limit_changed" => $exam_limit_changed,
            "authorized" => $writer->getWritingAuthorized(),
            "authorized_from" => $writer->getWritingAuthorized() !== null  && $writer->getUserId() === $writer->getWritingAuthorizedBy()
                ? $this->plugin->txt("participant")
                : ($item->getAuthorizedFromFullname() ?? $unknown),
            "excluded" => $writer->getWritingExcluded(),
            "excluded_from" => $item->getExecludedFromFullname() ?? $unknown,
        ];
    }

    private function intervalFormat(bool $has_days, bool $has_seconds) : string
    {
        $interval_format = "%d " . $this->lng->txt('days') . " %h " . $this->lng->txt('hours') . ' %minutes ' . $this->lng->txt('minutes');

        return ($has_days ? "%d " . $this->lng->txt('days') . " " : "") .
            "%h " . $this->lng->txt('hours') . ' %i ' . $this->lng->txt('minutes') .
            ($has_seconds ? " %s " . $this->lng->txt('seconds') : "");
    }

    public function getColumns(?array $additional_parameters): array
    {
        $cf = $this->ui_factory->table()->column();
        $cfp = $this->plugin_ui_factory->table()->column();

        $df = new \ILIAS\Data\Factory();
        $location_avaiable = $this->hasLocations();
        $duration_avaiable = !empty($this->getSettings()->getWritingLimitMinutes());
        $date_without_seconds = $df->dateFormat()->withTime24($df->dateFormat()->standard());
        $date_with_seconds = $df->dateFormat()->amend($date_without_seconds)->colon()->seconds()->get();
        $long_exam = true;
        $a_interval_format = $this->intervalFormat($long_exam, false);
        $w_interval_format = $this->intervalFormat($long_exam, true);

        return [
            "image" => $cf->text($this->lng->txt("image"))->withIsOptional(true, false)->withIsSortable(false),
            "name" => $cf->text($this->lng->txt("name"))->withIsOptional(false, true)->withIsSortable(true),
            "login" => $cf->text($this->lng->txt("login"))->withIsOptional(false, true)->withIsSortable(true),
            "pseudonym" => $cf->text($this->plugin->txt("pseudonym"))->withIsOptional(false, true)->withIsSortable(true),
            "location" => $cf->text($this->plugin->txt("location"))->withIsOptional(true, $location_avaiable)->withIsSortable(true),
            "status" => $cf->status($this->plugin->txt("essay_status"))->withIsOptional(false, true)->withIsSortable(true),
//            "writing_last_save" => $cf->date($this->plugin->txt("writing_last_save"), $date_long)->withIsOptional(true, true)->withIsSortable(true),
            "working_period" => $cfp->unboundTimeSpan($this->plugin->txt("working_period"), $date_with_seconds)->withIsOptional(false, true)->withIsSortable(true),
            "working_duration" => $cfp->interval($this->plugin->txt("working_duration"), $w_interval_format)->withIsOptional(false, true)->withIsSortable(true),
            "assessment_period" => $cfp->unboundTimeSpan($this->plugin->txt("assessment_period"), $date_with_seconds)->withIsOptional(false, true)->withIsSortable(true),
            "assessment_duration" =>  $cfp->interval($this->plugin->txt("assessment_duration"), $a_interval_format)->withIsOptional(true, $duration_avaiable)->withIsSortable(true),
            "time_limit_changed" => $cf->boolean($this->plugin->txt("time_limit_changed"), $this->lng->txt("yes"), $this->lng->txt("no"))->withIsOptional(true, true)->withIsSortable(true),
            "authorized" => $cfp->nullableDate($this->plugin->txt("writing_autorized_at"), $date_without_seconds)->withIsOptional(false, true)->withIsSortable(true),
            "authorized_from" => $cf->text($this->plugin->txt("writing_autorized_from"))->withIsOptional(true, true)->withIsSortable(true),
            "excluded" => $cfp->nullableDate($this->plugin->txt("writing_excluded_at"), $date_without_seconds)->withIsOptional(false, true)->withIsSortable(true),
            "excluded_from" => $cf->text($this->plugin->txt("writing_excluded_from"))->withIsOptional(true, true)->withIsSortable(true),
        ];
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        return count($this->writer_service->all());
    }

    public function getTableActions(): array
    {
        return [
            $this->viewProccessingAction(),
            $this->exportStepsAction(),
            $this->addLogEntryAction(),
            $this->mailToWriterAction(),
            $this->authorizeWritingAction(),
            $this->unauthorizeWritingAction(),
            $this->workingTimeChangeAction(),
            $this->changeLocationAction(),
//            $this->pdfVersionDownloadAction(),
//            $this->editPdfVersionAction(),
//            $this->changeTextToPdfAction(),
            $this->excludeParticipantAction(),
            $this->repealExcludeParticipantAction(),
            $this->removeWriterAction(),
        ];
    }

    private function viewProccessingAction()
    {
        return $this->plugin_ui_factory->table()->action()->modal(
            "view_processing",
            $this->plugin->txt("view_processing"),
            [$this, "viewProcessing"],
            fn (WriterItem $item) => $item->getWriter()->canGetSight(),
            Action\Type::Single
        );
    }

    private function exportStepsAction()
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "export_steps",
            $this->plugin->txt("export_steps"),
            [$this, "exportSteps"],
            fn (WriterItem $item) => $item->getWriter()->canGetSight(),
            Action\Type::Single
        );
    }

    public function addLogEntryAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "add_log_entry",
            $this->plugin->txt("add_log_entry_for writer"),# Todo: Fix language entry
            $this->lng->txt("add"),
            [$this, "addLogEntryFields"],
            [$this, "addLogEntry"],
            fn (WriterItem $writer) => true,
            Action\Type::Single
        );
    }

    public function addLogEntryFields(WriterItem $writer)
    {
        return [
            $this->ui_factory->input()->field()->textarea($this->plugin->txt("log_entry_text")),
        ];
    }

    public function mailToWriterAction()
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "mail_to_writer",
            $this->plugin->txt("mail_to_writer"),
            [$this, "mailToWriter"],
            fn (WriterItem $writer) => true,
            Action\Type::Standard
        );
    }

    public function authorizeWritingAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "authorize_writing",
            $this->plugin->txt("authorize_writing"),
            $this->plugin->txt("authorize_writing"),
            $this->plugin->txt("authorize_writing_confirmation"),
            $this->ctrl->getFormAction($this, 'authorizeWriting'),
            fn (WriterItem $item) => "Item " . $item->getId(),
            fn (WriterItem $item) => $item->getWriter()->canGetAuthorized(),
            Action\Type::Standard
        );
    }

    public function unauthorizeWritingAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "unauthorize_writing",
            $this->plugin->txt("unauthorize_writing"),
            $this->plugin->txt("unauthorize_writing"),
            $this->plugin->txt("unauthorize_writing_confirmation"),
            $this->ctrl->getFormAction($this, 'unauthorizeWriting'),
            fn (WriterItem $item) => "Item " . $item->getId(),
            fn (WriterItem $item) => $item->getWriter()->canGetUnauthorized(),
            Action\Type::Standard
        );
    }



    public function workingTimeChangeAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "change_working_time",
            $this->plugin->txt("change_working_time"),
            $this->lng->txt("change"),
            [$this, "workingTimeChangeFields"],
            [$this, "addLogEntry"],
            fn (WriterItem $item) => $item->getWriter()->canChangeWorkingTime(),
            Action\Type::Standard
        )->withActionButtons([$this->ui_factory->button()->standard(
            $this->plugin->txt("delete_individual_working_time"),
            $this->ctrl->getLinkTarget($this, 'deleteWorkingTime')
        )]);
    }

    public function workingTimeChangeFields(array $writer)
    {
        $factory = $this->ui_factory->input()->field();

        return [
            'earliest_start' =>  $factory->dateTime(
                $this->plugin->txt("writing_start"),
                $this->plugin->txt('label_general') . ' '
            )->withUseTime(true),
            'latest_end' => $factory->dateTime(
                $this->plugin->txt("writing_end"),
                $this->plugin->txt('label_general') . ' '
            )->withUseTime(true),
            'writing_limit_days' => $factory->numeric($this->plugin->txt("writing_limit_days")),
            'writing_limit_hours_minutes' =>  $factory->dateTime(
                $this->plugin->txt("writing_limit_hours_minutes"),
                $this->plugin->txt('label_general') . ' '
            )->withTimeOnly(true)
        ];
    }


    public function changeLocationAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "change_location",
            $this->plugin->txt("change_location"),
            $this->lng->txt("save"),
            [$this, "changeLocationFields"],
            [$this, "changeLocation"],
            fn (WriterItem $item) => $this->hasLocations(),
            Action\Type::Standard
        );


    }

    /**
     * @param WriterItem[] $writer
     * @return array
     */
    public function changeLocationFields(array $writer)
    {
        $options = [];
        foreach ($this->getLocations() as $id => $location) {
            $options[$id] = $location;
        }
        $location_input = $this->ui_factory->input()->field()->select($this->plugin->txt("location"), $options);

        if (count($writer) === 1 && $writer[0]?->getWriter()?->getLocation() !== null) {
            $location_input = $location_input->withValue($writer[0]->getWriter()->getLocation());
        }

        return    ["location" => $location_input];
    }

//    public function pdfVersionDownloadAction()
//    {
//        return $this->plugin_ui_factory->table()->action()->direct(
//            "pdf_version_download",
//            $this->plugin->txt("pdf_version_download"),
//            [$this, "pdfVersionDownload"],
//            fn (WriterItem $writer) => $writer->canDownloadPDFVersion(),
//            Action\Type::Single
//        );
//    }
//
//    public function pdfVersionDownload(WriterItem $writer)
//    {
//
//    }
//
//
//    public function editPdfVersionAction()
//    {
//        return $this->plugin_ui_factory->table()->action()->direct(
//            "pdf_version_edit",
//            $this->plugin->txt("pdf_version_edit"),
//            [$this, "editPdfVersion"],
//            fn (WriterItem $writer) => true,
//            Action\Type::Single
//        );
//    }
//
//    public function editPdfVersion(array $writer)
//    {
//
//    }
//
//    public function changeTextToPdfAction()
//    {
//        return $this->plugin_ui_factory->table()->action()->direct(
//            "change_text_to_pdf",
//            $this->plugin->txt("change_text_to_pdf"),
//            [$this, "changeTextToPdf"],
//            fn (WriterItem $writer) => true,
//            Action\Type::Standard
//        );
//    }
//
//    public function changeTextToPdf(array $writer)
//    {
//
//    }

    public function excludeParticipantAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "exclude_participant",
            $this->plugin->txt("exclude_participant"),
            $this->plugin->txt("exclude_participant"),
            $this->plugin->txt("exclude_participant_confirmation"),
            $this->ctrl->getFormAction($this, 'excludeParticipant'),
            fn (WriterItem $item) => $item->getUserData()->getFullname(true),
            fn (WriterItem $item) => $item->getWriter()->canGetExcluded(),
            Action\Type::Standard
        );
    }

    public function repealExcludeParticipantAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "repeal_exclude_participant",
            $this->plugin->txt("repeal_exclude_participant"),
            $this->plugin->txt("repeal_exclude_participant"),
            $this->plugin->txt("repeal_exclude_participant_confirmation"),
            $this->ctrl->getFormAction($this, 'repealExcludeParticipant'),
            fn (WriterItem $item) => $item->getUserData()->getFullname(true),
            fn (WriterItem $item) => $item->getWriter()->canGetRepealed(),
            Action\Type::Standard
        );
    }

    public function removeWriterAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "remove_writer",
            $this->plugin->txt("remove_writer"),
            $this->plugin->txt("remove_writer"),
            $this->plugin->txt("remove_writer_confirmation"),
            $this->ctrl->getFormAction($this, 'removeWriter'),
            fn (WriterItem $item) => $item->getUserData()->getFullname(true),
            fn (WriterItem $item) => true,
            Action\Type::Standard
        );
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null): \Generator
    {
        $writers = $this->writer_service->all();
        $user_ids = [];
        foreach ($writers as $key => $writer) {

            if(isset($filter_data['status']) && !in_array($writer->getStatus()->value, $filter_data['status'])){
                unset($writers[$key]);
                continue;
            }

            if(($changed = $filter_data['time_limit_changed'] ?? null) !== null && (
                ($writer->hasChangedTimeLimit() && $changed === self::FILTER_NO) ||
                (!$writer->hasChangedTimeLimit() && $changed === self::FILTER_YES)
                )) {
                unset($writers[$key]);
                continue;
            }

            if(isset($filter_data['location']) && !in_array($writer->getStatus(), $filter_data['location'])){
                unset($writers[$key]);
                continue;
            }

            $user_ids[$writer->getUserId()] = $writer->getUserId();
            if ($writer->getWritingAuthorizedBy() !== null) {
                $user_ids[$writer->getWritingAuthorizedBy()] = $writer->getWritingAuthorizedBy();
            }
            if ($writer->getWritingExcludedBy() !== null) {
                $user_ids[$writer->getWritingExcludedBy()] = $writer->getWritingExcludedBy();
            }
        }

        $users = $this->system_api->user()->getUsersByIds($user_ids);

        $user_displays = $this->system_api->user()->getUserDisplaysByIds($user_ids, null);

        foreach ($writers as $writer) {
            $user = $users[$writer->getUserId()] ?? null;
            $user_display = $user_displays[$writer->getUserId()] ?? null;
            $authorized_from = $writer->getWritingAuthorizedBy() !== null ? $users[$writer->getWritingAuthorizedBy()] ?? null : null;
            $excluded_from = $writer->getWritingExcludedBy() !== null ? $users[$writer->getWritingExcludedBy()] ?? null : null;

            if(isset($filter_data['name']) && !str_contains($writer->getPseudonym() . $user?->getFullname(true), $filter_data['name'])){
                continue;
            }

            yield new WriterItem($writer->getId(), $writer, $user, $user_display, $authorized_from, $excluded_from);
        }
    }

    public function getTableItem(int $id): \ILIAS\Plugin\LongEssayAssessment\UI\Table\Item
    {
        $writer = $this->writer_service->oneByWriterId($id);
        $user = $this->user_service->getUser($writer->getUserId());
        $user_display = $this->user_service->getUserDisplay($writer->getUserId(), null);
        $authorized_from = $writer->getWritingAuthorizedBy() !== null ? $users[$writer->getWritingAuthorizedBy()] ?? null : null;
        $excluded_from = $writer->getWritingExcludedBy() !== null ? $users[$writer->getWritingExcludedBy()] ?? null : null;

        return new WriterItem($writer->getId(), $writer, $user, $user_display, $authorized_from, $excluded_from);
    }

    public function getFilterInputs(): array
    {
        $field = $this->ui_factory->input()->field();

        $status = [
            (string)WritingStatus::NOT_STARTED->value => $this->plugin->txt("status_writing_not_started"),
            (string)WritingStatus::STARTED->value => $this->plugin->txt("status_writing_started"),
            (string)WritingStatus::EXCLUDED->value => $this->plugin->txt("status_writing_excluded"),
            (string)WritingStatus::AUTHORIZED->value => $this->plugin->txt("status_writing_authorized"),
        ];

        return [
            "name" => $field->text($this->plugin->txt("participants")),
            "location" =>  $field->multiselect($this->plugin->txt("locations"), $this->getLocations()),
            "status" => $field->multiSelect($this->plugin->txt("essay_status"), $status),
            "time_limit_changed" => $this->ui_factory->input()->field()->select(
                $this->plugin->txt("time_limit_changed"),
                [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
            )
        ];
    }

    public function getFilterInputActivation(): ?array
    {
        return [true, $this->hasLocations(), true, true];
    }

    protected function hasLocations() : bool
    {
        $location = $this->location ??= $this->assessment_api->location()->allTitles();

        return !empty($location);
    }

    protected function getLocations() : array
    {
        return $this->location ??= $this->assessment_api->location()->allTitles();
    }

    protected function getLocation(?int $id) : string
    {
        if($id === null) {
            return "";
        }

        $location = $this->getLocations();
        return $location[$id] ?? "";
    }

    protected function getSettings() : OrgaSettings
    {
        return $this->settings ??= $this->orga_service->get();
    }

    public function getFilterBaseAction(): string
    {
        return $this->ctrl->getFormAction($this, "showItems");
    }
}
