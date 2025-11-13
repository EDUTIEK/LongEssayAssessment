<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Task\Data;

use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryFactory as FactoryTrait;

class RepositoryFactory implements \Edutiek\AssessmentService\Task\Data\Repositories
{
    use FactoryTrait;

    public function __construct(
        Generate $g,
        ilDBInterface $db,
    ) {
        $this->g = $g;
        $this->db = $db;
    }

    public function correctorAssignment(): CorrectorAssignmentRepo
    {
        return $this->repo(CorrectorAssignmentRepo::class, CorrectorAssignment::class);
    }

    public function resource(): ResourceRepo
    {
        return $this->repo(ResourceRepo::class, Resource::class);
    }

    public function settings(): SettingsRepo
    {
        return $this->repo(SettingsRepo::class, Settings::class);
    }

    public function writerAnnotation(): WriterAnnotationRepo
    {
        return $this->repo(WriterAnnotationRepo::class, WriterAnnotation::class);
    }
    public function correctionSettings(): CorrectionSettingsRepo
    {
        return $this->repo(CorrectionSettingsRepo::class, CorrectionSettings::class);
    }

    public function correctorComment(): CorrectorCommentRepo
    {
        return $this->repo(CorrectorCommentRepo::class, CorrectorComment::class);
    }

    public function correctorPoints(): CorrectorPointsRepo
    {
        return $this->repo(CorrectorPointsRepo::class, CorrectorPoints::class);
    }

    public function correctorPrefs(): CorrectorPrefsRepo
    {
        return $this->repo(CorrectorPrefsRepo::class, CorrectorPrefs::class);
    }

    public function correctorSnippets(): CorrectorSnippetRepo
    {
        return $this->repo(CorrectorSnippetRepo::class, CorrectorSnippet::class);
    }

    public function correctorSummary(): CorrectorSummaryRepo
    {
        return $this->repo(CorrectorSummaryRepo::class, CorrectorSummary::class);
    }

    public function correctorSnippet(): CorrectorTaskPrefsRepo
    {
        return $this->repo(CorrectorSnippetRepo::class, CorrectorSnippet::class);
    }

    public function correctorTaskPrefs(): CorrectorTaskPrefsRepo
    {
        return $this->repo(CorrectorTaskPrefsRepo::class, CorrectorTaskPrefs::class);
    }

    public function ratingCriterion(): RatingCriterionRepo
    {
        return $this->repo(RatingCriterionRepo::class, RatingCriterion::class);
    }
}
