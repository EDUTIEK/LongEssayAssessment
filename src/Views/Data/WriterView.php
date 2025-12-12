<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use Edutiek\AssessmentService\Views\Data\WriterView as WriterViewAbstract;

class WriterView extends WriterViewAbstract
{
    public function __construct(
        private readonly \Edutiek\AssessmentService\Assessment\Data\Writer $writer,
        private readonly \Edutiek\AssessmentService\System\Data\UserData $writer_data,
        private readonly \Edutiek\AssessmentService\System\Data\UserDisplay $writer_display,
        private readonly ?\Edutiek\AssessmentService\Assessment\Data\Location $location,
        private readonly \Edutiek\AssessmentService\Views\Data\EssayTaskSummary $essay_task_summary,
        private readonly ?\Edutiek\AssessmentService\System\Data\UserData $authorized_by_data,
        private readonly ?\Edutiek\AssessmentService\System\Data\UserData $excluded_by_data
    ) {
    }

    public function getWriter(): \Edutiek\AssessmentService\Assessment\Data\Writer
    {
        return $this->writer;
    }

    public function getWriterData(): \Edutiek\AssessmentService\System\Data\UserData
    {
        return $this->writer_data;
    }

    public function getWriterDisplay(): \Edutiek\AssessmentService\System\Data\UserDisplay
    {
        return $this->writer_display;
    }

    public function getLocation(): ?\Edutiek\AssessmentService\Assessment\Data\Location
    {
        return $this->location;
    }

    public function getEssayTaskSummary(): \Edutiek\AssessmentService\Views\Data\EssayTaskSummary
    {
        return $this->essay_task_summary;
    }

    public function getAuthorizedByData(): ?\Edutiek\AssessmentService\System\Data\UserData
    {
        return $this->authorized_by_data;
    }

    public function getExcludedByData(): ?\Edutiek\AssessmentService\System\Data\UserData
    {
        return $this->excluded_by_data;
    }
}
