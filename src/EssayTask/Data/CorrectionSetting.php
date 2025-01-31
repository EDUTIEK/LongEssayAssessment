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

#[Table(name: 'xlas_et_corr_setting')]
class CorrectionSetting extends \Edutiek\AssessmentService\EssayTask\Data\CorrectionSetting
{
    #[Key]
    private int $ass_id = 0;
    private string $criteria_mode = '';
    private string $positive_rating = '';
    private string $negative_rating = '';
    private int $fixed_inclusions = 0;
    private int $include_comments = 0;
    private int $include_comment_ratings = 0;
    private int $include_comment_points = 0;
    private int $include_criteria_points = 0;

    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }
    public function getCriteriaMode(): string
    {
        return $this->criteria_mode;
    }
    public function setCriteriaMode(string $criteria_mode): self
    {
        $this->criteria_mode = $criteria_mode;
        return $this;
    }
    public function getPositiveRating(): string
    {
        return $this->positive_rating;
    }
    public function setPositiveRating(string $positive_rating): self
    {
        $this->positive_rating = $positive_rating;
        return $this;
    }
    public function getNegativeRating(): string
    {
        return $this->negative_rating;
    }
    public function setNegativeRating(string $negative_rating): self
    {
        $this->negative_rating = $negative_rating;
        return $this;
    }
    public function getFixedInclusions(): int
    {
        return $this->fixed_inclusions;
    }
    public function setFixedInclusions(int $fixed_inclusions): self
    {
        $this->fixed_inclusions = $fixed_inclusions;
        return $this;
    }
    public function getIncludeComments(): int
    {
        return $this->include_comments;
    }
    public function setIncludeComments(int $include_comments): self
    {
        $this->include_comments = $include_comments;
        return $this;
    }
    public function getIncludeCommentRatings(): int
    {
        return $this->include_comment_ratings;
    }
    public function setIncludeCommentRatings(int $include_comment_ratings): self
    {
        $this->include_comment_ratings = $include_comment_ratings;
        return $this;
    }
    public function getIncludeCommentPoints(): int
    {
        return $this->include_comment_points;
    }
    public function setIncludeCommentPoints(int $include_comment_points): self
    {
        $this->include_comment_points = $include_comment_points;
        return $this;
    }
    public function getIncludeCriteriaPoints(): int
    {
        return $this->include_criteria_points;
    }
    public function setIncludeCriteriaPoints(int $include_criteria_points): self
    {
        $this->include_criteria_points = $include_criteria_points;
        return $this;
    }
}
