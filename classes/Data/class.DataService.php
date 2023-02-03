<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

use ILIAS\Plugin\LongEssayTask\BaseService;
use ILIAS\Plugin\LongEssayTask\Corrector\CorrectionFilterItem;
use ilObjUser;
use Throwable;

/**
 * Service for handling data related to a task
 * @package ILIAS\Plugin\LongEssayTask\Data
 */
class DataService extends BaseService
{
    /** @var WriterRepository */
    protected $writerRepo;

    /** @var EssayRepository */
    protected $essayRepo;

    /** @var CorrectorRepository */
    protected $correctorRepo;

    /* cached data objects */

    private $ownWriter = null;
    private $ownWriterLoaded = false;

    private $ownEssay = null;
    private $ownEssayLoaded = false;

    private $ownCorrector = null;
    private $ownCorrectorLoaded = false;

    private $ownTimeExtension = null;
    private $ownTimeExtensionLoaded = false;


	const USER_PREF_STATUS = "xlet_correction_status";
	const USER_PREF_POSITION = "xlet_correction_position";
	const ALL = "all";
	private array $correction_status_cache = [];
	private array $correction_position_cache = [];
	public static array $correction_status_list = [CorrectorSummary::STATUS_STARTED, CorrectorSummary::STATUS_DUE,
		CorrectorSummary::STATUS_STITCH, CorrectorSummary::STATUS_AUTHORIZED, self::ALL];
	public static array $corrector_position_list = [1, 2, self::ALL];


    /**
     * @inheritDoc
     */
    public function __construct(int $task_id)
    {
        parent::__construct($task_id);

        $this->writerRepo = $this->localDI->getWriterRepo();
        $this->essayRepo = $this->localDI->getEssayRepo();
        $this->correctorRepo = $this->localDI->getCorrectorRepo();
    }

    /**
     * Get the writer record of the current user
     * @return Writer|null
     */
    public function getOwnWriter() : ?Writer
    {
        if (!$this->ownWriterLoaded) {
            $this->ownWriter = $this->writerRepo->getWriterByUserId($this->dic->user()->getId(), $this->task_id);
            $this->ownWriterLoaded = true;
        }
        return $this->ownWriter;
    }

    /**
     * Get the corrector record of the current user
     * @return Corrector|null
     */
    public function getOwnEssay() : ?Essay
    {
        if (!$this->ownEssayLoaded) {
            if (!empty($writer = $this->getOwnWriter())) {
                $this->ownEssay = $this->essayRepo->getEssayByWriterIdAndTaskId($writer->getId(), $this->task_id);
            }
            $this->ownEssayLoaded = true;
        }
        return $this->ownEssay;
    }

    /**
     * Get the time extension of the current user in seconds
     * @return int
     */
    public function getOwnTimeExtensionSeconds() : int
    {
        if (!$this->ownTimeExtensionLoaded) {
            if (!empty($writer = $this->getOwnWriter())) {
                $this->ownTimeExtension = $this->writerRepo->getTimeExtensionByWriterId($writer->getId(), $this->task_id);
            }
            $this->ownTimeExtensionLoaded = true;
        }
        if (!empty($this->ownTimeExtension)) {
            return (int) $this->ownTimeExtension->getMinutes() * 60;
        }
        return 0;
    }

