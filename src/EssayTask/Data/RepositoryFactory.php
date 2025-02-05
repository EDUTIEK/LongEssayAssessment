<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\EssayTask\Data;

use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryFactory as FactoryTrait;

class RepositoryFactory implements \Edutiek\AssessmentService\EssayTask\Data\Repositories
{
    use FactoryTrait;

    public function __construct(
        Generate $g,
        ilDBInterface $db,
    ) {
        $this->g = $g;
        $this->db = $db;
    }

    public function correctionSettingsRepo(): CorrectionSettingsRepo
    {
        return $this->repo(CorrectionSettingsRepo::class, CorrectionSettings::class);
    }

    public function correctorCommentRepo(): CorrectorCommentRepo
    {
        return $this->repo(CorrectorCommentRepo::class, CorrectorComment::class);
    }

    public function correctorPointsRepo(): CorrectorPointsRepo
    {
        return $this->repo(CorrectorPointsRepo::class, CorrectorPoints::class);
    }

    public function correctorPrefsRepo(): CorrectorPrefsRepo
    {
        return $this->repo(CorrectorPrefsRepo::class, CorrectorPrefs::class);
    }

    public function correctorSummaryRepo(): CorrectorSummaryRepo
    {
        return $this->repo(CorrectorSummaryRepo::class, CorrectorSummary::class);
    }

    public function correctorTaskPrefsRepo(): CorrectorTaskPrefsRepo
    {
        return $this->repo(CorrectorTaskPrefsRepo::class, CorrectorTaskPrefs::class);
    }

    public function essayRepo(): EssayRepo
    {
        return $this->repo(EssayRepo::class, Essay::class);
    }

    public function essayImageRepo(): EssayImageRepo
    {
        return $this->repo(EssayImageRepo::class, EssayImage::class);
    }

    public function ratingCriterionRepo(): RatingCriterionRepo
    {
        return $this->repo(RatingCriterionRepo::class, RatingCriterion::class);
    }

    public function taskSettingsRepo(): TaskSettingsRepo
    {
        return $this->repo(TaskSettingsRepo::class, TaskSettings::class);
    }

    public function writerHistoryRepo(): WriterHistoryRepo
    {
        return $this->repo(WriterHistoryRepo::class, WriterHistory::class);
    }

    public function writerNoticeRepo(): WriterNoticeRepo
    {
        return $this->repo(WriterNoticeRepo::class, WriterNotice::class);
    }

    public function writerPrefsRepo(): WriterPrefsRepo
    {
        return $this->repo(WriterPrefsRepo::class, WriterPrefs::class);
    }

    public function writingSetingsRepo(): WritingSettingsRepo
    {
        return $this->repo(WritingSettingsRepo::class, WritingSettings::class);
    }
}
