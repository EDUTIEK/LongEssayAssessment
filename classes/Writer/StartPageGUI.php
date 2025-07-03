<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use Edutiek\AssessmentService\Assessment\Data\WorkingTime;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\Manager as TaskManager;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\EssayTask\Data\WritingSettings;
use ILIAS\Plugin\LongEssayAssessment\Dependencies\AssessmentDic;
use Edutiek\AssessmentService\EssayTask\Data\WritingType;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use Edutiek\AssessmentService\Task\Data\ResourceAvailability;
use ilDatePresentation;
use DateTimeImmutable;
use ilDateTime;
use Edutiek\AssessmentService\Assessment\Data\ResultAvailableType;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use ILIAS\UI\Component\Component;
use Closure;
use Edutiek\AssessmentService\System\File\Storage as FileStorage;

class StartPageGUI extends BaseGUI
{
    private readonly Permissions $perms;
    private readonly OrgaSettings $orga_settings;
    private WritingSettings $writing_settings;
    private readonly TaskManager $task_manager;
    private FileStorage $file_storage;
    
    private readonly WorkingTime $working_time;
    private readonly bool $is_written;
    private readonly bool $is_after_writing;

    /** @var array<int, Component[]> */
    private array $solution_resources;
    /** @var array<int, Component[]> */
    private array $writing_resources;
    /** @var \Edutiek\AssessmentService\EssayTask\Data\Essay[]  */
    private array $essays;


    public function __construct(
        BaseObjectData $object,
        private readonly Writer $writer,
        private readonly object $target,
        private readonly Closure $is_resource_available)
    {
        parent::__construct($object);

        $this->perms = $this->assessment_api->permissions($this->object->getId());
        $this->orga_settings = $this->assessment_api->orgaSettings()->get();
        $this->task_manager = $this->task_api->manager();
        $this->file_storage = $this->system_api->fileStorage();

        $this->working_time = new WorkingTime($this->orga_settings, $this->writer);

        $this->is_written = $this->writer?->getWritingAuthorized() !== null;
        $this->is_after_writing = $this->is_written || $this->working_time->isNowAfterAllowedTime();
        [$this->solution_resources, $this->writing_resources] = $this->calcResource();

        $this->writing_settings = $this->essay_task_api->writingSettings()->get();
        $this->essays = $this->essay_task_api->essay()->allByWriterId($this->writer->getId());
    }

    public function showPage(): void
    {
        $this->toolbar();

        $this->renderContent([
            $this->screenMessage(),
            $this->assessmentInfo(),
            ...$this->allTaskBlocks(),
            $this->result(),
        ]);
    }

    private function screenMessage(): array
    {
        if ($this->writer->getWritingExcluded()) {
            $this->info($this->plugin->txt('message_writing_excluded'));

        } elseif (!$this->writer->getWritingAuthorized() && array_filter($this->essays, fn($e) => $e->getWrittenText() || $e->getPdfVersion())) {
            
            if ($this->perms->canReviewWrittenAssessment()) {
                $this->failure($this->plugin->txt(
                    $this->writing_settings->getWritingType() === WritingType::PDF_UPLOAD ? 'message_writing_to_authorize_pdf' : 'message_writing_to_authorize'
                ));
            } elseif ($this->perms->canWrite()) {
                if ($this->get->has('returned')) {
                    $this->info($this->plugin->txt('message_writing_returned_interrupted'));
                } else {
                    $this->info($this->plugin->txt(
                        $this->writing_settings->getWritingType() === WritingType::PDF_UPLOAD ? 'message_writing_to_authorize_pdf' : 'message_writing_to_continue'
                    ));
                }
            } else {
                $this->failure($this->plugin->txt('message_writing_not_authorized'));
            }

        } elseif ($this->writer->getWritingAuthorized()) {
            if ($this->get->has('returned')) {
                if($this->orga_settings->getClosingMessage()) {
                    $message = $this->displayText($this->orga_settings->getClosingMessage());
                } else {
                    $message = $this->plugin->txt('message_writing_authorized');
                }
                if ($this->orga_settings->getReviewStart() || $this->orga_settings->getReviewEnd()) {
                    $message .= sprintf(
                        '<p>' . $this->plugin->txt('message_review_period') . '</p>',
                        $this->formatDateRange($this->orga_settings->getReviewStart(), $this->orga_settings->getReviewEnd())
                    );
                }

                $back_url = \ilLink::_getLink($this->dic->repositoryTree()->getParentId($this->object->getRefId()));
                $back_text = $this->plugin->txt('message_writing_authorized_link');
                $message .= '<p><a href="' . $back_url . '">' . $back_text . '</a></p>';

                return [$this->ui_factory->legacy('<div class="alert alert-success" role="alert">' . $message . '</div>')];
            } else {
                $this->info($this->plugin->txt('message_writing_authorized'));
            }
        }

        return [];
    }