    /**
     * Get the corrector record of the current user
     * @return Corrector|null
     */
    public function getOwnCorrector() : ?Corrector
    {
        if (!$this->ownCorrectorLoaded) {
            $this->ownCorrector = $this-$this->correctorRepo->getCorrectorByUserId($this->dic->user()->getId(), $this->task_id);
            $this->ownCorrectorLoaded = true;
        }
        return $this->ownCorrector;
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
                    $this->plugin->txt('period_only_from') . ' ' .
                    \ilDatePresentation::formatDate(new \ilDateTime($start, IL_CAL_DATETIME));
            }
            elseif (empty($start)) {
                return
                    $this->plugin->txt('period_only_until') . ' ' .
                    \ilDatePresentation::formatDate(new \ilDateTime($end, IL_CAL_DATETIME));
            }
            else {
                return $this->plugin->txt('period_from') . ' ' .
                \ilDatePresentation::formatDate(new \ilDateTime($start, IL_CAL_DATETIME)) . ' ' .
                $this->plugin->txt('period_until') . ' ' .
                \ilDatePresentation::formatDate(new \ilDateTime($end, IL_CAL_DATETIME));

                // return \ilDatePresentation::formatPeriod(new \ilDateTime($start, IL_CAL_DATETIME), new \ilDateTime($end, IL_CAL_DATETIME));
            }
        }
        catch (Throwable $e) {
            return $this->plugin->txt('not_specified');
        }
    }


	/**
	 * writing status of an essay
	 * @param Essay|null $essay
	 * @return string
	 */
	public function writingStatus(?Essay $essay) : string
	{
		if (empty($essay) || empty($essay->getEditStarted())) {
			return Essay::WRITING_STATUS_NOT_WRITTEN;
		}
		elseif (!empty($essay->getWritingExcluded())) {
			return Essay::WRITING_STATUS_EXCLUDED;
		}
		elseif (empty($essay->getWritingAuthorized())) {
			return Essay::WRITING_STATUS_NOT_AUTHORIZED;
		}
		else {
			// standard case for correction
			return Essay::WRITING_STATUS_AUTHORIZED;
		}
	}

	/**
	 * Format the writing status of an essay
	 * @param Essay|null $essay
	 * @param bool $highlight_correction_specials
	 * @return string
	 */
    public function formatWritingStatus(?Essay $essay, bool $highlight_correction_specials = true) : string
    {
		$status = $this->writingStatus($essay);

		switch($status){
			case Essay::WRITING_STATUS_NOT_WRITTEN:
				if ($highlight_correction_specials) {
					return '<strong>' . $this->plugin->txt('writing_status_not_written') . '</strong>';
				}else{
					return $this->plugin->txt('writing_status_not_written');
				}
			case Essay::WRITING_STATUS_NOT_AUTHORIZED:
				if ($highlight_correction_specials) {
					return '<strong>' . $this->plugin->txt('writing_status_not_authorized') . '</strong>';
				}else{
					return $this->plugin->txt('writing_status_not_authorized');
				}
			case Essay::WRITING_STATUS_EXCLUDED:
				return $this->plugin->txt('writing_excluded_from') . " " .
					\ilObjUser::_lookupFullname($essay->getWritingExcludedBy());
			case Essay::WRITING_STATUS_AUTHORIZED:
				return $this->plugin->txt('writing_status_authorized');
		}
		return "-";
    }

	/**
	 *  the correction status of an essay
	 */
	public function correctionStatus(?Essay $essay) : string
	{
		if (empty($essay) || empty($essay->getWritingAuthorized())) {
			return Essay::CORRECTION_STATUS_NOT_POSSIBLE;
		}
		elseif (!empty($essay->getCorrectionFinalized())) {
			return Essay::CORRECTION_STATUS_FINISHED;
		}
		elseif ($this->localDI->getCorrectorAdminService($this->task_id)->isStitchDecisionNeeded($essay)) {
			return Essay::CORRECTION_STATUS_STITCH_NEEDED;
		}
		else {
			return Essay::CORRECTION_STATUS_OPEN;
		}
	}

    /**
     * Format the correction status of an essay
     */
    public function formatCorrectionStatus(?Essay $essay) : string
    {
		switch($this->correctionStatus($essay)){
			case Essay::CORRECTION_STATUS_NOT_POSSIBLE:
				return $this->plugin->txt('correction_status_not_possible');
			case Essay::CORRECTION_STATUS_FINISHED:
				return $this->plugin->txt('correction_status_finished');
			case Essay::CORRECTION_STATUS_STITCH_NEEDED:
				return $this->plugin->txt('correction_status_stitch_needed');
			case Essay::CORRECTION_STATUS_OPEN:
				return $this->plugin->txt('correction_status_open');
		}
		return " - ";
    }

    /**
     * Format the availability of the final result
     * @param TaskSettings $settings
     * @return string|void
     */
    public function formatResultAvailability(TaskSettings $settings) {
        switch ($settings->getResultAvailableType()) {
            case TaskSettings::RESULT_AVAILABLE_FINALISED:
                return $this->plugin->txt('label_available') . ' '. $this->plugin->txt('result_available_finalised');
            case TaskSettings::RESULT_AVAILABLE_REVIEW:
                return $this->plugin->txt('label_available') . ' '. $this->plugin->txt('result_available_review');
            case TaskSettings::RESULT_AVAILABLE_DATE:
                return $this->plugin->txt('label_available') . ' '. $this->formatPeriod($settings->getResultAvailableDate(), null);
        }
    }


    /**
     * Format the final result stored for an essay
     */
    public function formatFinalResult(?Essay $essay) : string
    {
        if (empty($essay)) {
            return $this->plugin->txt('result_not_available');
        }

        if (empty($essay->getCorrectionFinalized())) {
            return $this->plugin->txt('result_not_finalized');
        }

        if (empty($essay->getFinalGradeLevelId())) {
            $text =  $this->plugin->txt('result_not_graded');
        }
        else {
            $level = $this->localDI->getObjectRepo()->getGradeLevelById($essay->getFinalGradeLevelId());
            $text = $level->getGrade();
        }

        if (!empty($essay->getFinalPoints())) {
            $text .= ' (' . $essay->getFinalPoints() . ' ' . $this->plugin->txt('points') . ')';
        }

        if (!empty($essay->getStitchComment())) {
            $text .= ' ' . $this->plugin->txt('via_stitch_decision');
        }

        return $text;
    }

	/**
	 * @param Essay $essay
	 * @param CorrectorSummary|null $summary
	 * @param bool $without_blocked is needed to lower db calls if its irrelevant that DUE or BLOCKED
	 * @return string
	 * @todo: Pretty costly function when called multiple times in a list, should be optimized by caching or queries
	 */
	public function getOwnCorrectionStatus(Essay $essay, ?CorrectorSummary $summary, bool $without_blocked=false): string
	{
		if (empty($summary) || empty($summary->getLastChange())) {
			if($without_blocked){
				return CorrectorSummary::STATUS_DUE;
			}

			$assignments = $this->correctorRepo->getAssignmentsByWriterId($essay->getWriterId());
			$own_assignment = null;
			$other_assignments = [];

			foreach($assignments as $assignment){
				if($assignment->getCorrectorId() == $summary->getCorrectorId()){
					$own_assignment = $assignment;
				}else{
					$other_assignments[] = $assignment;
				}
			}

			if(!empty($own_assignment) && $own_assignment->getPosition() > 0){
				$one_correction_missing = false;
				// Checks if corrections with a lower position are all already authorized, blocked if they are not
				foreach($other_assignments as $assignment){
					if($assignment->getPosition() < $own_assignment->getPosition()){
						$other_summary = $this->essayRepo->getCorrectorSummaryByEssayIdAndCorrectorId($essay->getId(), $assignment->getCorrectorId());
						if(!empty($other_summary->getCorrectionAuthorized())){
							$one_correction_missing = true;
						}
					}
				}

				if($one_correction_missing){
					return CorrectorSummary::STATUS_BLOCKED;
				}else{
					return CorrectorSummary::STATUS_DUE;
				}
			}else{
				return CorrectorSummary::STATUS_DUE;
			}
		}

		if (empty($summary->getCorrectionAuthorized())) {

			return CorrectorSummary::STATUS_STARTED;
		}

		if($this->localDI->getCorrectorAdminService($this->task_id)->isStitchDecisionNeeded($essay)){
			return CorrectorSummary::STATUS_STITCH;
		}

		return CorrectorSummary::STATUS_AUTHORIZED;
	}

    /**
     * Format the result from a single correction
     */
    public function formatCorrectionResult(?CorrectorSummary $summary, bool $onlyStatus = false, $onlyAuthorizedGrades = false) : string
    {
        if (empty($summary) || empty($summary->getLastChange())) {
            return $this->plugin->txt('grading_not_started');
        }

		$grade = function ($text) use ($summary) {
			$grade = null;
			$points = null;

			if ($level = $this->localDI->getObjectRepo()->getGradeLevelById((int) $summary->getGradeLevelId())) {
				$grade = $level->getGrade();
			}
			if (!empty($summary->getPoints())) {
				$points = ($grade ? " (": "(") . $summary->getPoints() . ' ' . $this->plugin->txt('points') . ')';
			}
			return ($grade||$points) ? "$text - $grade$points" :  $text;
		};

        if (empty($summary->getCorrectionAuthorized())) {
			$text = $this->plugin->txt('grading_open');

			if($onlyStatus || $onlyAuthorizedGrades) {
				return  $text;
			}

			return $grade($text);
        }

        $text = $this->plugin->txt('grading_authorized');

        if ($onlyStatus) {
            return $text;
        }

        return $grade($text);
    }

	/**
	 * Is Correction result open?
	 * @param CorrectorSummary|null $summary
	 * @return bool
	 */
	public function isCorrectionResultOpen(?CorrectorSummary $summary) : bool
    {
		return empty($summary) || empty($summary->getLastChange()) || empty($summary->getCorrectionAuthorized());
	}

    /**
     * Check if a resource is already available
     */
    public function isResourceAvailable(Resource $resource, TaskSettings $taskSettings) : bool
    {
        if ($resource->getAvailability() == Resource::RESOURCE_AVAILABILITY_BEFORE) {
            return true;
        }

        if ($resource->getAvailability() == Resource::RESOURCE_AVAILABILITY_DURING
            && $this->isInRange(time(), $this->dbTimeToUnix($taskSettings->getWritingStart()), null)) {
            return true;
        }

        if ($resource->getAvailability() == Resource::RESOURCE_AVAILABILITY_AFTER
            && $taskSettings->isSolutionAvailable()
            && $this->isInRange(time(), $this->dbTimeToUnix($taskSettings->getSolutionAvailableDate()),null)) {
            return true;
        }

        return false;
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
                return sprintf($this->plugin->txt('assignment_pos_x'), $assignment->getPosition());
        }
    }

    /**
     * Format the name of a corrector
     */
    public function formatCorrectorAssignment (CorrectorAssignment $assignment, $onlyStatus = false) : string
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
            . ' - ' . $this->formatCorrectionResult($summary, $onlyStatus, true);

     }

    /**
     * Cleanup HTML code from a richtext editor to be securely displayed
     */
     public function cleanupRichText(?string $text) : string
     {
        // allow only HTML tags that are supported in the writer and corrector app
        return \ilUtil::secureString((string) $text, true,
            '<p><div><br><strong><b><em><i><u><ol><ul><li><h1><h2><h3><h4><h5><h6><pre>');
     }

	/**
	 * save correction status filter value to user preferences
	 *
	 * @param int $user_id
	 * @param $value
	 * @return void
	 */
	public function saveCorrectionStatusFilter(int $user_id, $value)
	{
		if(in_array($value, self::$correction_status_list)){
			ilObjUser::_writePref($user_id, self::USER_PREF_STATUS . "_" . $this->task_id, $value);
			$this->correction_status_cache[$user_id] = $value;
		}
	}

	/**
	 * Get correction status filter value from user preferences
	 *
	 * @param int $user_id
	 * @return string
	 */
	public function getCorrectionStatusFilter(int $user_id): string
	{
		if(isset($this->correction_status_cache[$user_id])){
			return $this->correction_status_cache[$user_id];
		}
		$value = ilObjUser::_lookupPref($user_id, self::USER_PREF_STATUS . "_" . $this->task_id);
		if(in_array($value, self::$correction_status_list)){
			$this->correction_status_cache[$user_id] = $value;
			return $value;
		}
		return self::ALL;
	}

	/**
	 * save corrector position filter value to user preferences
	 *
	 * @param int $user_id
	 * @param $value
	 * @return void
	 */
	public function saveCorrectorPositionFilter(int $user_id, $value)
	{
		if(in_array($value, self::$corrector_position_list)){
			ilObjUser::_writePref($user_id, self::USER_PREF_POSITION . "_" . $this->task_id, $value);
			$this->correction_position_cache[$user_id] = $value;
		}
	}

	/**
	 * Get corrector position filter value from user preferences
     * filter positions starts with 1 (first corrector)
	 * assigned positions start with 0 (first corrector)
	 * @param int $user_id
	 * @return string
	 */
	public function getCorrectorPositionFilter(int $user_id): string
	{
		if(isset($this->correction_position_cache[$user_id])){
			return $this->correction_position_cache[$user_id];
		}
		$value = ilObjUser::_lookupPref($user_id, self::USER_PREF_POSITION . "_" . $this->task_id);
		if(in_array($value, self::$corrector_position_list)){
			$this->correction_position_cache[$user_id] = $value;
			return $value;
		}
		return self::ALL;
	}
}
