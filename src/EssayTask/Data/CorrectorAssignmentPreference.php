<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\EssayTask\Data;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xles_et_corr_ass_pref')]
class CorrectorAssignmentPreference extends \Edutiek\AssessmentService\EssayTask\Data\CorrectorAssignmentPreference
{
    #[Key]
    private int $corrector_id = 0;
    private float $essay_page_zoom = 0;
    private float $essay_text_zoom = 0;
    private float $summary_text_zoom = 0;
    private int $include_comments = 0;
    private int $include_comment_ratings = 0;
    private int $include_comment_points = 0;
    private int $include_criteria_points = 0;

    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }
    public function setCorrectorId(int $corrector_id): void
    {
        $this->corrector_id = $corrector_id;
    }
    public function getEssayPageZoom(): float
    {
        return $this->essay_page_zoom;
    }
    public function setEssayPageZoom(float $essay_page_zoom): void
    {
        $this->essay_page_zoom = $essay_page_zoom;
    }
    public function getEssayTextZoom(): float
    {
        return $this->essay_text_zoom;
    }
    public function setEssayTextZoom(float $essay_text_zoom): void
    {
        $this->essay_text_zoom = $essay_text_zoom;
    }
    public function getSummaryTextZoom(): float
    {
        return $this->summary_text_zoom;
    }
    public function setSummaryTextZoom(float $summary_text_zoom): void
    {
        $this->summary_text_zoom = $summary_text_zoom;
    }
    public function getIncludeComments(): int
    {
        return $this->include_comments;
    }
    public function setIncludeComments(int $include_comments): void
    {
        $this->include_comments = $include_comments;
    }
    public function getIncludeCommentRatings(): int
    {
        return $this->include_comment_ratings;
    }
    public function setIncludeCommentRatings(int $include_comment_ratings): void
    {
        $this->include_comment_ratings = $include_comment_ratings;
    }
    public function getIncludeCommentPoints(): int
    {
        return $this->include_comment_points;
    }
    public function setIncludeCommentPoints(int $include_comment_points): void
    {
        $this->include_comment_points = $include_comment_points;
    }
    public function getIncludeCriteriaPoints(): int
    {
        return $this->include_criteria_points;
    }
    public function setIncludeCriteriaPoints(int $include_criteria_points): void
    {
        $this->include_criteria_points = $include_criteria_points;
    }
}