    private function toolbar(): void
    {
        if (!$this->working_time->isStarted()) {
            if ($this->perms->canWrite()) {
                $contents[] = $start_modal = $this->ui_factory->modal()->interruptive(
                    $this->plugin->txt('start_working'),
                    $this->plugin->txt($this->working_time->hasTimeLimitFromStart() ? 'start_working_time_limited' : 'start_working_time_unlimited'),
                    $this->ctrl->getLinkTarget($this, 'startWorking')
                )->withActionButtonLabel($this->plugin->txt('start_working'));
                $button = $this->ui_factory->button()->primary($this->plugin->txt('start_working'), '#')->withOnClick($start_modal->getShowSignal());
                $this->toolbar->addComponent($button);
            }
        } else {
            switch ($this->writing_settings->getWritingType()) {
                case WritingType::ESSAY_EDITOR:
                    if ($this->perms->canWrite()) {
                        $button = $this->ui_factory->button()->primary(
                            $this->plugin->txt('continue_writing'),
                            $this->ctrl->getLinkTarget($this->target, 'startWriter')
                        );
                        $this->toolbar->addComponent($button);
                    } elseif ($this->perms->canReviewWrittenAssessment() && $this->writer && !$this->writer->getWritingAuthorized()) {
                        $button = $this->ui_factory->button()->standard(
                            $this->plugin->txt('review_writing'),
                            $this->ctrl->getLinkTarget($this->target, 'startWritingReview')
                        );
                        $this->toolbar->addComponent($button);
                    }
                    break;

                case WritingType::PDF_UPLOAD:
                    if (($this->perms->canWrite() || $this->perms->canReviewWrittenAssessment())
                        && $this->writer && !$this->writer->getWritingAuthorized()
                        && array_filter($this->essays, fn($e) => $e->getPdfVersion())) {
                        $button = $this->ui_factory->button()->primary(
                            $this->plugin->txt('writer_review_pdf'),
                            // todo: use ::class when writeruploadgui is migrated
                            $this->ctrl->getLinkTargetByClass(
                                'ilias\plugin\longessayassessment\writer\writeruploadgui',
                                'reviewPdf'
                            )
                        );
                        $this->toolbar->addComponent($button);

                        if ($this->perms->canWrite()) {
                            $button = $this->ui_factory->button()->standard(
                                $this->plugin->txt('writer_replace_pdf'),
                                $this->ctrl->getLinkTargetByClass(
                                    // todo: use ::class when writeruploadgui is migrated
                                    'ilias\plugin\longessayassessment\writer\writeruploadgui',
                                    'uploadPdf'
                                )
                            );
                            $this->toolbar->addComponent($button);
                        }

                    } elseif ($this->perms->canWrite()) {
                        $button = $this->ui_factory->button()->primary(
                            $this->plugin->txt('writer_upload_pdf'),
                            $this->ctrl->getLinkTargetByClass(
                                // todo: use ::class when writeruploadgui is migrated
                                'ilias\plugin\longessayassessment\writer\writeruploadgui',
                                'uploadPdf'
                            )
                        );
                        $this->toolbar->addComponent($button);
                    }
                    break;
            }
        }
     }

    private function assessmentInfo()
    {
        $inst_parts = [];

        if ($this->orga_settings->getDescription() && !$this->is_after_writing) {
            $inst_parts[] = $this->ui_factory->legacy($this->displayText($this->orga_settings->getDescription()));
        }

        if ($this->working_time->isNowBeforeAllowedTime()) {
            $properties[$this->plugin->txt('writing_period')] = $this->ui_factory->button()->shy(
                $this->formatWorkingTime($this->working_time)
                    . ' ' . $this->plugin->txt('refresh_page'),
                $this->ctrl->getLinkTarget($this->target)
            );
        }
        elseif ($this->working_time->isLimited() && !$this->is_written) {
            $properties[$this->plugin->txt('writing_period')] = ($this->working_time->isStarted()) ?
                $this->formatDateRange($this->working_time->getWorkingStart(), $this->working_time->getWorkingDeadline()) :
                $this->formatWorkingTime($this->working_time);
        }

        if (isset($this->writer) && $this->writer->getLocation() !== null) {
            $location = $this->assessment_api->location()->one($this->writer->getLocation());
            $properties[$this->plugin->txt("location")] = ($location !== null ? $location?->getTitle() : " - ");
        }

        if ($this->is_after_writing) {
            $divider = $this->ui_factory->divider()->vertical();

            if ($this->orga_settings->getDescription()) {
                $inst_parts[] = $this->ui_factory->button()->shy(
                    $this->plugin->txt('task_description'),
                    $this->ctrl->getLinkTarget($this->target, 'viewDescription')
                );
            }
            if ($this->orga_settings->getClosingMessage() && $this->is_written) {
                $inst_parts = empty($inst_parts) ? [] : array_merge($inst_parts, [$divider]);
                $inst_parts[] = $this->ui_factory->button()->shy(
                    $this->plugin->txt('closing_message'),
                    $this->ctrl->getLinkTarget($this->target, 'viewClosingMessage')
                );
            }
        }

        return [$this->ui_factory->panel()->standard('@todo', $inst_parts)];
    }

