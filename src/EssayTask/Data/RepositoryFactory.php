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

    public function correctorSummary(): CorrectorSummaryRepo
    {
        return $this->repo(CorrectorSummaryRepo::class, CorrectorSummary::class);
    }

    public function correctorTaskPrefs(): CorrectorTaskPrefsRepo
    {
        return $this->repo(CorrectorTaskPrefsRepo::class, CorrectorTaskPrefs::class);
    }

    public function essay(): EssayRepo
    {
        return $this->repo(EssayRepo::class, Essay::class);
    }

    public function essayImage(): EssayImageRepo
    {
        return $this->repo(EssayImageRepo::class, EssayImage::class);
    }

    public function ratingCriterion(): RatingCriterionRepo
    {
        return $this->repo(RatingCriterionRepo::class, RatingCriterion::class);
    }

    public function taskSettings(): TaskSettingsRepo
    {
        return $this->repo(TaskSettingsRepo::class, TaskSettings::class);
    }

    public function writerHistory(): WriterHistoryRepo
    {
        return $this->repo(WriterHistoryRepo::class, WriterHistory::class);
    }

    public function writerNotice(): WriterNoticeRepo
    {
        return $this->repo(WriterNoticeRepo::class, WriterNotice::class);
    }

    public function writerPrefs(): WriterPrefsRepo
    {
        return $this->repo(WriterPrefsRepo::class, WriterPrefs::class);
    }

    public function writingSettings(): WritingSettingsRepo
    {
        return $this->repo(WritingSettingsRepo::class, WritingSettings::class);
    }
}
