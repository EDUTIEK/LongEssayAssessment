<?php

namespace ILIAS\Plugin\LongEssayAssessment\GUI\Correction;

use Edutiek\AssessmentService\System\Data\UserData;
use Edutiek\AssessmentService\System\Data\UserDisplay;
use Edutiek\AssessmentService\EssayTask\AssessmentStatus\WriterEssaySummary;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Task\Data\CorrectorSummary;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use Edutiek\AssessmentService\Assessment\Data\Location;
use ILIAS\UI\Component\Symbol\Symbol;
use Edutiek\AssessmentService\Assessment\Data\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Task\Data\Settings;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Properties;

class CorrectionItem extends \ILIAS\Plugin\LongEssayAssessment\UI\Table\Item
{
    public function __construct(
        int $id,
        private Writer $writer,
        private ?UserData $writer_data,
        private ?UserDisplay $user_display,
        private ?Location $location,
        private ?Essay $essay,
        private array $summaries_by_position,
        private array $corrector_data_by_position,
        private readonly ?UserData $finalized_by_data,
        private readonly ?UserData $authorized_by_data,
        private readonly ?UserData $excluded_by_data,
        private readonly Settings $settings,
        private readonly Properties $properties
    ) {
        parent::__construct($id);
    }

    private function getUserData(): ?UserData
    {
        return $this->writer_data;
    }

    public function getWriterImage(): ?string
    {
        return $this->user_display?->getImageUrl();
    }

    public function getWriterName(): ?string
    {
        return $this->writer_data?->getListname(false);
    }

    public function getWriterLogin(): ?string
    {
        return $this->writer_data?->getLogin();
    }

    public function getWriter(): Writer
    {
        return $this->writer;
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
     * Returns Fullname of the person who excluded this writer
     *         empty string if there is no exclude
     *         null if there is no person found
     * @return string|null
     */
    public function getExecludedByName(): ?string
    {

        return $this->excluded_by_data?->getListname(true);
    }

    /**
     * Returns Fullname of the person who authorized this writer
     *         empty string if there is no authorization
     *         null if there is no person found
     * @return string|null
     */
    public function getAuthorizedByName(): ?string
    {
        return $this->authorized_by_data?->getListname(true);
    }

    /**
     * Returns Fullname of the person who finalized the correction
     *         empty string if there is no authorization
     *         null if there is no person found
     * @return string|null
     */
    public function getFinalizedByName(): ?string
    {
        return $this->finalized_by_data?->getListname(true);
    }

    public function getAssignedCorrectorsCount(): int
    {
        return count($this->corrector_data_by_position);
    }

    public function getCorrectorDataByPosition(int $position): ?UserData
    {
        return $this->corrector_data_by_position[$position] ?? null;
    }

    public function getSummaryByPosition(int $position): ?CorrectorSummary
    {
        return $this->summaries_by_position[$position] ?? null;
    }

    public function canDownloadCorrectionPdf(): bool
    {
        return !empty($this->summaries_by_position);
    }

    public function getTaskSettings(): Settings
    {
        return $this->settings;
    }

    public function getAssessmentProperties(): Properties
    {
        return $this->properties;
    }
}
