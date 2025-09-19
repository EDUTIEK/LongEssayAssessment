<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin;

use Edutiek\AssessmentService\System\Data\UserData;
use Edutiek\AssessmentService\System\Data\UserDisplay;
use Edutiek\AssessmentService\EssayTask\AssessmentStatus\WriterEssaySummary;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Task\AssessmentStatus\CorrectionStatus;
use Edutiek\AssessmentService\Task\Data\CorrectorSummary;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use Edutiek\AssessmentService\Assessment\Data\Location;
use ILIAS\UI\Component\Symbol\Symbol;
use Edutiek\AssessmentService\Assessment\Data\Corrector;

class CorrectionItem extends \ILIAS\Plugin\LongEssayAssessment\UI\Table\Item
{
    /**
     * @param int              $id
     * @param Writer           $writer
     * @param Symbol           $user_image
     * @param array            $user_data
     * @param Location|null    $location
     * @param Essay|null       $essay
     * @param CorrectionStatus $correction_status
     * @param CorrectorSummary[]            $summaries_by_position
     * @param Corrector[]            $correcor_by_position
     */
    public function __construct(
        int $id,
        private Writer $writer,
        private array $user_data,
        private ?Location $location,
        private ?Essay $essay,
        private CorrectionStatus $correction_status,
        private array $summaries_by_position,
        private array $correcor_by_position,
        private ?UserDisplay $user_display
    ) {
        parent::__construct($id);
    }

    private function getUserData(int $user_id) : ?UserData
    {
        return $this->user_data[$user_id] ?? null;
    }

    public function getWriterImage() : ?string
    {
        return $this->user_display?->getImageUrl();
    }

    public function getWriterName(): ?string
    {
        return $this->getUserData($this->writer->getUserId())?->getFullname(false);
    }

    public function getWriterLogin(): ?string
    {
        return $this->getUserData($this->writer->getUserId())?->getLogin();
    }

    public function getWriter(): Writer
    {
        return $this->writer;
    }

    public function getLocation():? Location
    {
        return $this->location;
    }

    public function getCorrectionStatus(): CorrectionStatus
    {
        return $this->correction_status;
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
    public function getExecludedByName() : ?string
    {
        $by = $this->writer->getWritingExcludedBy();
        return $by !== null
            ? $this->getUserData($by)?->getFullname(true) : '';
    }

    /**
     * Returns Fullname of the person who authorized this writer
     *         empty string if there is no authorization
     *         null if there is no person found
     * @return string|null
     */
    public function getAuthorizedByName() : ?string
    {
        $by = $this->writer->getWritingAuthorizedBy();
        return $by !== null
            ? $this->getUserData($by)?->getFullname(true) : '';
    }

    /**
     * Returns Fullname of the person who finalized the correction
     *         empty string if there is no authorization
     *         null if there is no person found
     * @return string|null
     */
    public function getFinalizedByName() : ?string
    {
        $by = $this->writer->getCorrectionFinalizedBy();
        return $by !== null
            ? $this->getUserData($by)?->getFullname(true) : '';
    }

    public function isStitchNeeded()
    {
        return $this->correction_status == CorrectionStatus::STITCH_NEEDED;
    }

    public function getCorrectorDataByPosition(int $position) : ?UserData
    {
        $corrector = $this->correcor_by_position[$position] ?? null;
        return $corrector !== null ? $this->getUserData($corrector->getUserId()) : null;
    }

    public function getSummaryByPosition(int $position) : ?CorrectorSummary
    {
        return $this->summaries_by_position[$position] ?? null;
    }

    public function canDownloadCorrectionPdf(): bool
    {
        return !empty($this->summaries_by_position);
    }
}
