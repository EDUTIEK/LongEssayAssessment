<?php

namespace ILIAS\Plugin\LongEssayAssessment\GUI\Writer;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaService;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use Edutiek\AssessmentService\EssayTask\Essay\ClientService as EssayService;
use Edutiek\AssessmentService\EssayTask\AssessmentStatus\FullService as AssessmentStatus;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\UI\Component\Modal\RoundTrip;
use Edutiek\AssessmentService\Assessment\LogEntry\Type as LogEntryType;
use Edutiek\AssessmentService\Assessment\LogEntry\MentionUser as LogEntryMention;
use Edutiek\AssessmentService\Assessment\WorkingTime\ValidationError;
use Edutiek\AssessmentService\Assessment\Data\WritingStatus;
use ILIAS\Plugin\LongEssayAssessment\Assessment\WorkingTime\ValidationErrorStore;
use ILIAS\Plugin\LongEssayAssessment\Assessment\WorkingTime\IndividualValidator;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\HasColumns;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\HasFilterFields;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\InitialVisibleColumns;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\FilterParent;
use _PHPStan_2d0955352\Nette\Neon\Exception;

abstract class WriterTableGUI extends BaseGUI implements DataTableParent, FilterParent
{
    use ConfirmationIds;
    use HasColumns;
    use HasFilterFields;
    use InitialVisibleColumns;

