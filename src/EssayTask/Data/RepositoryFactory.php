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

    public function essay(): EssayRepo
    {
        return $this->repo(EssayRepo::class, Essay::class);
    }

    public function essayImage(): EssayImageRepo
    {
        return $this->repo(EssayImageRepo::class, EssayImage::class, $this->db);
    }

    public function writingStep(): WritingStepRepo
    {
        return $this->repo(WritingStepRepo::class, WritingStep::class);
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
