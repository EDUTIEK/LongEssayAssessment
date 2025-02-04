<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\CacheRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\DatabaseRepository;
use ilAccessHandler;
use ilObjectDataCache;
use ilObjectFactory;

class RepositoryFactory implements \Edutiek\AssessmentService\Assessment\Data\Repositories
{
    public function __construct(
        private readonly Generate $g,
        private readonly ilDBInterface $db,
        private readonly ilAccessHandler $access,
        private readonly ilObjectDataCache $data_cache,
        private readonly ilObjectFactory $object_factory
    ) {
    }

    private array $instances = [];
    private ?CacheRepository $last_added;

    public function alert(): AlertRepo
    {
        return $this->repo(AlertRepo::class, Alert::class);
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

    public function location(): LocationRepo
    {
        return $this->repo(LocationRepo::class, Location::class);
    }

    public function orgaSettings(): OrgaSettingsRepo
    {
        return $this->repo(OrgaSettingsRepo::class, OrgaSettings::class);
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

    /**
     * @template R
     *
     * @param class-string<R> $repo
     * @return R
     */
    private function repo(string $repo, string $model): object
    {
        return $this->instances[$repo] ??= new $repo($this->add($model));
    }

    /**
     * @template M
     *
     * @param class-string<M> $model
     * @return CacheRepository<M>
     */
    private function add(string $model): CacheRepository
    {
        $cached = new CacheRepository(new DatabaseRepository($this->db, $this->g->readModel($model)));
        if ($this->last_added !== null) {
            $cached->connectCache($this->last_added);
        }
        return $this->last_added = $cached;
    }
}
