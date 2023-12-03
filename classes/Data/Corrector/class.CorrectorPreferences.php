<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Corrector;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use Edutiek\LongEssayAssessmentService\Data\CorrectionSummary;

/**
 * Corrector Preferences
 *
 * @author Fred Neumann <neumann@ilias.de>
 */
class CorrectorPreferences extends RecordData
{

	protected const tableName = 'xlas_corrector_prefs';
	protected const hasSequence = false;
	protected const keyTypes = [
		'corrector_id' => 'integer',
	];
	protected const otherTypes = [
		'essay_page_zoom' => 'float',
		'essay_text_zoom' => 'float',
        'summary_text_zoom' => 'float',
		'include_comments' => 'integer',
		'include_comment_ratings' => 'integer',
		'include_comment_points' => 'integer',
        'include_criteria_points' => 'integer',
        'include_writer_notes' => 'integer',
	];

    protected int $corrector_id = 0;
    protected float $essay_page_zoom = 0.25;            
    protected float $essay_text_zoom =  1;              
    protected float $summary_text_zoom =  1;            
    protected int $include_comments = CorrectionSummary::INCLUDE_INFO;      
    protected int $include_comment_ratings = CorrectionSummary::INCLUDE_INFO;
    protected int $include_comment_points = CorrectionSummary::INCLUDE_INFO;  
    protected int $include_criteria_points = CorrectionSummary::INCLUDE_INFO; 
    protected int $include_writer_notes = CorrectionSummary::INCLUDE_INFO; 
    
	public function __construct(int $corrector_id)
	{
		$this->corrector_id = $corrector_id;
	}

	public static function model(): CorrectorPreferences
	{
		return new self(0);
	}

    /**
     * Get the corrector id
     */
    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }

    
    /**
     * Set the corrector_id
     */
    public function setCorrectorId(int $corrector_id): CorrectorPreferences
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }

    /**
     * Get the zoom of a pdf page display
     */
    public function getEssayPageZoom(): float
    {
        return $this->essay_page_zoom;
    }

    /**
     * Set the zoom of a pdf page display
     */
    public function setEssayPageZoom(float $essay_page_zoom): CorrectorPreferences
    {
        $this->essay_page_zoom = $essay_page_zoom;
        return $this;
    }

    /**
     * Get the zoom of an essay text display
     */
    public function getEssayTextZoom(): float
    {
        return $this->essay_text_zoom;
    }

    /**
     * Set the zoom of an essay text display
     */
    public function setEssayTextZoom(float $essay_text_zoom): CorrectorPreferences
    {
        $this->essay_text_zoom = $essay_text_zoom;
        return $this;
    }

    /**
     * Get the zoom of the summary editing
     */
    public function getSummaryTextZoom(): float
    {
        return $this->summary_text_zoom;
    }

    /**
     * Set the zoom of the summary editing
     */
    public function setSummaryTextZoom(float $summary_text_zoom): CorrectorPreferences
    {
        $this->summary_text_zoom = $summary_text_zoom;
        return $this;
    }

    /**
     * Get how to include comments in the authorized correction
     */
    public function getIncludeComments(): int
    {
        return $this->include_comments;
    }

    /**
     * Set how to include comments in the authorized correction
     */
    public function setIncludeComments(int $include_comments): CorrectorPreferences
    {
        $this->include_comments = $include_comments;
        return $this;
    }

    /**
     * Get how to include comment ratings in the authorized correction
     */
    public function getIncludeCommentRatings(): int
    {
        return $this->include_comment_ratings;
    }

    /**
     * Set how to include comment ratings in the authorized correction
     */
    public function setIncludeCommentRatings(int $include_comment_ratings): CorrectorPreferences
    {
        $this->include_comment_ratings = $include_comment_ratings;
        return $this;
    }

    /**
     * Get how to include comment points in the authorized correction
     */
    public function getIncludeCommentPoints(): int
    {
        return $this->include_comment_points;
    }

    /**
     * Set how to include comment points in the authorized correction
     */
    public function setIncludeCommentPoints(int $include_comment_points): CorrectorPreferences
    {
        $this->include_comment_points = $include_comment_points;
        return $this;
    }

    /**
     * Get how to include criteria points in the authorized correction
     */
    public function getIncludeCriteriaPoints(): int
    {
        return $this->include_criteria_points;
    }

    /**
     * Set how to include criteria points in the authorized correction
     */
    public function setIncludeCriteriaPoints(int $include_criteria_points): CorrectorPreferences
    {
        $this->include_criteria_points = $include_criteria_points;
        return $this;
    }

    /**
     * Get how to  include writer notes in the authorized correction
     */
    public function getIncludeWriterNotes(): int
    {
        return $this->include_writer_notes;
    }

    /**
     * Set how to include writer notes in the authorized correction
     */
    public function setIncludeWriterNotes(int $include_writer_notes): CorrectorPreferences
    {
        $this->include_writer_notes = $include_writer_notes;
        return $this;
    }
}