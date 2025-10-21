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

namespace ILIAS\Plugin\LongEssayAssessment\Task\Data;

use Edutiek\AssessmentService\Task\Data\CriteriaMode;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;
use Edutiek\AssessmentService\Task\Data\SummaryInclusion;

#[Table(name: 'xlas_ta_corr_settings')]
class CorrectionSettings extends \Edutiek\AssessmentService\Task\Data\CorrectionSettings
{
    #[Key]
    private int $ass_id = 0;
    private string $criteria_mode = '';
    private string $positive_rating = '';
    private string $negative_rating = '';
    private int $fixed_inclusions = 0;
    private int $include_comments = 1;
    private int $include_comment_ratings = 1;
    private int $include_comment_points = 1;
    private int $include_criteria_points = 1;

    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }
    public function getCriteriaMode(): CriteriaMode
    {
        return CriteriaMode::tryFrom($this->criteria_mode) ?? CriteriaMode::NONE;
    }
    public function setCriteriaMode(CriteriaMode $criteria_mode): self
    {
        $this->criteria_mode = $criteria_mode->value;
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
    public function getFixedInclusions(): bool
    {
        return (bool) $this->fixed_inclusions;
    }
    public function setFixedInclusions(bool $fixed_inclusions): self
    {
        $this->fixed_inclusions = (int) $fixed_inclusions;
        return $this;
    }
    public function getIncludeComments(): SummaryInclusion
    {
        return SummaryInclusion::tryFrom($this->include_comments) ?? SummaryInclusion::INCLUDE_NOT;
    }
    public function setIncludeComments(SummaryInclusion $include_comments): self
    {
        $this->include_comments = $include_comments->value;
        return $this;
    }
    public function getIncludeCommentRatings(): SummaryInclusion
    {
        return SummaryInclusion::tryFrom($this->include_comment_ratings) ?? SummaryInclusion::INCLUDE_NOT;
    }
    public function setIncludeCommentRatings(SummaryInclusion $include_comment_ratings): self
    {
        $this->include_comment_ratings = $include_comment_ratings->value;
        return $this;
    }
    public function getIncludeCommentPoints(): SummaryInclusion
    {
        return SummaryInclusion::tryFrom($this->include_comment_points) ?? SummaryInclusion::INCLUDE_NOT;
    }
    public function setIncludeCommentPoints(SummaryInclusion $include_comment_points): self
    {
        $this->include_comment_points = $include_comment_points->value;
        return $this;
    }
    public function getIncludeCriteriaPoints(): SummaryInclusion
    {
        return SummaryInclusion::tryFrom($this->include_criteria_points) ?? SummaryInclusion::INCLUDE_NOT;
    }
    public function setIncludeCriteriaPoints(SummaryInclusion $include_criteria_points): self
    {
        $this->include_criteria_points = $include_criteria_points->value;
        return $this;
    }
}
