<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

use ILIAS\Plugin\LongEssayTask\BaseService;
use Throwable;

/**
 * Service for handling data related to a task
 * @package ILIAS\Plugin\LongEssayTask\Data
 */
class DataService extends BaseService
{
    /** @var WriterRepository */
    protected $writerRepo;

    /** @var CorrectorRepository */
    protected $correctorRepo;

    /**
     * @inheritDoc
     */
    public function __construct(int $task_id)
    {
        parent::__construct($task_id);

        $this->writerRepo = $this->localDI->getWriterRepo();
        $this->correctorRepo = $this->localDI->getCorrectorRepo();
    }


    /**
     * Convert a string timestamp stored in the database to a unix timestamp
     * Respect the time zone of ILIAS
     * @param ?string $db_timestamp
     * @return ?int
     */
    public function dbTimeToUnix(?string $db_timestamp): ?int
    {
        if (empty($db_timestamp)) {
            return null;
        }

        try {
            $datetime = new \ilDateTime($db_timestamp, IL_CAL_DATETIME);
            return $datetime->get(IL_CAL_UNIX);
        }
        catch (Throwable $throwable) {
            return null;
        }
    }

    /**
     * Convert a unix timestamp to a string timestamp stored in the database
     * Respect the time zone of ILIAS
     * @param ?int $unix_timestamp
     * @return ?string
     */
    public function unixTimeToDb(?int $unix_timestamp): ?string {

        if (empty($unix_timestamp)) {
            return null;
        }

        try {
            $datetime = new \ilDateTime($unix_timestamp, IL_CAL_UNIX);
            return $datetime->get(IL_CAL_DATETIME);
        }
        catch (Throwable $throwable) {
            return null;
        }
    }

    /**
     * Check if an integer is in a range of others
     * Used to check if a timestamp is in a time span
     */
    public function isInRange(int $test, ?int $start, ?int $end)
    {
        if (!empty($start) && $test < $start) {
            return false;
        }
        if (!empty($end) && $test > $end) {
            return false;
        }
        return true;
    }

    /**
     * Format a time period from timestamp strings with fallback for missing values
     */
    public function formatPeriod(?string $start, ?string $end): string
    {
        try {
            if(empty($start) && empty($end)) {
                return $this->plugin->txt('not_specified');
            }
            elseif (empty($end)) {
                return
                    $this->plugin->txt('period_from') . ' ' .
                    \ilDatePresentation::formatDate(new \ilDateTime($start, IL_CAL_DATETIME));
            }
            elseif (empty($start)) {
                return
                    $this->plugin->txt('period_until') . ' ' .
                    \ilDatePresentation::formatDate(new \ilDateTime($end, IL_CAL_DATETIME));
            }
            else {
                return \ilDatePresentation::formatPeriod(new \ilDateTime($start, IL_CAL_DATETIME), new \ilDateTime($end, IL_CAL_DATETIME));
            }
        }
        catch (Throwable $e) {
            return $this->plugin->txt('not_specified');
        }
    }

    /**
     * Format the writing status of an essay
     */
    public function formatWritingStatus(?Essay $essay) : string
    {
        if (empty($essay) || empty($essay->getEditStarted())) {
            return $this->plugin->txt('writing_status_not_written');
        }
        elseif (empty($essay->getWritingAuthorized())) {
            return $this->plugin->txt('writing_status_not_authorized');
        }
        else {
            return $this->plugin->txt('writing_status_authorized');
        }
    }

    /**
     * Format the correction status of an essay
     */
    public function formatCorrectionStatus(?Essay $essay) : string
    {
        if (empty($essay) || empty($essay->getWritingAuthorized())) {
            return $this->plugin->txt('correction_status_not_possible');
        }
        elseif (!empty($essay->getCorrectionFinalized())) {
            return $this->plugin->txt('correction_status_finished');
        }
        elseif ($this->localDI->getCorrectorAdminService($this->task_id)->isStitchDecisionNeeded($essay)) {
            return $this->plugin->txt('correction_status_stitch_needed');
        }
        else {
            return $this->plugin->txt('correction_status_open');
        }
    }

    /**
     * Format the final result stored for an essay
     */
    public function formatFinalResult(?Essay $essay) : string
    {
        if (empty($essay) || empty($essay->getCorrectionFinalized()) || empty($essay->getFinalGradeLevelId())) {
            return $this->plugin->txt('not_specified');
        }

        $level = $this->localDI->getObjectRepo()->getGradeLevelById($essay->getFinalGradeLevelId());
        $text = $level->getGrade();

        if (!empty($essay->getFinalPoints())) {
            $text .= ' (' . $essay->getFinalPoints() . ' ' . $this->plugin->txt('points') . ')';
        }

        if (!empty($essay->getStitchComment())) {
            $text .= ' ' . $this->plugin->txt('via_stitch_decision');
        }

        return $text;
    }

    /**
     * Format the result from a single correction
     */
    public function formatCorrectionResult(?CorrectorSummary $summary, bool $onlyStatus = false) : string
    {
        if (empty($summary) || empty($summary->getLastChange())) {
            return $this->plugin->txt('grading_not_started');
        }

        if (empty($summary->getCorrectionAuthorized())) {
            return  $this->plugin->txt('grading_open');
        }

        $text = $this->plugin->txt('grading_authorized');

        if ($onlyStatus) {
            return $text;
        }

        if ($level = $this->localDI->getObjectRepo()->getGradeLevelById((int) $summary->getGradeLevelId())) {
            $text = $level->getGrade();
        }
        if (!empty($summary->getPoints())) {
            $text .= ' (' . $summary->getPoints() . ' ' . $this->plugin->txt('points') . ')';
        }

        return $text;
    }

    /**
     * Format the position of a corrector
     */
    public function formatCorrectorPosition(CorrectorAssignment $assignment) : string
    {
        switch ($assignment->getPosition()) {
            case 0:
                return $this->plugin->txt('assignment_pos_first');

            case 1:
                return $this->plugin->txt('assignment_pos_second');

            default:
                return $this->plugin->txt('assignment_pos_other');
        }
    }

    /**
     * Format the name of a corrector
     */
    public function formatCorrectorAssignment (CorrectorAssignment $assignment) : string
    {
        $corrector = $this->localDI->getCorrectorRepo()->getCorrectorById($assignment->getCorrectorId());
        $writer = $this->localDI->getWriterRepo()->getWriterById($assignment->getWriterId());


        if (empty($corrector)) {
            return $this->plugin->txt('assignment_pos_empty');
        }

        if (!empty($writer) && !empty($essay = $this->localDI->getEssayRepo()->getEssayByWriterIdAndTaskId($writer->getId(), $writer->getTaskId()))) {
            $summary = $this->localDI->getEssayRepo()->getCorrectorSummaryByEssayIdAndCorrectorId($essay->getId(), $corrector->getId());
        }

        return \ilObjUser::_lookupFullname($corrector->getUserId())
            . ' ('. \ilObjUser::_lookupLogin($corrector->getUserId()) . ')'
            . ' - ' . $this->formatCorrectionResult($summary);

     }

}