    protected const FILTER_YES = "1";
    protected const FILTER_NO = "2";
    protected OrgaService $orga_service;
    protected OrgaSettings $settings;
    protected WriterService $writer_service;
    protected UserService $user_service;
    protected ?array $location = null;
    protected EssayService $essay_service;
    protected AssessmentStatus $assessment_status;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->writer_service = $this->assessment_api->writer();
        $this->user_service = $this->system_api->user();
        $this->orga_service = $this->assessment_api->orgaSettings();
        $this->essay_service = $this->essay_task_api->essay(true);
        $this->assessment_status = $this->essay_task_api->assessmentStatus();
    }

    public function viewProcessing(WriterItem $writer): RoundTrip
    {
        $link = $this->request->getUri()->__toString();

        $content = [];

        if ($this->getSettings()->getMultiTasks()) {
            $essay = [];
            foreach ($this->essay_service->allByWriterId($writer->getId()) as $e) {
                $essay[$e->getTaskId()] = $e;
            }

            foreach ($this->task_api->manager()->all() as $task_info) {
                $content[] = $this->ui_factory->panel()->standard($task_info->getTitle(), [
                    $this->ui_factory->legacy($essay[$task_info->getId()]?->getWrittenText() ?? "")
                ]);
            }
        } else {
            $essay = $this->essay_service->allByWriterId($writer->getId());
            $essay = !empty($essay) ? array_pop($essay) : null;

            $content[] = $this->ui_factory->legacy($essay?->getWrittenText() ?? "");
        }

        $task_infos = $this->task_api->manager()->all();
        $essay = $this->essay_service->allByWriterId($writer->getId());

        $sight_modal = $this->ui_factory->modal()->roundtrip(
            $this->plugin->txt("submission"),
            $content
        );
        $reload_button = $this->ui_factory->button()->standard($this->lng->txt("refresh"), "")
                                          ->withLoadingAnimationOnClick(true)
                                          ->withOnLoadCode(
                                              function ($id) use ($link) {
                                                  return
                                                      "$('#{$id}').click(function() { 
                                                        n_url = '{$link}';
                                                        text = $('#$id').html();
                                                        $('#$id').html('...');
                                                        il.UI.core.replaceContent($(this).closest('.modal').attr('id'), n_url, 'component');
                                                        $('#$id').html(text);
                                                        il.UI.button.deactivateLoadingAnimation('$id');
                                                        return false;
                                                      });";
                                              }
                                          );

        return $sight_modal->withActionButtons([$reload_button]);
    }
    public function exportSteps(WriterItem $writer)
    {
        //TODO: Implement export steps
    }
    public function addLogEntry(WriterItem $writer, array $data)
    {
        $this->assessment_api->logEntry()->addEntry(
            LogEntryType::WRITER_NOTE,
            LogEntryMention::fromSystem($writer->getWriter()->getUserId()),
            LogEntryMention::fromSystem($this->user->getId()),
            $data['entry'] ?? ""
        );

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("log_entry_created"), true);
        $this->ctrl->redirect($this);
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
                $logins[] = $w->getUserData()->getLogin();
            }
        }

        $this->openMailForm($logins, 'showStartPage');
    }
    public function authorizeWriting(WriterItem $writer)
    {
        // TODO: implement authorize
    }
    public function unauthorizeWriting(WriterItem $writer)
    {
        // TODO: implement unauthorize
    }
    /**
     * @param WriterItem[] $writer_items
     * @param array        $data
     * @return void
     */
    public function workingTimeChange(array $writer_items, array $data)
    {
        foreach ($writer_items as $item) {
            $writer = $item->getWriter();
            $earliest_start = $data['earliest_start'] ?? null;
            $latest_end = $data['latest_end'] ?? null;
            $writing_limit = (int) ($data['writing_limit_days'] ?? 0) * 24 * 60;
            if ($data['writing_limit_hours_minutes'] instanceof \DateTimeInterface) {
                list($hours, $minutes) = explode(':', $data['writing_limit_hours_minutes']->format('H:i'));
                $writing_limit += (int) $hours * 60 + (int) $minutes;
            }

            $ok = $this->writer_service->changeWorkingTime($writer, $earliest_start, $latest_end, $writing_limit, $this->dic->user()->getId());
            if ($ok) {
                $this->tpl->setOnScreenMessage("success", $this->lng->txt("settings_saved"), true);
            } else {
                $error = (implode(
                    '<br>',
                    array_map(
                        fn(ValidationError $error) => $this->plugin->txt('failure_' . $error->value),
                        $writer->getValidationErrors()
                    )
                ));
                $this->tpl->setOnScreenMessage("failure", $error, true);
            }
            $this->ctrl->redirect($this, 'showItems');
        }
    }

    protected function deleteWorkingTime()
    {
        foreach ($this->confirmationIds() as $writer_id) {
            $writer = $this->writer_service->oneByWriterId($writer_id);
            $this->writer_service->removeWorkingTime($writer, $this->dic->user()->getId());
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt(
            count($this->confirmationIds()) == 1 ? 'one_working_time_deleted' : 'x_working_times_deleted'
        ), true);
        $this->ctrl->redirect($this, 'showItems');
    }

    /**
     * @param WriterItem[] $writer_items
     * @param array $data
     * @return void
     */
    public function changeLocation(array $writer_items, array $data)
    {
        $location = $data['location'] !== "" ? (int) $data['location'] : null;

        foreach ($writer_items as $item) {
            $writer = $item->getWriter();
            $writer->setLocation($location);
            $this->writer_service->save($writer);
        }

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("location_assigned"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    /**
     * @param WriterItem[] $writer_item
     * @return void
     */
    public function excludeParticipants()
    {
        foreach ($this->confirmationIds() as $id) {
            $writer = $this->writer_service->oneByWriterId($id);
            if ($writer !== null) {
                $this->writer_service->exclude($writer, $this->dic->user()->getId());
            }
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("exclude_writer_success"), true);
        $this->ctrl->redirect($this, "showItems");
    }
    public function repealExcludeParticipants()
    {
        foreach ($this->confirmationIds() as $id) {
            $writer = $this->writer_service->oneByWriterId($id);
            if ($writer !== null) {
                $this->writer_service->repealExclusion($writer, $this->dic->user()->getId());
            }
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("exclude_writer_repeal_success"), true);
        $this->ctrl->redirect($this, "showItems");
    }
    public function removeWriter()
    {
        foreach ($this->confirmationIds() as $id) {
            $writer = $this->writer_service->oneByWriterId($id);
            if ($writer !== null) {
                $this->writer_service->remove($writer, $this->dic->user()->getId());
            }
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("remove_writer_success"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    public function editPdfVersion(array $writer)
    {
        //TODO: edit pdf version page
    }

    public function getColumnMapping(
        WriterItem|\ILIAS\Plugin\LongEssayAssessment\UI\Table\Item $item,
        ?array $additional_parameters
    ): array {
        $timezone = new \DateTimeZone($this->user->getTimeZone());
        $writer = $item->getWriter();
        $user_data = $item->getUserData();
        $user_display = $item->getUserDisplay();
        $essay_summary = $item->getEssaySummary();
        $renderer = $this->renderer;
        $unknown = $this->lng->txt('unknown');


        if (!empty($user_display?->getImageUrl())) {
            $avatar = $this->ui_factory->symbol()->avatar()->picture($user_display->getImageUrl(), $user_data->getLogin());
        } else {
            $avatar = $this->ui_factory->symbol()->avatar()->letter($user_data?->getFullname(false) ?? $unknown);
        }

        $status = match($writer->getWritingStatus()) {
            WritingStatus::NOT_STARTED => $this->plugin->txt("status_writing_not_started"),
            WritingStatus::STARTED => $this->plugin->txt("status_writing_started"),
            WritingStatus::EXCLUDED => $this->plugin->txt("writing_excluded_from") . " " .
                ($item->getExecludedFromFullname() ?? $unknown),
            WritingStatus::AUTHORIZED => $this->plugin->txt("writing_authorized_from") . " " .
                ($writer->getUserId() === $writer->getWritingAuthorizedBy()
                    ? $this->plugin->txt("participant")
                    : ($item->getAuthorizedFromFullname() ?? $unknown))
        };

        $working_start = $writer->getWorkingStart()?->setTimezone($timezone);
        $working_end = $writer->getWritingAuthorized()?->setTimezone($timezone);
        $exam_start = $writer->getEarliestStart() ?? $this->getSettings()->getWritingStart()?->setTimezone($timezone);
        $exam_end = $writer->getLatestEnd() ?? $this->getSettings()->getWritingEnd()?->setTimezone($timezone);
        $assessment_duration = $writer->getTimeLimitMinutes() ?? $this->getSettings()->getWritingLimitMinutes();
        if (empty($assessment_duration) && $exam_start !== null && $exam_end !== null) {
            $assessment_duration = $exam_start->diff($exam_end)->i;
        }

        $exam_limit_changed = $writer->hasChangedTimeLimit();

        return [
            "image" => $avatar,
            "name" => $user_data->getListname(false),
            "login" => $user_data->getLogin(),
            "pseudonym" => $writer->getPseudonym(),
            "location" => $this->getLocation($writer->getLocation()),
            "status" => $status,
            "writing_last_save" => $essay_summary?->getLastSave()?->setTimezone($timezone),
            "word_count" => $essay_summary?->getWords() ?? 0,
            "working_start" => $working_start,
            "working_end" => $working_end,
            "working_duration" => $working_start !== null
                ? date_diff($working_start, $working_end ?? (new \DateTimeImmutable('now', $timezone)))
                : null,
            "assessment_start" => $exam_start,
            "assessment_end" => $exam_end,
            "assessment_duration" => $assessment_duration ?? "",
            "time_limit_changed" => $exam_limit_changed,
            "authorized" => $writer->getWritingAuthorized()?->setTimezone($timezone),
            "authorized_from" => $writer->getWritingAuthorized() !== null && $writer->getUserId() === $writer->getWritingAuthorizedBy()
                ? $this->plugin->txt("participant")
                : ($item->getAuthorizedFromFullname() ?? $unknown),
            "excluded" => $writer->getWritingExcluded()?->setTimezone($timezone),
            "excluded_from" => $item->getExecludedFromFullname() ?? $unknown,
            "pdf_version" => $essay_summary?->hasPdfUploads() ?? false
        ];
    }

    private function intervalFormat(bool $has_days, bool $has_seconds): string
    {
        return ($has_days ? "%D:" : "") . "%H:%I" . ($has_seconds ? ":%S" : "");
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
        $a_interval_format = $this->intervalFormat($long_exam, false);
        $w_interval_format = $this->intervalFormat($long_exam, true);

        return $this->setInitialVisible($this->filterColumns(array_filter([
            "image" => $cfp->image($this->lng->txt("image"))->withIsOptional(true, false)->withIsSortable(false),
            "name" => $cf->text($this->lng->txt("name"))->withIsOptional(false, true)->withIsSortable(true),
            "login" => $cf->text($this->lng->txt("login"))->withIsOptional(false, true)->withIsSortable(true),
            "pseudonym" => $cf->text($this->plugin->txt("pseudonym"))->withIsOptional(true, true)->withIsSortable(true),
            "location" => $location_avaiable ? $cf->text($this->plugin->txt("location"))->withIsOptional(true, $location_avaiable)->withIsSortable(true) : null,
            "status" => $cf->status($this->plugin->txt("essay_status"))->withIsOptional(true, true)->withIsSortable(true),
            "writing_last_save" => $cfp->nullableDate($this->plugin->txt("writing_last_save"), $date_with_seconds)->withIsOptional(true, $has_started)->withIsSortable(true),
            "word_count" => $cf->number($this->plugin->txt('word_count'))->withIsOptional(false, $has_started)->withIsSortable(true),
            "working_start" => $cfp->nullableDate($this->plugin->txt("working_start"), $date_with_seconds)->withIsOptional(true, $has_started)->withIsSortable(true),
            "working_end" => $cfp->nullableDate($this->plugin->txt("working_end"), $date_with_seconds)->withIsOptional(true, $has_started)->withIsSortable(true),
            "working_duration" => $cfp->interval($this->plugin->txt("working_duration"), $w_interval_format)->withIsOptional(true, $has_started)->withIsSortable(true),
            "assessment_start" => $cfp->nullableDate($this->plugin->txt("assessment_start"), $date_without_seconds)->withIsOptional(true, false)->withIsSortable(true),
            "assessment_end" => $cfp->nullableDate($this->plugin->txt("assessment_end"), $date_without_seconds)->withIsOptional(true, false)->withIsSortable(true),
            "assessment_duration" => $cfp->interval($this->plugin->txt("assessment_duration"), $a_interval_format)->withIsOptional(true, $duration_avaiable)->withIsSortable(true),
            "time_limit_changed" => $cf->boolean($this->plugin->txt("time_limit_changed"), $this->lng->txt("yes"), $this->lng->txt("no"))->withIsOptional(true, true)->withIsSortable(true),
            "authorized" => $cfp->nullableDate($this->plugin->txt("writing_autorized_at"), $date_without_seconds)->withIsOptional(true, $has_started)->withIsSortable(true),
            "authorized_from" => $cf->text($this->plugin->txt("writing_autorized_from"))->withIsOptional(true, false)->withIsSortable(true),
            "excluded" => $cfp->nullableDate($this->plugin->txt("writing_excluded_at"), $date_without_seconds)->withIsOptional(true, $has_started)->withIsSortable(true),
            "excluded_from" => $cf->text($this->plugin->txt("writing_excluded_from"))->withIsOptional(true, false)->withIsSortable(true),
            "pdf_version" => $cf->boolean($this->plugin->txt("pdf_version"), $this->lng->txt("yes"), $this->lng->txt("no"))->withIsOptional(true, false)->withIsSortable(true),
        ])));
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        return count($this->writer_service->all());
    }

    protected function viewProccessingAction()
    {
        return $this->plugin_ui_factory->table()->action()->modal(
            "view_processing",
            $this->plugin->txt("view_processing"),
            [$this, "viewProcessing"],
            fn(WriterItem $item) => $item->getWriter()->canGetSight(),
            Action\Type::Single
        );
    }

    protected function exportStepsAction()
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "export_steps",
            $this->plugin->txt("export_steps"),
            [$this, "exportSteps"],
            fn(WriterItem $item) => $item->getWriter()->canGetSight(),
            Action\Type::Single
        );
    }

    protected function addLogEntryAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "add_log_entry",
            $this->plugin->txt("add_log_entry_for writer"),# Todo: Fix language entry
            $this->lng->txt("add"),
            [$this, "addLogEntryFields"],
            [$this, "addLogEntry"],
            fn(WriterItem $writer) => true,
            Action\Type::Single
        );
    }

    public function addLogEntryFields(WriterItem $writer)
    {
        return [
            $this->ui_factory->input()->field()->textarea($this->plugin->txt("log_entry_text")),
        ];
    }

    protected function mailToWriterAction()
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "mail_to_writer",
            $this->plugin->txt("mail_to_writer"),
            [$this, "mailToWriter"],
            fn(WriterItem $writer) => true,
            Action\Type::Standard
        );
    }

    protected function authorizeWritingAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "authorize_writing",
            $this->plugin->txt("authorize_writing"),
            $this->plugin->txt("authorize_writing"),
            $this->plugin->txt("authorize_writing_confirmation"),
            $this->ctrl->getFormAction($this, 'authorizeWriting'),
            fn(WriterItem $item) => "Item " . $item->getId(),
            fn(WriterItem $item) => $item->getWriter()->canGetAuthorized(),
            Action\Type::Standard
        );
    }

    protected function unauthorizeWritingAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "unauthorize_writing",
            $this->plugin->txt("unauthorize_writing"),
            $this->plugin->txt("unauthorize_writing"),
            $this->plugin->txt("unauthorize_writing_confirmation"),
            $this->ctrl->getFormAction($this, 'unauthorizeWriting'),
            fn(WriterItem $item) => "Item " . $item->getId(),
            fn(WriterItem $item) => $item->getWriter()->canGetUnauthorized(),
            Action\Type::Standard
        );
    }

    protected function workingTimeDeleteAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "delete_working_time",
            $this->plugin->txt("delete_individual_working_time"),
            $this->lng->txt("delete"),
            "",
            $this->ctrl->getLinkTarget($this, 'deleteWorkingTime'),
            fn(WriterItem $item) => $item->getUserData()->getFullname(true),
            fn(WriterItem $item) => $item->getWriter()->hasChangedTimeLimit(),
            Action\Type::Standard
        );
    }

    protected function workingTimeChangeAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "change_working_time",
            $this->plugin->txt("change_working_time"),
            $this->lng->txt("change"),
            [$this, "workingTimeChangeFields"],
            [$this, "workingTimeChange"],
            fn(WriterItem $item) => $item->getWriter()->canChangeWorkingTime(),
            Action\Type::Standard
        )->withTransformations([$this->workingTimeValidation()]);
    }

    private function workingTimeValidation(): \ILIAS\Refinery\Constraint
    {
        $error_store = new ValidationErrorStore();
        $assessment_api = $this->assessment_api;
        $validation = function (array $data) use ($error_store, $assessment_api) {
            $earliest_start = $data['earliest_start'] ?? null;
            $latest_end = $data['latest_end'] ?? null;
            $writing_limit = (int) ($data['writing_limit_days'] ?? 0) * 24 * 60;
            if ($data['writing_limit_hours_minutes'] instanceof \DateTimeInterface) {
                list($hours, $minutes) = explode(':', $data['writing_limit_hours_minutes']->format('H:i'));
                $writing_limit += (int) $hours * 60 + (int) $minutes;
            }
            $writer_validator = new IndividualValidator($earliest_start, $latest_end, $writing_limit, null);
            $working_time_service = $this->assessment_api->workingTime($this->getSettings(), $writer_validator);
            return $working_time_service->validate($error_store);
        };
        $error = function (\Closure $cls, array $data) use ($error_store): string {
            return (implode(
                '<br>',
                array_map(
                    fn(ValidationError $error) => $this->plugin->txt('failure_' . $error->value),
                    $error_store->getValidationErrors()
                )
            ));
        };
        return $this->refinery->custom()->constraint($validation, $error);
    }

    /**
     * @param WriterItem[] $items
     * @return array
     */
    public function workingTimeChangeFields(array $items)
    {
        $factory = $this->ui_factory->input()->field();
        $writer = count($items) === 1 ? array_pop($items)?->getWriter() : null;
        $time_zone = new \DateTimeZone($this->dic->user()->getTimeZone());
        $split_limit = function (int $limit) {
            $days = floor($limit / (24 * 60));
            $hours = floor(($limit - $days * 24 * 60) / 60);
            $minutes = $limit % 60;
            return [$days, $hours, $minutes];
        };


        if ($writer === null || !$writer->hasChangedTimeLimit()) {
            $settings = $this->getSettings();
            list($days, $hours, $minutes) = $split_limit((int) $settings->getWritingLimitMinutes());

            $earliest_start = $settings->getWritingStart()?->setTimezone($time_zone);
            $latest_end = $settings->getWritingEnd()?->setTimezone($time_zone);
            $writing_limit_days = $days > 0 ? $days : null;
            $writing_limit_hours_minutes = new \DateTimeImmutable(sprintf('%02d:%02d:00', $hours, $minutes), $time_zone);
        } else {
            list($days, $hours, $minutes) = $split_limit((int) $writer->getTimeLimitMinutes());

            $earliest_start = $writer->getEarliestStart()?->setTimezone($time_zone);
            $latest_end = $writer->getLatestEnd()?->setTimezone($time_zone);
            $writing_limit_days = $days > 0 ? $days : null;
            $writing_limit_hours_minutes = new \DateTimeImmutable(sprintf('%02d:%02d:00', $hours, $minutes), $time_zone);
        }


        return [
            'earliest_start' => $factory->dateTime(
                $this->plugin->txt("writing_start"),
                $this->plugin->txt('label_general') . ' '
            )->withUseTime(true)
                                        ->withValue($earliest_start),
            'latest_end' => $factory->dateTime(
                $this->plugin->txt("writing_end"),
                $this->plugin->txt('label_general') . ' '
            )->withUseTime(true)
                                    ->withValue($latest_end),
            'writing_limit_days' => $factory->numeric($this->plugin->txt("writing_limit_days"))
                                            ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0))
                                            ->withValue($writing_limit_days),
            'writing_limit_hours_minutes' => $factory->dateTime(
                $this->plugin->txt("writing_limit_hours_minutes"),
                $this->plugin->txt('label_general') . ' '
            )->withTimeOnly(true)
                                                     ->withValue($writing_limit_hours_minutes)
        ];
    }


    protected function changeLocationAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "change_location",
            $this->plugin->txt("change_location"),
            $this->lng->txt("save"),
            [$this, "changeLocationFields"],
            [$this, "changeLocation"],
            fn(WriterItem $item) => $this->hasLocations(),
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

        return ["location" => $location_input];
    }

    protected function pdfVersionDownloadAction()
    {
        return $this->plugin_ui_factory->table()->action()->modal(
            "pdf_version_download",
            $this->plugin->txt("pdf_version_download"),
            [$this, "pdfVersionDownloadModal"],
            fn(WriterItem $item) => $item->getEssaySummary()?->hasPdfUploads() ?? false,
            Action\Type::Single
        );
    }

    public function pdfVersionDownloadModal(WriterItem $writer): RoundTrip
    {
        return $this->ui_factory->modal()->roundtrip("Test", []);
    }


    protected function editPdfVersionAction()
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "pdf_version_edit",
            $this->plugin->txt("pdf_version_edit"),
            [$this, "editPdfVersion"],
            fn(WriterItem $writer) => true,
            Action\Type::Single
        );
    }

    protected function excludeParticipantAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "exclude_participant",
            $this->plugin->txt("exclude_participant"),
            $this->plugin->txt("exclude_participant"),
            $this->plugin->txt("exclude_participant_confirmation"),
            $this->ctrl->getFormAction($this, 'excludeParticipants'),
            fn(WriterItem $item) => $item->getUserData()->getFullname(true),
            fn(WriterItem $item) => $item->getWriter()->canGetExcluded(),
            Action\Type::Standard
        );
    }

    protected function repealExcludeParticipantAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "repeal_exclude_participant",
            $this->plugin->txt("repeal_exclude_participant"),
            $this->plugin->txt("repeal_exclude_participant"),
            $this->plugin->txt("repeal_exclude_participant_confirmation"),
            $this->ctrl->getFormAction($this, 'repealExcludeParticipants'),
            fn(WriterItem $item) => $item->getUserData()->getFullname(true),
            fn(WriterItem $item) => $item->getWriter()->canGetRepealed(),
            Action\Type::Standard
        );
    }

    protected function removeWriterAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "remove_writer",
            $this->plugin->txt("remove_writer"),
            $this->plugin->txt("remove_writer"),
            $this->plugin->txt("remove_writer_confirmation"),
            $this->ctrl->getFormAction($this, 'removeWriter'),
            fn(WriterItem $item) => $item->getUserData()->getFullname(true),
            fn(WriterItem $item) => true,
            Action\Type::Standard
        );
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null): \Generator
    {
        $writer_view = $this->plugin->dic()->view()->writer();

        $filter = array_merge(['ass_id' => $this->object->getAssId()], $filter_data);
        if (!empty($ids)) {
            $filter['id'] = $ids;
        }

        foreach ($writer_view->some($filter) as $view) {
            yield new WriterItem($view->getWriter()->getId(), $view->getWriter(), $view->getWriterData(), $view->getWriterDisplay(), $view->getEssayTaskSummary(), $view->getAuthorizedByData(), $view->getExcludedByData());
        }
    }

    public function getTableItem(int $id): \ILIAS\Plugin\LongEssayAssessment\UI\Table\Item
    {
        $writer_view = $this->plugin->dic()->view()->writer();
        $view = $writer_view->some(['id' => $id, 'ass_id' => $this->object->getAssId()]);
        $view = empty($view) ? null : $view[0];

        if ($view === null) {
            throw new \Exception("Writer with id $id not found");
        }

        return new WriterItem($view->getWriter()->getId(), $view->getWriter(), $view->getWriterData(), $view->getWriterDisplay(), $view->getEssayTaskSummary(), $view->getAuthorizedByData(), $view->getExcludedByData());

        //        $writer = $this->writer_service->oneByWriterId($id);
        //        $essay_status = $this->assessment_status->oneWriterEssaySummary($writer->getId());
        //        $user = $this->user_service->getUser($writer->getUserId());
        //        $user_display = $this->user_service->getUserDisplay($writer->getUserId(), null);
        //        $authorized_from = $writer->getWritingAuthorizedBy() !== null ? $users[$writer->getWritingAuthorizedBy()] ?? null : null;
        //        $excluded_from = $writer->getWritingExcludedBy() !== null ? $users[$writer->getWritingExcludedBy()] ?? null : null;
        //
        //        return new WriterItem($writer->getId(), $writer, $user, $user_display, $essay_status, $authorized_from, $excluded_from);
    }

    public function getFilterInputs(): array
    {
        $field = $this->ui_factory->input()->field();

        $status = [
            (string) WritingStatus::NOT_STARTED->value => $this->plugin->txt("status_writing_not_started"),
            (string) WritingStatus::STARTED->value => $this->plugin->txt("status_writing_started"),
            (string) WritingStatus::EXCLUDED->value => $this->plugin->txt("status_writing_excluded"),
            (string) WritingStatus::AUTHORIZED->value => $this->plugin->txt("status_writing_authorized"),
        ];

        return $this->filterFilterFields([
            "name" => $field->text($this->plugin->txt("participants")),
            "location" => $field->multiselect($this->plugin->txt("locations"), $this->getLocations()),
            "status" => $field->multiSelect($this->plugin->txt("essay_status"), $status),
            "time_limit_changed" => $this->ui_factory->input()->field()->select(
                $this->plugin->txt("time_limit_changed"),
                [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
            ),
            "min_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("min_word_count"))
                                            ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0)),
            "max_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("max_word_count"))
                                            ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(1)),
            "pdf_version" => $this->ui_factory->input()->field()->select(
                $this->plugin->txt("filter_pdf_version"),
                [self::FILTER_YES => $this->plugin->txt("yes"), self::FILTER_NO => $this->plugin->txt("no")]
            )
        ]);
    }

    public function getFilterInputActivation(): ?array
    {
        return $this->filterFilterFields([
            "name" => true,
            "location" => $this->hasLocations(),
            "status" => true,
            "time_limit_changed" => true,
            "min_words" => true,
            "max_words" => true,
            "pdf_version" => true
        ]);
    }

    protected function hasLocations(): bool
    {
        $location = $this->location ??= $this->assessment_api->location()->allTitles();

        return !empty($location);
    }

    protected function getLocations(): array
    {
        return $this->location ??= $this->assessment_api->location()->allTitles();
    }

    protected function getLocation(?int $id): string
    {
        if ($id === null) {
            return "";
        }

        $location = $this->getLocations();
        return $location[$id] ?? "";
    }

    protected function getSettings(): OrgaSettings
    {
        return $this->settings ??= $this->orga_service->get();
    }

    public function getFilterBaseAction(): string
    {
        return $this->ctrl->getFormAction($this, 'showItems');
    }
}
