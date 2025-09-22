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
use ilDateTime;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use ILIAS\UI\Component\Component;
use Closure;
use Edutiek\AssessmentService\System\File\Storage as FileStorage;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo as Task;
use Edutiek\AssessmentService\System\Format\FullService as SystemFormat;
use Edutiek\AssessmentService\Assessment\Format\FullService as AssessmentFormat;
use Edutiek\AssessmentService\Assessment\WorkingTime\FullService as WorkingTime;
use ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI;
use ILIAS\UIComponent;

class StartPageGUI extends BaseGUI
{
    private readonly Permissions $perms;
    private readonly OrgaSettings $orga_settings;
    private readonly WritingSettings $writing_settings;
    private readonly TaskManager $task_manager;
    private readonly FileStorage $file_storage;
    private readonly WorkingTime $working_time;
    private readonly bool $is_written;
    private readonly SystemFormat $system_format;
    private readonly AssessmentFormat $assessment_format;

    /** @var array<int, Component[]> indexed by task_id */
    private array $solution_resources = [];
    /** @var array<int, Component[]> inexed by task_id */
    private array $writing_resources = [];
    /** @var \Edutiek\AssessmentService\EssayTask\Data\Essay[] */
    private array $essays;


    public function __construct(
        BaseObjectData $object,
        private readonly Writer $writer,
        private readonly object $target,
    ) {
        parent::__construct($object);

        $this->perms = $this->assessment_api->permissions($this->object->getContextId());
        $this->task_manager = $this->task_api->manager();
        $this->file_storage = $this->system_api->fileStorage();

        $this->orga_settings = $this->assessment_api->orgaSettings()->get();
        $this->writing_settings = $this->essay_task_api->writingSettings()->get();
        $this->system_format = $this->system_api->format($this->dic->user()->getId());
        $this->assessment_format = $this->assessment_api->format($this->orga_settings);

        $this->working_time = $this->assessment_api->workingTime($this->orga_settings, $this->writer);
        $this->essays = $this->essay_task_api->essay()->allByWriterId($this->writer->getId());

        $this->is_written = $this->writer->getWritingAuthorized() !== null;
    }

