<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common;

use ILIAS\UI\Implementation\Component\Input\Field\DateTime;
use ilLongEssayAssessmentPlugin;
use ilObjUser;
use DateTimeZone;
use ilDatePresentation;
use Throwable;
use DateTimeInterface;
use ilDateTime;
use ILIAS\Plugin\LongEssayAssessment\Data\WorkingTime;

class Formatter
{
    private ilLongEssayAssessmentPlugin $plugin;

    public function __construct(ilLongEssayAssessmentPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Format a datetime object with fallback
     */
    public function formatDateTime(?DateTimeInterface $date, $relative = true): string
    {
        $old_relative = ilDatePresentation::useRelativeDates();
        ilDatePresentation::setUseRelativeDates($relative);

        try {
            if(empty($date)) {
                $text = $this->plugin->txt('not_specified');
            } else {
                $text = \ilDatePresentation::formatDate(new ilDateTime($date->getTimestamp(), IL_CAL_UNIX));
            }
        } catch (Throwable $e) {
            $text = $this->plugin->txt('not_specified');
        }

        ilDatePresentation::setUseRelativeDates($old_relative);
        return $text;
    }


    /**
     * Format a time period from datetime objects with fallback for missing values
     */
    public function formatPeriod(?DateTimeInterface $start, ?DateTimeInterface $end, $relative = true): string
    {
        $old_relative = ilDatePresentation::useRelativeDates();
        ilDatePresentation::setUseRelativeDates($relative);

        try {
            if($start === null  && $end === null) {
                $text = $this->plugin->txt('not_specified');
            } elseif ($end === null) {
                $text =
                    $this->plugin->txt('period_only_from') . ' ' .
                    ilDatePresentation::formatDate(new ilDateTime($start->getTimestamp(), IL_CAL_UNIX));
            } elseif ($start === null) {
                $text =
                    $this->plugin->txt('period_only_until') . ' ' .
                    ilDatePresentation::formatDate(new ilDateTime($end->getTimestamp(), IL_CAL_UNIX));
            } else {
                $text = $this->plugin->txt('period_from') . ' ' .
                    ilDatePresentation::formatDate(new ilDateTime($start->getTimestamp(), IL_CAL_UNIX)) . ' ' .
                    $this->plugin->txt('period_until') . ' ' .
                    ilDatePresentation::formatDate(new ilDateTime($end->getTimestamp(), IL_CAL_UNIX));
            }
        } catch (Throwable $e) {
            $text = $this->plugin->txt('not_specified');
        }

        ilDatePresentation::setUseRelativeDates($old_relative);
        return $text;
    }

    /**
     * Format a duration given in seconds as day, hours, minutes
     */
    public function formatDuration($seconds): string
    {
        $duration = (int) $seconds;
        $days = floor($duration / (24 * 3600));
        $hours = floor(($duration - $days * 24 * 3600) / 3600);
        $minutes = floor(($duration - $days * 24 * 3600 - $hours * 3600) / 60);
        $seconds = $duration % 60;

        $parts = [];
        if (!empty($days)) {
            $parts[] = ($days == 1) ? $this->plugin->txt('one_day') : sprintf($this->plugin->txt('x_days'), $days);
        }
        if (!empty($hours)) {
            $parts[] = ($hours == 1) ? $this->plugin->txt('one_hour') : sprintf($this->plugin->txt('x_hours'), $hours);
        }
        if (!empty($minutes)) {
            $parts[] = ($minutes == 1) ? $this->plugin->txt('one_minute') : sprintf($this->plugin->txt('x_minutes'), $minutes);
        }
        if (!empty($seconds)) {
            $parts[] = ($seconds == 1) ? $this->plugin->txt('one_second') : sprintf($this->plugin->txt('x_seconds'), $seconds);
        }

        return implode(' ', $parts);
    }

    /**
     * Format a working time
     */
    public function formatWorkingTime(WorkingTime $working_time): string
    {
        if ($working_time->isLimited()) {
            $string = $this->formatPeriod($working_time->getEarliestStart(), $working_time->getLatestEnd());
            if ($working_time->getTimeLimitMinutes()) {
                $string .= ', ' . $this->formatDuration($working_time->getTimeLimitMinutes() * 60);
            }
            return $string;
        }

        return $this->plugin->txt('not_specified');
    }
}