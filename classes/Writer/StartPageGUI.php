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
use Edutiek\AssessmentService\Assessment\Permissions\ReadService;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
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

class StartPageGUI extends BaseGUI
{
    private readonly ReadService $perms;
    private readonly OrgaSettings $orga_settings;
    private readonly ?Writer $writer;
    private readonly WorkingTime $working_time;
    private readonly bool $is_written;
    private readonly bool $is_after_writing;

    /** @var array<int, Component[]> */
    private array $solution_resources;
    /** @var array<int, Component[]> */
    private array $writing_resources;

    public function __construct(BaseObjectData $object, private readonly object $target, private readonly Closure $is_resource_available)
    {
        parent::__construct($object);

        $this->perms = $this->assessment_api->permissions($this->object->getId());
        $this->orga_settings = $this->assessment_api->orgaSettings()->get();
        $this->writer = $this->assessment_api->writer()->oneByUserId($this->dic->user()->getId(), $this->object->getId());

        $this->working_time = new WorkingTime($this->orga_settings, $this->writer);

        $this->is_written = $this->writer?->getWritingAuthorized() !== null;
        $this->is_after_writing = $this->is_written || $this->working_time->isNowAfterAllowedTime();
        [$this->solution_resources, $this->writing_resources] = $this->calcResource();
    }

    public function show(): void
    {
        $this->toolbar();

        $this->renderContent([
            $this->screenMessage(),
            $this->ins(),
            ...$this->allTaskBlocks(),
            $this->result(),
        ]);
    }

    private function screenMessage(): array
    {
        if ($this->writer === null) {
            return [];
        }

        $writing_settings = $this->essay_task_api->writingSettings()->get();

        $essays = $this->essay_task_api->essay()->allByWriterId($this->writer->getId());

        if ($this->writer->getWritingExcluded()) {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('message_writing_excluded'));

        } elseif (!$this->writer->getWritingAuthorized() && array_filter($essays, fn($e) => $e->getWrittenText() || $e->getPdfVersion())) {
            if ($this->perms->canReviewWrittenAssessment()) {
                $this->tpl->setOnScreenMessage('failure', $this->plugin->txt(
                    $writing_settings->getWritingType() === WritingType::PDF_UPLOAD ? 'message_writing_to_authorize_pdf' : 'message_writing_to_authorize'
                ));
            } elseif ($this->perms->canWrite()) {
                if (isset($this->params['returned'])) {
                    $this->tpl->setOnScreenMessage('info', $this->plugin->txt('message_writing_returned_interrupted'));
                } else {
                    $this->tpl->setOnScreenMessage('info', $this->plugin->txt(
                        $writing_settings->getWritingType() === WritingType::PDF_UPLOAD ? 'message_writing_to_authorize_pdf' : 'message_writing_to_continue'
                    ));
                }
            } else {
                $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('message_writing_not_authorized'));
            }

        } elseif ($this->writer->getWritingAuthorized()) {
            if (isset($this->params['returned'])) {
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
                $this->tpl->setOnScreenMessage('info', $this->plugin->txt('message_writing_authorized'));
            }
        }

        return [];
    }

    private function toolbar(): void
    {
        $writing_settings = $this->essay_task_api->writingSettings()->get();

        switch ($writing_settings->getWritingType()) {
        case WritingType::ESSAY_EDITOR:
            if ($this->perms->canWrite()) {
                $button = $this->ui_factory->button()->primary(
                    $this->plugin->txt($this->writer === null ? 'start_writing' : 'continue_writing'),
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
                && $this->writer && $this->writer->getPdfVersion() && !$this->writer->getWritingAuthorized() ) {
                $button = $this->ui_factory->button()->primary(
                    $this->plugin->txt('writer_review_pdf'),
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
                        'ilias\plugin\longessayassessment\writer\writeruploadgui',
                        'uploadPdf'
                    )
                );
                $this->toolbar->addComponent($button);
            }
            break;
        }
    }

    private function ins()
    {
        if ($this->orga_settings->getDescription() && !$this->is_after_writing) {
            $inst_parts[] = $this->ui_factory->legacy($this->displayText($this->orga_settings->getDescription()));
        }

        if ($this->is_before_writing) {
            $properties[$this->plugin->txt('writing_period')] = $this->ui_factory->button()->shy(
                $this->formatDateRange($this->orga_settings->getWritingStart(), $this->writing_end)
                    . ' ' . $this->plugin->txt('refresh_page'),
                $this->ctrl->getLinkTarget($this->target)
            );
        }
        elseif (!$this->is_written) {
            $properties[$this->plugin->txt('writing_period')] = $this->formatDateRange($this->orga_settings->getWritingStart(), $this->writing_end);
        }

        if ($this->is_after_writing) {
            if ($this->orga_settings->getDescription()) {
                $inst_parts[] = $this->ui_factory->button()->shy(
                    $this->plugin->txt('task_description'),
                    $this->ctrl->getLinkTarget($this->target, 'viewDescription')
                );
            }
            if ($this->orga_settings->getClosingMessage() && $this->is_written) {
                $separate($divider);
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

        // xx
        if ($this->writer?->getLocation() !== null) {
            $properties[$this->plugin->txt('location')] = ($location = $this->task_repo->getLocationById($this->writer->getLocation())) !== null ? $location->getTitle() : ' - ';
        }

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
        } elseif (!$this->is_before_writing) {
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

        if($this->orga_settings->getReviewStart() || $this->orga_settings->getReviewEnd()) {
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
        if ($this->perms->canDownloadCorrectionReports() && $this->perms->hasCorrectionReports()) {
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

        $tasks = $this->manager_service->all();
        

        foreach ($tasks as $task) {
            $resources = $this->task_api->resource($task->getId())->allByTypes([ResourceType::URL, ResourceType::FILE]);
            foreach ($resources as $resource) {
                $item = null;
                if (($this->is_resource_available)($resource)) {

                    if ($resource->getType() == ResourceType::FILE && $resource->getFileId() !== null) {
                        $resource_file = $this->dic->resourceStorage()->manage()->find($resource->getFileId());
                        if ($resource_file !== null) {
                            $revision = $this->dic->resourceStorage()->manage()->getCurrentRevision($resource_file);
                            $this->ctrl->setParameter($this->target, 'resource_id', $resource->getId());
                            $this->ctrl->setParameter($this->target, 'task_id', (string) $task->getId());
                            $item = $this->ui_factory->item()->standard(
                                $this->ui_factory->link()->standard(
                                    $resource->getTitle(),
                                    $this->ctrl->getLinkTarget($this->target, 'downloadResourceFile')
                                )
                            )   ->withDescription((string) $resource->getDescription())
                                ->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'))
                                ->withProperties(
                                    array(
                                        $this->lng->txt('filename') => $revision->getInformation()->getTitle(),
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
        $tasks = $this->manager_service->all();
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

    private function formatResultAvailability(): string
    {
        return match ($this->orga_settings->getResultAvailableType()) {
            ResultAvailableType::FINALISED => $this->plugin->txt('result_available_finalised'),
            ResultAvailableType::REVIEW => $this->plugin->txt('result_available_review'),
            ResultAvailableType::DATE => $this->formatDateRange($this->orga_settings->getResultAvailableDate(), null),
        };
    }

    private function formatFinalResult(?Writer $essay): string
    {
        if (null === $essay) {
            return $this->plugin->txt('result_not_available');
        }

        if (null === $essay->getCorrectionFinalized()) {
            return $this->plugin->txt('result_not_finalized');
        }

        $level = $this->localDI->getObjectRepo()->getGradeLevelById((int) $essay->getFinalGradeLevelId());
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
}
