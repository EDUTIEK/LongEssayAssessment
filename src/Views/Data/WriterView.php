<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use Edutiek\AssessmentService\Assessment\Data\Location;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\System\Data\UserData;
use Edutiek\AssessmentService\System\Data\UserDisplay;
use Edutiek\AssessmentService\Views\Data\ClientSummary;
use Edutiek\AssessmentService\Views\Data\EssayTaskSummary;
use Edutiek\AssessmentService\Views\Data\WriterView as WriterViewAbstract;

class WriterView extends WriterViewAbstract
{
    public function __construct(
        private readonly Writer $writer,
        private readonly UserData $writer_data,
        private readonly UserDisplay $writer_display,
        private readonly ?Location $location,
        private readonly ClientSummary $client_summary,
        private readonly EssayTaskSummary $essay_task_summary,
        private readonly ?UserData $authorized_by_data,
        private readonly ?UserData $excluded_by_data
    ) {
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

    public function getClientSummary(): ClientSummary
    {
        return $this->client_summary;
    }

    public function getEssayTaskSummary(): EssayTaskSummary
    {
        return $this->essay_task_summary;
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
