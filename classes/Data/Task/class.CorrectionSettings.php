<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use Edutiek\LongEssayAssessmentService\Data\CorrectionSummary;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorSummary;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectionSettings extends RecordData
{
    public const ASSIGN_MODE_RANDOM_EQUAL = 'random_equal';
    
    public const CRITERIA_MODE_NONE = 'none';
    public const CRITERIA_MODE_FIXED = 'fixed';
    public const CRITERIA_MODE_CORRECTOR = 'corr';

    protected const tableName = 'xlas_corr_setting';
    protected const hasSequence = false;
    protected const keyTypes = [
        'task_id' => 'integer',
    ];
    protected const otherTypes = [
        'required_correctors'=> 'integer',
        'mutual_visibility' => 'integer',
        'multi_color_highlight' => 'integer',
        'max_points' => 'integer',
        'max_auto_distance' => 'integer',
        'assign_mode' => 'text',
        'stitch_when_distance' => 'integer',
        'stitch_when_decimals' => 'integer',
        'criteria_mode' => 'text',
        'positive_rating' => 'text',
        'negative_rating' => 'text',
        'anonymize_correctors' => 'integer',
        'fixed_inclusions' => 'integer',
        'include_comments' => 'integer',
        'include_comment_ratings' => 'integer',
        'include_comment_points' => 'integer',
        'include_criteria_points' => 'integer',
        'reports_enabled' => 'integer',
        'reports_available_start' => 'datetime'
    ];

    protected int $task_id;
    protected int $required_correctors = 2;
    protected int $mutual_visibility = 1;
    protected int $multi_color_highlight = 1;
    protected int $max_points = 0;
    protected int $max_auto_distance = 0;
    protected string $assign_mode = self::ASSIGN_MODE_RANDOM_EQUAL;
    protected int $stitch_when_distance = 1;
    protected int $stitch_when_decimals = 1;
    protected string $criteria_mode = self::CRITERIA_MODE_NONE;
    protected string $positive_rating = "";
    protected string $negative_rating = "";
    protected int $anonymize_correctors = 0;
    protected int $fixed_inclusions = 0;
    protected int $include_comments = CorrectorSummary::INCLUDE_INFO;
    protected int $include_comment_ratings = CorrectorSummary::INCLUDE_INFO;
    protected int $include_comment_points = CorrectorSummary::INCLUDE_INFO;
    protected int $include_criteria_points = CorrectorSummary::INCLUDE_INFO;
    protected int $reports_enabled = 0;
    protected ?string $reports_available_start = null;

    public function __construct(int $task_id)
    {
        $this->task_id = $task_id;
    }

    public static function model()
    {
        return new self(0);
    }


    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    /**
     * @param int $task_id
     * @return CorrectionSettings
     */
    public function setTaskId(int $task_id): CorrectionSettings
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getRequiredCorrectors(): int
    {
        return $this->required_correctors;
    }

    /**
     * @param int $required_correctors
     * @return CorrectionSettings
     */
    public function setRequiredCorrectors(int $required_correctors): CorrectionSettings
    {
        $this->required_correctors = $required_correctors;
        return $this;
    }

    /**
     * @return int
     */
    public function getMutualVisibility(): int
    {
        return $this->mutual_visibility;
    }

    /**
     * @param int $mutual_visibility
     * @return CorrectionSettings
     */
    public function setMutualVisibility(int $mutual_visibility): CorrectionSettings
    {
        $this->mutual_visibility = $mutual_visibility;
        return $this;
    }

    /**
     * @return int
     */
    public function getMultiColorHighlight(): int
    {
        return $this->multi_color_highlight;
    }

    /**
     * @param int $multi_color_highlight
     * @return CorrectionSettings
     */
    public function setMultiColorHighlight(int $multi_color_highlight): CorrectionSettings
    {
        $this->multi_color_highlight = $multi_color_highlight;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxPoints(): int
    {
        return $this->max_points;
    }

    /**
     * @param int $max_points
     * @return CorrectionSettings
     */
    public function setMaxPoints(int $max_points): CorrectionSettings
    {
        $this->max_points = $max_points;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxAutoDistance(): float
    {
        return $this->max_auto_distance;
    }

    /**
     * @param int $max_auto_distance
     * @return CorrectionSettings
     */
    public function setMaxAutoDistance(float $max_auto_distance): CorrectionSettings
    {
        $this->max_auto_distance = $max_auto_distance;
        return $this;
    }

    /**
     * @return string
     */
    public function getAssignMode(): string
    {
        return $this->assign_mode;
    }

    /**
     * @param string $assign_mode
     * @return CorrectionSettings
     */
    public function setAssignMode(string $assign_mode): CorrectionSettings
    {
        $this->assign_mode = $assign_mode;
        return $this;
    }

    /**
     * @return int
     */
    public function getStitchWhenDistance(): bool
    {
        return (bool) $this->stitch_when_distance;
    }

    /**
     * @param int $stitch_when_distance
     */
    public function setStitchWhenDistance(bool $stitch_when_distance): void
    {
        $this->stitch_when_distance = (int) $stitch_when_distance;
    }

    /**
     * @return int
     */
    public function getStitchWhenDecimals(): bool
    {
        return (bool) $this->stitch_when_decimals;
    }

    /**
     * @param int $stitch_when_decimals
     */
    public function setStitchWhenDecimals(bool $stitch_when_decimals): void
    {
        $this->stitch_when_decimals = (int) $stitch_when_decimals;
    }

    /**
     * @return string
     */
    public function getCriteriaMode(): string
    {
        return $this->criteria_mode;
    }

    /**
     * @param string $criteria_mode
     */
    public function setCriteriaMode(string $criteria_mode): void
    {
        $this->criteria_mode = $criteria_mode;
    }

    public function getPositiveRating(): string
    {
        return $this->positive_rating;
    }

    public function setPositiveRating(string $positive_rating): CorrectionSettings
    {
        $this->positive_rating = $positive_rating;
        return $this;
    }

    public function getNegativeRating(): string
    {
        return $this->negative_rating;
    }

    public function setNegativeRating(string $negative_rating): CorrectionSettings
    {
        $this->negative_rating = $negative_rating;
        return $this;
    }

    public function getAnonymizeCorrectors() : bool
    {
        return $this->anonymize_correctors;
    }

    public function setAnonymizeCorrectors(bool $anonymize_correctors) : CorrectionSettings
    {
        $this->anonymize_correctors = (int) $anonymize_correctors;
        return $this;
    }

    public function getFixedInclusions() : bool
    {
        return (bool) $this->fixed_inclusions;
    }

    public function setFixedInclusions(bool $fixed_inclusions) : void
    {
        $this->fixed_inclusions = (int) $fixed_inclusions;
    }

    public function getIncludeComments() : int
    {
        return $this->include_comments;
    }

    public function setIncludeComments(int $include_comments) : void
    {
        $this->include_comments = $include_comments;
    }

    public function getIncludeCommentRatings() : int
    {
        return $this->include_comment_ratings;
    }

    public function setIncludeCommentRatings(int $include_comment_ratings) : void
    {
        $this->include_comment_ratings = $include_comment_ratings;
    }

    public function getIncludeCommentPoints() : int
    {
        return $this->include_comment_points;
    }

    public function setIncludeCommentPoints(int $include_comment_points) : void
    {
        $this->include_comment_points = $include_comment_points;
    }

    public function getIncludeCriteriaPoints() : int
    {
        return $this->include_criteria_points;
    }

    public function setIncludeCriteriaPoints(int $include_criteria_points) : void
    {
        $this->include_criteria_points = $include_criteria_points;
    }

    public function getReportsEnabled() : bool
    {
        return (bool) $this->reports_enabled;
    }

    public function setReportsEnabled(bool $reports_enabled) : void
    {
        $this->reports_enabled = (int) $reports_enabled;
    }

    public function getReportsAvailableStart() : ?string
    {
        return $this->reports_available_start;
    }

    public function setReportsAvailableStart(?string $reports_available_start) : void
    {
        $this->reports_available_start = $reports_available_start;
    }

}
