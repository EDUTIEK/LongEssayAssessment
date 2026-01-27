<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use Edutiek\AssessmentService\System\Data\UserData;
use Edutiek\AssessmentService\System\Data\UserDisplay;
use Edutiek\AssessmentService\Assessment\Data\Properties;
use Edutiek\AssessmentService\Views\Data\EssayTaskSummary;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\Data\Location;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use Edutiek\AssessmentService\Task\Data\Settings as Task;

class CorrectionsView extends \Edutiek\AssessmentService\Views\Data\CorrectionsView
{
    public function __construct(
        private readonly Task $task,
        private readonly Properties $assessment_properties,
        private readonly Writer $writer,
        private readonly UserData $writer_data,
        private readonly UserDisplay $writer_display,
        private readonly ?Location $location,
        private readonly ?Essay $essay,
        private readonly array $corrections,
        private readonly ?UserData $finalized_by_data,
        private readonly ?UserData $authorized_by_data,
        private readonly ?UserData $excluded_by_data
    ) {
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getAssessmentProperties(): Properties
    {
        return $this->assessment_properties;
    }

    public function getWriter(): Writer
    {
        return $this->writer;
    }

    public function getWriterData(): UserData
    {
        return $this->writer_data;
    }

    public function getWriterDisplay(): UserDisplay
    {
        return $this->writer_display;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function getEssay(): ?Essay
    {
        return $this->essay;
    }

    /**
     * @inheritDoc
     */
    public function getCorrections(): array
    {
        return $this->corrections;
    }

    public function getFinalizedByData(): ?UserData
    {
        return $this->finalized_by_data;
    }

    public function getAuthorizedByData(): ?UserData
    {
        return $this->authorized_by_data;
    }

    public function getExcludedByData(): ?UserData
    {
        return $this->excluded_by_data;
    }
}
