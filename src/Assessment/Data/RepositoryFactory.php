<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryFactory as FactoryTrait;
use ilAccessHandler;
use ilObjectDataCache;
use ilObjectFactory;
use ilTree;

class RepositoryFactory implements \Edutiek\AssessmentService\Assessment\Data\Repositories
{
    use FactoryTrait;

    public function __construct(
        Generate $g,
        ilDBInterface $db,
        private readonly ilAccessHandler $access,
        private readonly ilTree $tree,
        private readonly ilObjectDataCache $data_cache,
        private readonly ilObjectFactory $object_factory
    ) {
        $this->g = $g;
        $this->db = $db;
    }

    public function alert(): AlertRepo
    {
        return $this->repo(AlertRepo::class, Alert::class);
    }

    public function location(): LocationRepo
    {
        return $this->repo(LocationRepo::class, Location::class);
    }

    public function logEntry(): LogEntryRepo
    {
        return $this->repo(LogEntryRepo::class, LogEntry::class);
    }

    public function permissions(): PermissionsRepo
    {
        return $this->instances[PermissionsRepo::class] ??= new PermissionsRepo($this->access);
    }

    public function properties(): PropertiesRepo
    {
        return $this->instances[PropertiesRepo::class] ??= new PropertiesRepo($this->data_cache, $this->object_factory);
    }

    public function contextInfo(): ContextInfoRepo
    {
        return $this->instances[ContextInfoRepo::class] ??= new ContextInfoRepo($this->tree);
    }

    public function correctionSettings(): CorrectionSettingsRepo
    {
        return $this->repo(CorrectionSettingsRepo::class, CorrectionSettings::class);
    }

    public function corrector(): CorrectorRepo
    {
        return $this->repo(CorrectorRepo::class, Corrector::class);
    }

    public function gradeLevel(): GradeLevelRepo
    {
        return $this->repo(GradeLevelRepo::class, GradeLevel::class);
    }

    public function orgaSettings(): OrgaSettingsRepo
    {
        return $this->repo(OrgaSettingsRepo::class, OrgaSettings::class);
    }

    public function pdfConfig(): PdfConfigRepo
    {
        return $this->repo(PdfConfigRepo::class, PdfConfig::class);
    }

    public function pdfSettings(): PdfSettingsRepo
    {
        return $this->repo(PdfSettingsRepo::class, PdfSettings::class);
    }

    public function token(): TokenRepo
    {
        return $this->repo(TokenRepo::class, Token::class);
    }

    public function writer(): WriterRepo
    {
        return $this->repo(WriterRepo::class, Writer::class);
    }

    public function disabledGroup(): DisabledGroupRepo
    {
        return $this->repo(DisabledGroupRepo::class, DisabledGroup::class);
    }
}