    private function instructions($task, bool $one): array
    {
        $title = $this->plugin->txt('task_instructions');
        $title .= $one ? '' : ' ' . ($task->getPosition() + 1);

        $has_resources = $this->task_api->resource($task->getId())->oneByType(ResourceType::INSTRUCTIONS);
        $task_settings = $this->task_api->settings($task->getId())->get();

        $inst_parts = [];
        $properties = [];

        $separate = function ($divider) use (&$inst_parts): void {
            if ($inst_parts !== []) {
                $inst_parts[] = $divider;
            }
        };

        if ($properties !== []) {
            $separate($this->ui_factory->divider()->horizontal());
            $inst_parts[] = $this->ui_factory->listing()->descriptive($properties);
        }

        if ($this->is_after_writing) {
            $divider = $this->ui_factory->divider()->vertical();

            if ($task_settings->getInstructions()) {
                $separate($divider);
                $inst_parts[] = $this->ui_factory->button()->shy(
                    $this->plugin->txt('view_instructions'),
                    $this->ctrl->getLinkTarget($this->target, 'viewInstructions')
                );
            }
            if ($has_resources) {
                $separate($divider);
                $this->ctrl->setParameter($this->target, 'task_id', (string) $task->getId());
                $inst_parts[] = $this->ui_factory->button()->shy(
                    $this->plugin->txt('download_instructions'),
                    $this->ctrl->getLinkTarget($this->target, 'downloadInstructions')
                );
            }
        } elseif (!$this->working_time->isNowBeforeAllowedTime()) {
            if ($task_settings->getInstructions()) {
                $inst_parts[] = $this->ui_factory->item()->standard(
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('view_instructions'),
                        $this->ctrl->getLinkTarget($this->target, 'viewInstructions')
                    )
                )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('impr', '', 'medium'));
            }
            if ($has_resources) {
                $this->ctrl->setParameter($this->target, 'task_id', (string) $task->getId());
                $inst_parts[] = $this->ui_factory->item()->standard(
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('download_instructions'),
                        $this->ctrl->getLinkTarget($this->target, 'downloadInstructions')
                    )
                )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
            }
        }

        return [$this->ui_factory->panel()->standard($title, $inst_parts)];
    }

    private function resources($task, bool $one): array
    {
        $writing_resources = $this->writing_resources[$task->getId()] ?? [];
        if ($writing_resources !== []) {
            if ($this->is_after_writing) {
                $popover = $this->ui_factory->popover()->listing($writing_resources)->withTitle($this->plugin->txt('tab_resources'));
                $button = $this->ui_factory->button()->shy($this->plugin->txt('show_resources'), '#')
                    ->withOnClick($popover->getShowSignal());
                return [
                    $popover,
                    $this->ui_factory->panel()->standard($this->plugin->txt('tab_resources'), $button),
                ];
            } else {
                return [$this->ui_factory->panel()->standard($this->plugin->txt('tab_resources'), $writing_resources)];
            }
        }

        return [];
    }

    private function result(): array
    {
        $result_items = [];
        $properties = [];

        if ($this->perms->canViewResult()) {
            $result_items[] = $this->ui_factory->legacy($this->formatFinalResult($this->writer));
            $result_items[] = $this->ui_factory->divider()->horizontal();
        } else {
            $properties[$this->plugin->txt('label_available')] = $this->formatResultAvailability();
        }

        if ($this->orga_settings->getReviewStart() || $this->orga_settings->getReviewEnd()) {
            $properties[$this->plugin->txt('review_period')] =
                $this->formatDateRange($this->orga_settings->getReviewStart(), $this->orga_settings->getReviewEnd());
        }
        $result_items[] = $this->ui_factory->listing()->descriptive($properties);

        if ($this->writer !== null) {
            if ($this->perms->canReviewWrittenAssessment() && $this->writer->getWritingAuthorized()) {
                $result_items[] = $this->ui_factory->item()->standard(
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('download_written_submission'),
                        $this->ctrl->getLinkTarget($this->target, 'downloadWriterPdf')
                    )
                )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
            }
            if ($this->perms->canReviewCorrectedAssessment()) {
                $result_items[] = $this->ui_factory->item()->standard(
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('download_corrected_submission'),
                        $this->ctrl->getLinkTarget($this->target, 'downloadCorrectedPdf')
                    )
                )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
            }
        }
        if ($this->perms->canDownloadCorrectionReports() && $this->assessment_api->corrector()->hasReports()) {
            $result_items[] = $this->ui_factory->item()->standard(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt('download_correction_reports'),
                    $this->ctrl->getLinkTarget($this->target, 'downloadCorrectionReportsPdf')
                )
            )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
        }

        return [$this->ui_factory->panel()->standard($this->plugin->txt('result'), $result_items)];
    }

    private function solutions($task, bool $one): array
    {
        $task_settings = $this->task_api->settings($task->getId())->get();
        $solution_items = [];
        if ($this->perms->canViewSolution()) {
            if ($task_settings->getSolution()) {
                $this->ctrl->setParameter($this->target, 'task_id', (string) $task->getId());
                $solution_items[] = $this->ui_factory->item()->standard(
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('view_solution'),
                        $this->ctrl->getLinkTarget($this->target, 'viewSolution')
                    )
                )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('impr', '', 'medium'));
            }
            if ($this->task_api->resource($task->getId())->oneByType(ResourceType::SOLUTION)) {
                $this->ctrl->setParameter($this->target, 'task_id', (string) $task->getId());
                $solution_items[] = $this->ui_factory->item()->standard(
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('download_solution'),
                        $this->ctrl->getLinkTarget($this->target, 'downloadSolution')
                    )
                )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
            }
            $solution_items = array_merge($solution_items, $this->solution_resources[$task->getId()] ?? []);

            if ($solution_items !== []) {
                return [$this->ui_factory->panel()->standard($this->plugin->txt('task_solution'), $solution_items)];
            }
        }

        return [];
    }

    private function calcResource(): array
    {
        $writing_resources = [];
        $solution_resources = [];

        $tasks = $this->task_manager->all();
        

        foreach ($tasks as $task) {
            $resources = $this->task_api->resource($task->getId())->allByTypes([ResourceType::URL, ResourceType::FILE]);
            foreach ($resources as $resource) {
                $item = null;
                if (($this->is_resource_available)($resource)) {

                    if ($resource->getType() == ResourceType::FILE && $resource->getFileId() !== null) {

                        $file_info = $this->file_storage->getFileInfo($resource->getFileId());
                        if ($file_info !== null) {
                            $this->ctrl->setParameter($this->target, 'resource_id', $resource->getId());
                            $this->ctrl->setParameter($this->target, 'task_id', (string) $task->getId());
                            $item = $this->ui_factory->item()->standard(
                                $this->ui_factory->link()->standard(
                                   $file_info->getFileName(),
                                    $this->ctrl->getLinkTarget($this->target, 'downloadResourceFile')
                                )
                            )   ->withDescription((string) $resource->getDescription())
                                ->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'))
                                ->withProperties(
                                    array(
                                        $this->lng->txt('filename') => $file_info->getFileName(),
                                        $this->plugin->txt('resource_availability') => $this->plugin->txt('resource_availability_' . $resource->getAvailability()->value)
                                    )
                                );
                        }
                    } else {
                        $item = $this->ui_factory->item()->standard($this->ui_factory->link()->standard($resource->getTitle(), $resource->getUrl()))
                            ->withDescription((string) $resource->getDescription())
                            ->withLeadIcon($this->ui_factory->symbol()->icon()->standard('webr', '', 'medium'))
                            ->withProperties(array(
                                $this->plugin->txt('website') => $resource->getUrl(),
                                $this->plugin->txt('resource_availability') => $this->plugin->txt('resource_availability_' . $resource->getAvailability()->value)));
                    }

                    if ($item !== null) {
                        if ($resource->getAvailability() === ResourceAvailability::AFTER) {
                            $solution_resources[$task->getId()][] = $item;
                        } else {
                            $writing_resources[$task->getId()][] = $item;
                        }
                    }
                }
            }
        }

        return [$solution_resources, $writing_resources];
    }

    private function allTaskBlocks(): array
    {
        $blocks = [];
        $tasks = $this->task_manager->all();
        $tasks = [$tasks[0], $tasks[0]];
        $one = count($tasks) === 1;
        foreach ($tasks as $task) {
            $methods = ['instructions', 'resources', 'solutions'];
            $blocks[] = array_combine($methods, array_map(
                fn($m) => $this->$m($task, $one),
                $methods
            ));
        }

        return $blocks;
    }

    /**
     * todo: migrate to \Edutiek\AssessmentService\System\Format\Service::dates
     * - How to get rid of ilDatePresentation? e.g. with a formatDate Closure dependency of the service?
     * - Move language variables to the service? Would be needed fpr PDF generation, too
     */
    private function formatDateRange(?DateTimeImmutable $from, ?DateTimeImmutable $to): string
    {
        $old_relative = ilDatePresentation::useRelativeDates();
        ilDatePresentation::setUseRelativeDates(true);

        $txt = $this->plugin->txt(...);
        $format = fn($d) => ilDatePresentation::formatDate(new ilDateTime($d->getTimestamp(), IL_CAL_UNIX));

        $text = match([!!$from, !!$to]) {
            [true, false] => $txt('period_only_from') . ' ' . $format($from),
            [false, true] => $txt('period_only_until') . ' ' . $format($to),
            [true, true] => join(' ', [$txt('period_from'), $format($from), $txt('period_until'), $format($to)]),
            [false, false] => $txt('not_specified'),
        };

        ilDatePresentation::setUseRelativeDates($old_relative);

        return $text;
    }

    /**
     * todo: move to a new formatting service function of the assessment service
     * - Move language variables to the service?
     */
    private function formatResultAvailability(): string
    {
        return match ($this->orga_settings->getResultAvailableType()) {
            ResultAvailableType::FINALISED => $this->plugin->txt('result_available_finalised'),
            ResultAvailableType::REVIEW => $this->plugin->txt('result_available_review'),
            ResultAvailableType::DATE => $this->formatDateRange($this->orga_settings->getResultAvailableDate(), null),
        };
    }

    /**
     * todo: move to a new formatting service function of the assessment service
     * - Move language variables to the service?
     */
    private function formatFinalResult(?Writer $essay): string
    {
        if (null === $essay) {
            return $this->plugin->txt('result_not_available');
        }

        if (null === $essay->getCorrectionFinalized()) {
            return $this->plugin->txt('result_not_finalized');
        }

        $level = $this->assessment_api->gradLevel()->one((int) $essay->getFinalGradeLevelId());
        if (empty($level)) {
            $text =  $this->plugin->txt('result_not_graded');
        } else {
            $text = $level->getGrade();
        }

        if (!empty($essay->getFinalPoints())) {
            $text .= ' (' . $essay->getFinalPoints() . ' ' . $this->plugin->txt('points') . ')';
        }

        if (!empty($essay->getStitchComment())) {
            $text .= ' ' . $this->plugin->txt('via_stitch_decision');
        }

        return $text;
    }

    /**
     * todo: move to a new formatting service function of the assessment service
     */
    private function formatWorkingTime(WorkingTime $working_time): string
    {
        if ($working_time->isLimited()) {
            $string = $this->formatDateRange($working_time->getEarliestStart(), $working_time->getLatestEnd());
            if ($working_time->getTimeLimitMinutes()) {
                $string .= ', ' . $this->formatDuration($working_time->getTimeLimitMinutes() * 60);
            }
            return $string;
        }

        return $this->plugin->txt('not_specified');
    }

    /**
     * todo: move to a new formatting service function of the system service
     */
    private function formatDuration($seconds): string
    {
        $duration = (int) $seconds;
        $days = floor($duration / (24 * 3600));
        $hours = floor(($duration - $days * 24 * 3600) / 3600);
        $minutes = floor(($duration - $days * 24 * 3600 - $hours * 3600) / 60);
        $seconds = $duration % 60;

        $parts = [];
        if (!empty($days)) {
            $parts[] = ($days == 1) ? $this->plugin->txt('one_day') : sprintf($this->plugin->txt('x_days'), $days);
        }
        if (!empty($hours)) {
            $parts[] = ($hours == 1) ? $this->plugin->txt('one_hour') : sprintf($this->plugin->txt('x_hours'), $hours);
        }
        if (!empty($minutes)) {
            $parts[] = ($minutes == 1) ? $this->plugin->txt('one_minute') : sprintf($this->plugin->txt('x_minutes'), $minutes);
        }
        if (!empty($seconds)) {
            $parts[] = ($seconds == 1) ? $this->plugin->txt('one_second') : sprintf($this->plugin->txt('x_seconds'), $seconds);
        }

        return implode(' ', $parts);
    }
}
