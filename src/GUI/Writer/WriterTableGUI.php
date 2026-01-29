<?php

namespace ILIAS\Plugin\LongEssayAssessment\GUI\Writer;

use Closure;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\Data\WritingStatus;
use Edutiek\AssessmentService\Assessment\LogEntry\MentionUser as LogEntryMention;
use Edutiek\AssessmentService\Assessment\LogEntry\Type as LogEntryType;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaService;
use Edutiek\AssessmentService\Assessment\Writer\FullService as WriterService;
use Edutiek\AssessmentService\EssayTask\AssessmentStatus\FullService as AssessmentStatus;
use Edutiek\AssessmentService\EssayTask\Essay\ClientService as EssayService;
use Edutiek\AssessmentService\System\Data\Result;
use Edutiek\AssessmentService\System\Format\Service as FormatService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use Edutiek\AssessmentService\System\File\Disposition;
use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use ILIAS\HTTP\StatusCode;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\EssayTask\Data\Essay;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\FilterParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\ConfirmationIds;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\HasColumns;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\HasFilterFields;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper\InitialVisibleColumns;
use ILIAS\UI\Component\Modal\RoundTrip;
use ILIAS\UI\Implementation\Component\Input\Input;
use ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI;

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
    protected FormatService $format;
    private FileStorage $file_storage;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->writer_service = $this->assessment_api->writer();
        $this->user_service = $this->system_api->user();
        $this->orga_service = $this->assessment_api->orgaSettings();
        $this->essay_service = $this->essay_task_api->essay(true);
        $this->assessment_status = $this->essay_task_api->assessmentStatus();
        $this->format = $this->system_api->format($this->user->getId());
        $this->file_storage = $this->system_api->fileStorage();
    }

    public function viewProcessing(WriterItem $writer): RoundTrip
    {
        $link = $this->request->getUri()->__toString();

        $content = [];
        $tasks = $this->task_api->manager()->all();

        foreach ($tasks as $task_info) {
            $essay = $this->essay_service->oneByWriterIdAndTaskId($writer->getId(), $task_info->getId());

            $parts = [];
            if (!empty($essay?->getWrittenText()) && !$essay?->hasPdfFromWrittenText()) {
                $parts[] = $this->ui_factory->legacy($this->displayContent($essay->getWrittenText() ?? ""));
            }

            if (!empty($essay?->getPdfVersion())) {
                $file_info = $this->file_storage->getFileInfo($essay->getPdfVersion());
                $this->ctrl->setParameter($this, 'writer_id', $essay->getWriterId());
                $this->ctrl->setParameter($this, 'task_id', $essay->getTaskId());

                $parts[] = $this->plugin_ui_factory->viewer()->pdf(
                    $this->ctrl->getLinkTarget($this, 'deliverEssayPdf'),
                    $file_info->getFileName()
                );
            }

            if (count($tasks) > 1) {
                $content[] = $this->ui_factory->panel()->standard($task_info->getTitle(), $parts);
            } else {
                $content = $parts;
            }
        }

        $sight_modal = $this->ui_factory->modal()->roundtrip(
            $this->plugin->txt("submission"),
            $content
        );
        //        $reload_button = $this->ui_factory->button()->standard($this->lng->txt("refresh"), "")
        //                                          ->withLoadingAnimationOnClick(true)
        //                                          ->withOnLoadCode(
        //                                              function ($id) use ($link) {
        //                                                  return
        //                                                      "$('#{$id}').click(function() {
        //                                                        n_url = '{$link}';
        //                                                        text = $('#$id').html();
        //                                                        $('#$id').html('...');
        //                                                        il.UI.core.replaceContent($(this).closest('.modal').attr('id'), n_url, 'component');
        //                                                        $('#$id').html(text);
        //                                                        il.UI.button.deactivateLoadingAnimation('$id');
        //                                                        return false;
        //                                                      });";
        //                                              }
        //                                          );
        //
        return $sight_modal;
    }

    public function exportSteps(WriterItem $writer)
    {
        $id = $this->essay_task_api->writingSteps()->createExport($writer->getId());
        $this->system_api->tempDelivery()->sendFile($id, Disposition::ATTACHMENT);
        $this->system_api->tempStorage()->deleteFile($id);
    }

    public function addLogEntry(WriterItem $writer, array $data)
    {
        $this->assessment_api->logEntry()->addEntry(
            LogEntryType::WRITER_NOTE,
            LogEntryMention::fromSystem($this->user->getId()),
            LogEntryMention::fromSystem($writer->getWriter()->getUserId()),
            $data['reason'] ?? ""
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

        $this->openMailForm($logins, 'showItems');
    }

    public function authorizeWriting()
    {
        $writer_ids = $this->confirmationIds();
        $changed = [];
        $unchanged = [];

        foreach ($writer_ids as $writer_id) {
            if (($writer = $this->writer_service->oneByWriterId($writer_id)) !== null) {
                $user = $this->user_service->getUser($writer->getUserId());
                $name = ($user?->getListname(false) ?? $this->plugin->txt('unknown')) . ' (' . $writer->getPseudonym() . ')';
                $result = $this->writer_service->authorizeWriting($writer, true);
                if ($result->isOk()) {
                    $changed[] = $name;
                } else {
                    $unchanged[] = $name . ': ' . implode(', ', $result->failures());
                }
            }
        }

        $this->multiFeedback(
            $changed,
            $unchanged,
            $this->plugin->txt(count($changed) == 1 ? 'writing_authorized' : 'writings_authorized'),
            $this->plugin->txt('authorize_writings_none_possible')
        );

        $this->ctrl->redirect($this);
    }
    public function unauthorizeWriting()
    {
        $writer_ids = $this->confirmationIds();
        $changed = [];
        $unchanged = [];

        foreach ($writer_ids as $writer_id) {
            if (($writer = $this->writer_service->oneByWriterId($writer_id)) !== null) {
                $user = $this->user_service->getUser($writer->getUserId());
                $name = ($user?->getListname(false) ?? $this->plugin->txt('unknown')) . ' (' . $writer->getPseudonym() . ')';
                $result = $this->writer_service->removeWritingAuthorization($writer);
                if ($result->isOk()) {
                    $changed[] = $name;
                } else {
                    $unchanged[] = $name . ': ' . implode(', ', $result->failures());
                }
            }
        }

        $this->multiFeedback(
            $changed,
            $unchanged,
            $this->plugin->txt(count($changed) == 1 ? 'writing_unauthorized' : 'writings_unauthorized'),
            $this->plugin->txt('unauthorize_writings_none_possible')
        );

        $this->ctrl->redirect($this);

    }

    /**
     * @param WriterItem[] $items
     * @return array
     */
    public function workingTimeFields(array $items)
    {
        $settings = $this->orga_service->get();

        $writer = count($items) === 1 ? array_pop($items)?->getWriter() : null;
        $working_time = $this->assessment_api->workingTime($writer);
        [$days, $hours, $minutes] = $working_time->getTimeLimitParts();

        $fields = [];
        $factory = $this->ui_factory->input()->field();

        $fields['earliest_start'] = $factory->dateTime(
            $this->plugin->txt("writing_start"),
            $settings->getWritingStart()
                ? $this->plugin->txt('label_general') . ' ' . $this->format->date($settings->getWritingStart())
                : ''
        )->withUseTime(true)->withValue($working_time->getEarliestStart()?->format('Y-m-d H:i:s'));

        $fields['latest_end'] = $factory->dateTime(
            $this->plugin->txt("writing_end"),
            $settings->getWritingEnd()
                ? $this->plugin->txt('label_general') . ' ' . $this->format->date($settings->getWritingEnd())
                : ''
        )->withUseTime(true)->withValue($working_time->getLatestEnd()?->format('Y-m-d H:i:s'));


        $fields['writing_limit'] = $factory->optionalGroup(
            [
                'days' => $factory->numeric(
                    $this->plugin->txt("writing_limit_days"),
                )->withValue($days > 0 ? $days : null),
                'hours_minutes' => $factory->dateTime(
                    $this->plugin->txt("writing_limit_hours_minutes"),
                )->withTimeOnly(true)
                    ->withValue(new \DateTimeImmutable(
                        sprintf('%02d:%02d:00', $hours, $minutes),
                        new \DateTimeZone($this->user->getTimeZone())
                    ))
            ],
            $this->plugin->txt('writing_limit'),
            $settings->getWritingLimitMinutes()
                ? $this->plugin->txt('label_general') . ' ' . $this->format->duration($settings->getWritingLimitMinutes() * 60)
                : ''
        );
        if (!$working_time->hasTimeLimitFromStart()) {
            $fields['writing_limit'] = $fields['writing_limit']->withValue(null);
        }

        return $fields;
    }


    /**
     * Apply the data of the working time form to a writer
     */
    private function workingTimeDataToWriter(array $data, Writer $writer): void
    {
        $writer->setEarliestStart($data['earliest_start'] ?? null);
        $writer->setLatestEnd($data['latest_end'] ?? null);
        $limit = null;
        if (isset($data['writing_limit'])) {
            if (isset($data['writing_limit']['days'])) {
                $limit = (int) $data['writing_limit']['days'] * 24 * 60;
            }
            if (isset($data['writing_limit']['hours_minutes'])) {
                [$hours, $minutes] = explode(':', $data['writing_limit']['hours_minutes']->format('H:i'));
                $limit = (int) $limit + (int) $hours * 60 + (int) $minutes;
            }
        }
        if (empty($limit) && $this->getSettings()->getWritingLimitMinutes() === null) {
            // use null to keep the time limit unset
            $writer->setTimeLimitMinutes(null);
        } else {
            // use 0 to reset a time limit from the task
            $writer->setTimeLimitMinutes((int) $limit);
        }
    }

    /**
     * @param WriterItem[] $items
     * @param array        $data
     */
    public function workingTimeChange(array $items, array $data)
    {
        $dummy = $this->writer_service->new();
        $this->workingTimeDataToWriter($data, $dummy);

        $changed = [];
        $unchanged = [];
        foreach ($items as $item) {
            $result = $this->writer_service->changeWorkingTime(
                $item->getWriter(),
                $dummy->getEarliestStart(),
                $dummy->getLatestEnd(),
                $dummy->getTimeLimitMinutes()
            );
            if ($result->isOk()) {
                $changed[] = $item->getUserData()->getListname(true);
            } else {
                $unchanged[] = $item->getUserData()->getListname(true)
                    . ': ' . implode(', ', $result->failures());
            }
        }

        $this->multiFeedback(
            $changed,
            $unchanged,
            $this->plugin->txt('change_working_time_done'),
            $this->plugin->txt('change_working_time_failed')
        );

        $this->ctrl->redirect($this, 'showItems');
    }

    protected function workingTimeDelete()
    {
        foreach ($this->confirmationIds() as $writer_id) {
            $writer = $this->writer_service->oneByWriterId($writer_id);
            $this->writer_service->removeWorkingTime($writer);
        }
        $this->tpl->setOnScreenMessage("success", $this->plugin->txt(
            count($this->confirmationIds()) == 1 ? 'one_working_time_deleted' : 'x_working_times_deleted'
        ), true);
        $this->ctrl->redirect($this, 'showItems');
    }

    /**
     * @param WriterItem[] $items
     * @param array $data
     */
    public function changeLocation(array $items, array $data)
    {
        $location = $data['location'] !== "" ? (int) $data['location'] : null;

        foreach ($items as $item) {
            $writer = $item->getWriter();
            $writer->setLocation($location);
            $this->writer_service->save($writer);
        }

        $this->tpl->setOnScreenMessage("success", $this->plugin->txt("location_assigned"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    /**
     * @param WriterItem[] $items
     */
    public function excludeParticipants(array $items, array $data)
    {
        foreach ($items as $item) {
            $this->writer_service->exclude($item->getWriter(), $data['reason'] ?? null);
        }

        $this->success(
            $this->plugin->txt("exclude_writer_success")
            . $this->renderer->render($this->ui_factory->listing()->unordered(
                array_map(fn(WriterItem $item) => $item->getUserData()->getListname(true), $items)
            )),
            true
        );

        $this->ctrl->redirect($this);
    }

    /**
     * @param WriterItem[] $items
     */
    public function repealExcludeParticipants(array $items, array $data)
    {
        foreach ($items as $item) {
            $this->writer_service->repealExclusion($item->getWriter(), $data['reason'] ?? null);
        }

        $this->success(
            $this->plugin->txt("exclude_writer_repeal_success")
            . $this->renderer->render($this->ui_factory->listing()->unordered(
                array_map(fn(WriterItem $item) => $item->getUserData()->getListname(true), $items)
            )),
            true
        );
    }

    /**
     * @param WriterItem[] $items
     */
    private function removeWriter(array $items, array $data)
    {
        foreach ($items as $item) {
            $this->writer_service->remove($item->getWriter(), $data['reason'] ?? null);
        }

        $this->success(
            $this->plugin->txt("remove_writer_success")
            . $this->renderer->render($this->ui_factory->listing()->unordered(
                array_map(fn(WriterItem $item) => $item->getUserData()->getListname(true), $items)
            )),
            true
        );

        $this->ctrl->redirect($this);
    }

    public function editPdfVersion(WriterItem $writer)
    {
        $this->ctrl->setParameterByClass(WriterUploadGUI::class, 'writer_id', $writer->getId());
        $this->ctrl->redirectByClass(WriterUploadGUI::class);
    }

    /**
     * @param WriterItem[] $items
     * @return void
     */
    public function downloadWriting(array $items)
    {
        if ($this->getSettings()->getMultiTasks() || count($items) > 1) {
            $file_id = $this->assessment_api->pdfCreation()->createWritingZip(
                array_map(fn(WriterItem $item) => $item->getId(), $items)
            );
            $filename = 'writings.zip';
            $mimetype = 'application/zip';
        } else {
            $task = $this->task_api->manager()->first();
            $writer_id = reset($items)->getId();
            $file_id = $this->assessment_api->pdfCreation()->createWritingPdf($task->getId(), $writer_id);
            $filename = 'task' . $task->getId() . '_writer' . $writer_id . '-writing.pdf';
            $mimetype = 'application/pdf';
        }

        $this->system_api->fileDelivery()->sendFile(
            $file_id,
            Disposition::ATTACHMENT,
            (new FileInfo())->setFileName($filename)->setMimeType($mimetype)
        );
        $this->system_api->fileStorage()->deleteFile($file_id);
    }

    public function changeTextToPdf()
    {
        $writer_ids = $this->confirmationIds();
        $changed = [];
        $unchanged = [];

        foreach ($writer_ids as $writer_id) {
            if (($writer = $this->writer_service->oneByWriterId($writer_id)) !== null) {
                $user = $this->user_service->getUser($writer->getUserId());
                $name = ($user?->getListname(false) ?? $this->plugin->txt('unknown')) . ' (' . $writer->getPseudonym() . ')';

                $is_changed = false;
                foreach ($this->task_api->manager()->all() as $task) {
                    $essay = $this->essay_service->oneByWriterIdAndTaskId($writer->getId(), $task->getId());
                    if (!empty($essay?->getWrittenText()) && empty($essay->getPdfVersion())) {
                        $this->essay_service->textToPdf($essay);
                        $is_changed = true;
                    }
                }

                if ($is_changed) {
                    $this->writer_service->authorizeWriting($writer, true);
                    $changed[] = $name;
                } else {
                    $unchanged[] = $name;
                }
            }
        }

        $this->multiFeedback(
            $changed,
            $unchanged,
            $this->plugin->txt('change_text_to_pdf_success'),
            $this->plugin->txt('change_text_to_pdf_none_possible')
        );

        $this->ctrl->redirect($this);

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
            $avatar = $this->ui_factory->symbol()->avatar()->letter($user_data?->getLogin() ?? $unknown);
        }

        $status = $this->assessment_api->format($this->getSettings())->writingStatus($writer);

        $working_time = $this->assessment_api->workingTime($writer);

        $working_start = $writer->getWorkingStart()?->setTimezone($timezone);
        $working_end = $writer->getWritingAuthorized()?->setTimezone($timezone);
        $working_duration = null;
        if ($working_start !== null && $working_end !== null) {
            $working_duration = $working_start->diff($working_end)->i;
        }

        $exam_start = $writer->getEarliestStart() ?? $this->getSettings()->getWritingStart()?->setTimezone($timezone);
        $exam_end = $writer->getLatestEnd() ?? $this->getSettings()->getWritingEnd()?->setTimezone($timezone);
        $assessment_duration = $writer->getTimeLimitMinutes() ?? $this->getSettings()->getWritingLimitMinutes();
        if (empty($assessment_duration) && $exam_start !== null && $exam_end !== null) {
            $assessment_duration = $exam_start->diff($exam_end)->i;
        }


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
            "working_duration" => $working_duration,
            "assessment_start" => $exam_start,
            "assessment_end" => $exam_end,
            "assessment_duration" => $assessment_duration ?? "",
            "time_limit_changed" => $writer->hasChangedTimeLimit(),
            "authorized" => $writer->getWritingAuthorized()?->setTimezone($timezone),
            "authorized_from" => $writer->getWritingAuthorized() !== null && $writer->getUserId() === $writer->getWritingAuthorizedBy()
                ? $this->plugin->txt("participant")
                : ($item->getAuthorizedFromFullname() ?? $unknown),
            "excluded" => $writer->getWritingExcluded()?->setTimezone($timezone),
            "excluded_from" => $item->getExecludedFromFullname() ?? $unknown,
            "pdf_version" => $essay_summary?->hasPdfUploads() ?? false
        ];
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

        $has_days = true;
        if (!empty($settings->getWritingLimitMinutes())) {
            $has_days = $settings->getWritingLimitMinutes() > 1440;
        } elseif ($settings->getWritingStart() !== null && $settings->getWritingEnd() !== null) {
            $has_days = date_diff($settings->getWritingStart(), $settings->getWritingEnd())->d > 0;
        }
        $a_interval_format = ($has_days ? "%D " . $this->plugin->txt('days') . " " : "") . "%H:%I";
        $w_interval_format = ($has_days ? "%D " . $this->plugin->txt('days') . " " : "") . "%H:%I:%S";

        return $this->setInitialVisible($this->filterColumns(array_filter([
            "image" => $cfp->image($this->lng->txt("image"))->withIsOptional(true, false)->withIsSortable(false),
            "name" => $cf->text($this->lng->txt("name"))->withIsOptional(false, true)->withIsSortable(true),
            "login" => $cf->text($this->lng->txt("login"))->withIsOptional(true, false)->withIsSortable(true),
            "pseudonym" => $cf->text($this->plugin->txt("pseudonym"))->withIsOptional(true, false)->withIsSortable(true),
            "location" => $location_avaiable ? $cf->text($this->plugin->txt("location"))->withIsOptional(true, false)->withIsSortable(true) : null,
            "status" => $cf->status($this->plugin->txt("essay_status"))->withIsOptional(true, true)->withIsSortable(true),
            "writing_last_save" => $cfp->nullableDate($this->plugin->txt("writing_last_save"), $date_with_seconds)->withIsOptional(true, false)->withIsSortable(true),
            "word_count" => $cf->number($this->plugin->txt('word_count'))->withIsOptional(true, false)->withIsSortable(true),
            "pdf_version" => $cf->boolean($this->plugin->txt("pdf_version"), $this->lng->txt("yes"), $this->lng->txt("no"))->withIsOptional(true, false)->withIsSortable(true),
            "working_start" => $cfp->nullableDate($this->plugin->txt("working_start"), $date_with_seconds)->withIsOptional(true, false)->withIsSortable(true),
            "working_end" => $cfp->nullableDate($this->plugin->txt("working_end"), $date_with_seconds)->withIsOptional(true, false)->withIsSortable(true),
            "working_duration" => $cfp->interval($this->plugin->txt("working_duration"), $w_interval_format)->withIsOptional(true, false)->withIsSortable(true),
            "assessment_start" => $cfp->nullableDate($this->plugin->txt("assessment_start"), $date_without_seconds)->withIsOptional(true, false)->withIsSortable(true),
            "assessment_end" => $cfp->nullableDate($this->plugin->txt("assessment_end"), $date_without_seconds)->withIsOptional(true, false)->withIsSortable(true),
            "assessment_duration" => $cfp->interval($this->plugin->txt("assessment_duration"), $a_interval_format)->withIsOptional(true, false)->withIsSortable(true),
            "time_limit_changed" => $cf->boolean($this->plugin->txt("time_limit_changed"), $this->lng->txt("yes"), $this->lng->txt("no"))->withIsOptional(true, false)->withIsSortable(true),
            "authorized" => $cfp->nullableDate($this->plugin->txt("writing_autorized_at"), $date_without_seconds)->withIsOptional(true, true)->withIsSortable(true),
            "authorized_from" => $cf->text($this->plugin->txt("writing_autorized_from"))->withIsOptional(true, false)->withIsSortable(true),
            "excluded" => $cfp->nullableDate($this->plugin->txt("writing_excluded_at"), $date_without_seconds)->withIsOptional(true, true)->withIsSortable(true),
            "excluded_from" => $cf->text($this->plugin->txt("writing_excluded_from"))->withIsOptional(true, false)->withIsSortable(true),
        ])));
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        // todo: respect the filter when a count can be done fully in SQL, see CorrectionsViewRepo
        return count($this->writer_service->all());
    }

    protected function viewProccessingAction()
    {
        return $this->plugin_ui_factory->table()->action()->modal(
            "view_processing",
            $this->plugin->txt("view_processing"),
            $this->viewProcessing(...),
            fn(WriterItem $item) => true,
            Action\Type::Single
        )->withUpdateButton(false);
    }

    protected function exportStepsAction()
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "export_steps",
            $this->plugin->txt("export_steps"),
            $this->exportSteps(...),
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
            $this->getTableActionConfirmFields(...),
            $this->addLogEntry(...),
            fn(WriterItem $writer) => true,
            Action\Type::Standard
        );
    }

    protected function mailToWriterAction()
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "mail_to_writer",
            $this->plugin->txt("mail_to_writer"),
            $this->mailToWriter(...),
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
            fn(WriterItem $item) => $item->getUserData()->getListname(true),
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
            fn(WriterItem $item) => $item->getUserData()->getListname(true),
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
            $this->plugin->txt("delete_individual_working_time_confirmation"),
            $this->ctrl->getLinkTarget($this, 'workingTimeDelete'),
            fn(WriterItem $item) => $item->getUserData()->getListname(true),
            fn(WriterItem $item) => $item->getWriter()->hasChangedTimeLimit() && $item->getWriter()->canChangeWorkingTime(),
            Action\Type::Standard
        );
    }

    protected function workingTimeChangeAction()
    {
        // dummy writer for working time validation and error store
        $result = new Result();

        return $this->plugin_ui_factory->table()->action()->form(
            "change_working_time",
            $this->plugin->txt("change_working_time"),
            $this->lng->txt("change"),
            $this->workingTimeFields(...),
            $this->workingTimeChange(...),
            fn(WriterItem $item) => $item->getWriter()->canChangeWorkingTime(),
            Action\Type::Standard
        )->withTransformations([
            $this->refinery->custom()->constraint(
                function (array $data) use ($result) {
                    $writer = $this->writer_service->new();
                    $this->workingTimeDataToWriter($data, $writer);
                    return $this->assessment_api->workingTime($writer)->validate($result)->isOk();
                },
                function (Closure $cls, array $data) use ($result): string {
                    return $result->isOk() ? '' : implode(', ', $result->failures());
                }
            )]);
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
            $this->changeLocationFields(...),
            $this->changeLocation(...),
            fn(WriterItem $item) => $this->hasLocations(),
            Action\Type::Standard
        );
    }

    /**
     * @param WriterItem[] $items
     * @return array
     */
    public function changeLocationFields(array $items)
    {
        $options = [];
        foreach ($this->getLocations() as $id => $location) {
            $options[$id] = $location;
        }
        $location_input = $this->ui_factory->input()->field()->select($this->plugin->txt("location"), $options);

        if (count($items) === 1 && $items[0]?->getWriter()?->getLocation() !== null) {
            $location_input = $location_input->withValue($items[0]->getWriter()->getLocation());
        }

        return [
            "info" => $this->getTableActionInfoField($items),
            "location" => $location_input
        ];
    }

    protected function downloadWritingAction()
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "download_writing",
            $this->plugin->txt("download_writing"),
            $this->downloadWriting(...),
            fn(WriterItem $item) => true,
            Action\Type::Standard
        );
    }

    protected function changeTextToPdfAction()
    {
        return $this->plugin_ui_factory->table()->action()->confirmation(
            "change_text_to_pdf",
            $this->plugin->txt("change_text_to_pdf"),
            $this->plugin->txt("change_text_to_pdf"),
            $this->plugin->txt("change_text_to_pdf_confirmation"),
            $this->ctrl->getFormAction($this, 'changeTextToPdf'),
            fn(WriterItem $item) => $item->getUserData()->getListname(true),
            fn(WriterItem $item) => true,
            Action\Type::Standard
        );
    }

    protected function editPdfVersionAction()
    {
        return $this->plugin_ui_factory->table()->action()->direct(
            "pdf_version_edit",
            $this->plugin->txt("pdf_version_edit"),
            $this->editPdfVersion(...),
            fn(WriterItem $writer) => true,
            Action\Type::Single
        );
    }

    protected function excludeParticipantAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "exclude_participant",
            $this->plugin->txt("exclude_participant"),
            $this->plugin->txt("exclude_participant"),
            $this->getTableActionConfirmFields(...),
            $this->excludeParticipants(...),
            fn(WriterItem $item) => $item->getWriter()->canGetExcluded(),
            Action\Type::Standard
        )->withContent([$this->ui_factory->messageBox()->confirmation($this->plugin->txt('exclude_participant_confirmation'))]);
    }

    protected function repealExcludeParticipantAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "repeal_exclude_participant",
            $this->plugin->txt("repeal_exclude_participant"),
            $this->plugin->txt("repeal_exclude_participant"),
            $this->getTableActionConfirmFields(...),
            $this->repealExcludeParticipants(...),
            fn(WriterItem $item) => $item->getWriter()->isExcluded(),
            Action\Type::Standard
        )->withContent([$this->ui_factory->messageBox()->confirmation($this->plugin->txt('repeal_exclude_participant_confirmation'))]);
    }

    protected function removeWriterAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "remove_writer",
            $this->plugin->txt("remove_writer"),
            $this->plugin->txt("remove_writer"),
            $this->getTableActionConfirmFields(...),
            $this->removeWriter(...),
            fn(WriterItem $item) => true,
            Action\Type::Standard
        )->withContent([$this->ui_factory->messageBox()->confirmation($this->plugin->txt('remove_writer_confirmation'))]);
    }

    /**
     * @param WriterItem[] $items
     */
    private function getTableActionConfirmFields(array $items): array
    {
        $fields = [
            'info' => $this->getTableActionInfoField($items),
            'reason' => $this->ui_factory->input()->field()->textarea(
                $this->plugin->txt('log_entry_text'),
                $this->plugin->txt('log_entry_text_info')
            )->withAdditionalTransformation($this->refinery->string()->hasMinLength(5))
        ];

        return $fields;
    }

    /**
     * @param WriterItem[] $items
     */
    private function getTableActionInfoField(array $items): Input
    {
        return $this->plugin_ui_factory->field()->info($this->plugin->txt('participants'))
            ->withInfo($this->ui_factory->listing()->unordered(
                array_map(fn(WriterItem $item) => $item->getUserData()->getListname(true), $items)
            ));
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null): \Generator
    {
        $writer_view = $this->plugin->dic()->view()->writer();

        $filter = ['ass_id' => $this->object->getAssId()];

        if (!empty($filter_data)) {
            $filter = array_merge($filter, $filter_data);
        }
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
            "min_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("min_word_count")),
            "max_words" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("max_word_count")),
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

    public function deliverEssayPdf(): void
    {
        $essay = $this->essay_service->oneByWriterIdAndTaskId(
            $this->get->integer('writer_id'),
            $this->get->integer('task_id')
        );

        if ($essay?->getPdfVersion()) {
            $this->system_api->fileDelivery()->sendFile($essay->getPdfVersion(), Disposition::INLINE);
        } else {
            $response = $this->http->response()->withStatus(StatusCode::HTTP_NOT_FOUND);
            $this->http->saveResponse($response);
            $this->http->sendResponse();
            $this->http->close();
        }
    }

}
