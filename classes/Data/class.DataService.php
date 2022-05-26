<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

use ILIAS\Plugin\LongEssayTask\BaseService;
use Throwable;

/**
 * Service for handling data related to the object
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
    public function __construct($object)
    {
        parent::__construct($object);

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
        if (isset($start) && $test < $start) {
            return false;
        }
        if (isset($end) && $test > $end) {
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
                    $this->plugin->txt('period_from') . ' '
                    .\ilDatePresentation::formatDate(new \ilDateTime($start, IL_CAL_DATETIME));
            }
            elseif (empty($start)) {
                return
                    \ilDatePresentation::formatDate(new \ilDateTime($end, IL_CAL_DATETIME))
                    . ' ' . $this->plugin->txt('period_until');
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
     * Format the final result stored for an essay
     */
    public function formatFinalResult(?Essay $essay) : string
    {
        if (empty($essay) || empty($essay->getFinalGradeLevelId())) {
            return $this->plugin->txt('not_specified');
        }

        $level = $this->localDI->getObjectRepo()->getGradeLevelById($essay->getFinalGradeLevelId());
        $text = $level->getGrade();

        if (!empty($essay->getFinalPoints())) {
            $text .= ' (' . $essay->getFinalPoints() . ' ' . $this->plugin->txt('points') . ')';
        }

        return $text;
    }
}