<?php

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\WorkingTime;

use Edutiek\AssessmentService\Assessment\WorkingTime\IndividualWorkingTime;
use Edutiek\AssessmentService\Assessment\WorkingTime\ValidationErrorStore;
use DateTimeImmutable;
use Edutiek\AssessmentService\Assessment\WorkingTime\ValidationError;

class IndividualValidator implements IndividualWorkingTime
{
    /** @var ValidationError[] */
    private $validation_errors = [];

    public function __construct(
      private ?DateTimeImmutable $earliest_start,
      private ?DateTimeImmutable $latest_end,
      private ?int $time_limit_minutes,
      private ?DateTimeImmutable $working_start
    ) {
    }

    public function getEarliestStart(): ?DateTimeImmutable
    {
        return $this->earliest_start;
    }

    public function setEarliestStart(?DateTimeImmutable $earliest_start): self
    {
        $this->earliest_start = $earliest_start;
        return $this;
    }

    public function getLatestEnd(): ?DateTimeImmutable
    {
        return $this->latest_end;
    }

    public function setLatestEnd(?DateTimeImmutable $latest_end): self
    {
        $this->latest_end = $latest_end;
        return $this;
    }

    public function getTimeLimitMinutes(): ?int
    {
        return $this->time_limit_minutes;
    }

    public function setTimeLimitMinutes(?int $time_limit_minutes): IndividualWorkingTime
    {
        $this->time_limit_minutes = $time_limit_minutes;
        return $this;
    }

    public function getWorkingStart(): ?DateTimeImmutable
    {
        return $this->working_start;
    }

    public function setWorkingStart(?DateTimeImmutable $working_start): self
    {
        $this->working_start = $working_start;
        return $this;
    }
}