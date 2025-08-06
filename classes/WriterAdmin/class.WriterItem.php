<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\System\Data\UserData;
use Edutiek\AssessmentService\System\Data\UserDisplay;
use Edutiek\AssessmentService\EssayTask\AssessmentStatus\WriterEssaySummary;

class WriterItem extends \ILIAS\Plugin\LongEssayAssessment\UI\Table\Item
{
    public function __construct(
        int $id,
        private Writer $writer,
        private UserData $user_data,
        private UserDisplay $user_display,
        private ?WriterEssaySummary $essay_summaries,
        private ?UserData $authorized_from,
        private ?UserData $excluded_from,
    ) {
        parent::__construct($id);
    }

    public function getWriter(): Writer
    {
        return $this->writer;
    }

    public function getUserData(): UserData
    {
        return $this->user_data;
    }

    public function getUserDisplay(): UserDisplay
    {
        return $this->user_display;
    }

    public function getEssaySummaries(): ?WriterEssaySummary
    {
        return $this->essay_summaries;
    }

    public function getAuthorizedFrom(): ?UserData
    {
        return $this->authorized_from;
    }

    public function getExcludedFrom(): ?UserData
    {
        return $this->excluded_from;
    }

    public function setExcludedFrom(?UserData $excluded_from): void
    {
        $this->excluded_from = $excluded_from;
    }

    /**
     * Returns Fullname of the person who excluded this writer
     *         empty string if there is no exclude
     *         null if there is no person found
     * @return string|null
     */
    public function getExecludedFromFullname() : ?string
    {
        return $this->writer->isExcluded() ? $this->getExcludedFrom()?->getFullname(true) : '';
    }

    /**
     * Returns Fullname of the person who authorized this writer
     *         empty string if there is no authorization
     *         null if there is no person found
     * @return string|null
     */
    public function getAuthorizedFromFullname() : ?string
    {
        return $this->writer->isAuthorized() ? $this->getAuthorizedFrom()?->getFullname(true) : '';
    }
}