    public function showPage(): void
    {
        [$this->solution_resources, $this->writing_resources] = $this->fetchResources();

        $this->fillToolbar();

        if ($this->is_written || $this->working_time->isNowAfterAllowedTime()) {
            $this->add([
                $this->screenMessage(),
                $this->summarisePanel(),
                $this->resultPanel(),
                $this->solutionPanel()
            ]);

        } else {
            $this->add([
                $this->screenMessage(),
                $this->infoPanel(),
                $this->taskPanels(),
                $this->resultPanel(),
                $this->solutionPanel()
            ]);
        }

        $this->show();
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
                if ($this->orga_settings->getClosingMessage()) {
                    $message = $this->displayText($this->orga_settings->getClosingMessage());
                } else {
                    $message = $this->plugin->txt('message_writing_authorized');
                }
                if ($this->orga_settings->getReviewStart() || $this->orga_settings->getReviewEnd()) {
                    $message .= sprintf(
                        '<p>' . $this->plugin->txt('message_review_period') . '</p>',
                        $this->system_format->dateRange($this->orga_settings->getReviewStart(), $this->orga_settings->getReviewEnd())
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

    private function fillToolbar(): void
    {
        if (!$this->working_time->isStarted()) {
            if ($this->perms->canWrite()) {
                $start_modal = $this->ui_factory->modal()->interruptive(
                    $this->plugin->txt('start_working'),
                    $this->plugin->txt($this->working_time->hasTimeLimitFromStart() ? 'start_working_time_limited' : 'start_working_time_unlimited'),
                    $this->ctrl->getLinkTarget($this->target, 'startWorking')
                )->withActionButtonLabel($this->plugin->txt('start_working'));
                $this->add($start_modal);
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
                    } elseif ($this->perms->canReviewWrittenAssessment() && !$this->writer->getWritingAuthorized()) {
                        $button = $this->ui_factory->button()->standard(
                            $this->plugin->txt('review_writing'),
                            $this->ctrl->getLinkTarget($this->target, 'startWritingReview')
                        );
                        $this->toolbar->addComponent($button);
                    }
                    break;

                case WritingType::PDF_UPLOAD:
                    if ($this->perms->canWrite() || $this->perms->canReviewWrittenAssessment()) {
                        $button = $this->ui_factory->button()->primary(
                            $this->plugin->txt('writer_review_pdf'),
                            $this->ctrl->getLinkTargetByClass(WriterUploadGUI::class)
                        );
                        $this->toolbar->addComponent($button);
                    }
                    break;
            }
        }
    }

    private function infoPanel(): ?Component
    {
        $parts = [];
        $properties = [];

        if ($this->orga_settings->getDescription()) {
            $parts[] = $this->ui_factory->legacy($this->displayText($this->orga_settings->getDescription()));
        }

        if ($this->working_time->isNowBeforeAllowedTime()) {
            $properties[$this->plugin->txt('writing_period')] = $this->ui_factory->button()->shy(
                $this->working_time->format($this->system_format)
                    . ' ' . $this->plugin->txt('refresh_page'),
                $this->ctrl->getLinkTarget($this->target)
            );
        } elseif ($this->working_time->isLimited() && !$this->is_written) {
            $properties[$this->plugin->txt('writing_period')] = ($this->working_time->isStarted()) ?
                $this->system_format->dateRange($this->working_time->getWorkingStart(), $this->working_time->getWorkingDeadline()) :
                $this->working_time->format($this->system_format);
        }

        if (isset($this->writer) && $this->writer->getLocation() !== null) {
            $location = $this->assessment_api->location()->one($this->writer->getLocation());
            $properties[$this->plugin->txt('location')] = $location?->getTitle() ?? ' - ';
        }

        if ($properties) {
            if ($parts) {
                $parts[] = $this->ui_factory->divider()->horizontal();
            }
            $parts[] = $this->ui_factory->listing()->descriptive($properties);
        }

        if ($parts) {
            return $this->ui_factory->panel()->standard($this->plugin->txt('task_settings'), $parts);
        }
        return null;
    }

    private function writingItems(Task $task): array
    {
        $items = [];
        if ($this->working_time->isStarted()) {
            $task_settings = $this->task_api->settings($task->getId())->get();
            if ($task_settings->getInstructions()) {
                $items[] = $this->ui_factory->item()->standard(
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('view_instructions'),
                        $this->ctrl->getLinkTarget($this->target, 'viewInstructions')
                    )
                )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('impr', '', 'medium'));
            }
            $resource = $this->task_api->resource($task->getId())->oneByType(ResourceType::INSTRUCTIONS);
            if ($resource) {
                $this->ctrl->setParameter($this->target, 'task_id', (string) $task->getId());
                $items[] = $this->ui_factory->item()->standard(
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('download_instructions'),
                        $this->ctrl->getLinkTarget($this->target, 'downloadInstructions')
                    )
                )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
            }
        }
        return array_merge($items, $this->writing_resources[$task->getId()] ?? []);
    }

    private function resultPanel(): Component
    {
        $items = [];
        $properties = [];

        if ($this->perms->canViewResult()) {
            $items[] = $this->ui_factory->legacy($this->assessment_format->finalResult($this->writer));
            $items[] = $this->ui_factory->divider()->horizontal();
        } else {
            $properties[$this->plugin->txt('label_available')] = $this->assessment_format->resultAvailability();
        }

        if ($this->orga_settings->getReviewStart() || $this->orga_settings->getReviewEnd()) {
            $properties[$this->plugin->txt('review_period')] =
                $this->system_format->dateRange($this->orga_settings->getReviewStart(), $this->orga_settings->getReviewEnd());
        }

        $items[] = $this->ui_factory->listing()->descriptive($properties);

        if ($this->perms->canReviewWrittenAssessment() && $this->writer->getWritingAuthorized()) {
            $items[] = $this->ui_factory->item()->standard(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt('download_written_submission'),
                    $this->ctrl->getLinkTarget($this->target, 'downloadWriterPdf')
                )
            )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
        }
        if ($this->perms->canReviewCorrectedAssessment()) {
            $items[] = $this->ui_factory->item()->standard(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt('download_corrected_submission'),
                    $this->ctrl->getLinkTarget($this->target, 'downloadCorrectedPdf')
                )
            )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
        }

        if ($this->perms->canDownloadCorrectionReports() && $this->assessment_api->corrector()->hasReports()) {
            $items[] = $this->ui_factory->item()->standard(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt('download_correction_reports'),
                    $this->ctrl->getLinkTarget($this->target, 'downloadCorrectionReportsPdf')
                )
            )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
        }

        return $this->ui_factory->panel()->standard($this->plugin->txt('result'), $items);
    }

    private function solutionPanel(): ?Component
    {
        $parts = [];
        $tasks = $this->task_manager->all();
        $is_one = count($tasks) === 1;
        foreach ($tasks as $task) {
            $items = $this->solutionItems($task);
            if ($items) {
                if (count($tasks) === 1) {
                    $parts = $items;
                    break;
                }
                if ($parts) {
                    $parts[] = $this->ui_factory->divider()->horizontal();
                }
                $title = count($tasks) === 1 ? $this->plugin->txt('task') : $task->getTitle();
                $popover = $this->ui_factory->popover()->listing($items)->withTitle($title);
                $this->add($popover);
                $parts[] = $this->ui_factory->button()->shy($title, '#')->withOnClick($popover->getShowSignal());
            }
        }

        if ($parts) {
            return $this->ui_factory->panel()->standard($this->plugin->txt('task_solution'), $parts);
        }
        return null;

    }

    private function solutionItems(Task $task): array
    {
        if (!$this->perms->canViewSolution()) {
            return [];
        }

        $items = [];
        $task_settings = $this->task_api->settings($task->getId())->get();
        if ($task_settings->getSolution()) {
            $this->ctrl->setParameter($this->target, 'task_id', (string) $task->getId());
            $items[] = $this->ui_factory->item()->standard(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt('view_solution'),
                    $this->ctrl->getLinkTarget($this->target, 'viewSolution')
                )
            )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('impr', '', 'medium'));
        }
        if ($this->task_api->resource($task->getId())->oneByType(ResourceType::SOLUTION)) {
            $this->ctrl->setParameter($this->target, 'task_id', (string) $task->getId());
            $items[] = $this->ui_factory->item()->standard(
                $this->ui_factory->link()->standard(
                    $this->plugin->txt('download_solution'),
                    $this->ctrl->getLinkTarget($this->target, 'downloadSolution')
                )
            )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'));
        }
        return array_merge($items, $this->solution_resources[$task->getId()] ?? []);
    }

    private function fetchResources(): array
    {
        $writing_resources = [];
        $solution_resources = [];

        $tasks = $this->task_manager->all();

        foreach ($tasks as $task) {
            $resource_api = $this->task_api->resource($task->getId());
            $resources = $resource_api->allByTypes([ResourceType::URL, ResourceType::FILE]);
            foreach ($resources as $resource) {
                $item = null;
                if ($resource_api->isAvailable($this->orga_settings, $this->working_time, $resource)) {
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

    private function summarisePanel(): Component
    {
        $parts = [];
        $add = function ($item) use (&$parts): void {
            if ($parts) {
                $parts[] = $this->ui_factory->divider()->vertical();
            }
            $parts[] = $item;
        };

        if ($this->orga_settings->getDescription()) {
            $add($this->shyTo('task_description', 'viewDescription'));
        }
        if ($this->orga_settings->getClosingMessage() && $this->is_written) {
            $add($this->shyTo('closing_message', 'viewClosingMessage'));
        }

        $tasks = $this->task_manager->all();
        $is_one = count($tasks) === 1;
        foreach ($tasks as $task) {
            $items = $this->writingItems($task);
            if ($items) {
                $title = $is_one ? $this->plugin->txt('task') : $task->getTitle();
                $popover = $this->ui_factory->popover()->listing($items)->withTitle($title);
                $this->add($popover);
                $add($this->ui_factory->button()->shy($title, '#')->withOnClick($popover->getShowSignal()));
            }
        }

        return $this->ui_factory->panel()->standard($this->plugin->txt('task_info'), $parts);
    }

    private function taskPanels(): array
    {
        $tasks = $this->task_manager->all();
        $is_one = count($tasks) === 1;

        $info = $this->working_time->isStarted() ? [] :
            [$this->ui_factory->legacy($this->plugin->txt('task_instructions_info') . '<br />')];


        $panels = [];
        foreach ($tasks as $task) {
            $panels[] = $this->ui_factory->panel()->standard(
                $is_one ? $this->plugin->txt('task') : $task->getTitle(),
                [
                    ...$info,
                    ...$this->writingItems($task)
                ]
            );
        }
        return $panels;
    }

    private function shyTo(string $lang_var, string $cmd): Component
    {
        return $this->ui_factory->button()->shy(
            $this->plugin->txt($lang_var),
            $this->ctrl->getLinkTarget($this->target, $cmd)
        );
    }
}
