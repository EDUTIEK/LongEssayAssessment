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
        return $this->instances[AlertRepo::class] ??= new AlertRepo($this->add(Alert::class), $this->db);
    }

    public function logEntry(): LogEntryRepo
    {
        return $this->instances[LogEntryRepo::class] ??= new LogEntryRepo($this->add(LogEntry::class), $this->db);
    }

    public function permissions(): PermissionsRepo
    {
        return $this->instances[PermissionsRepo::class] ??= new PermissionsRepo($this->access);
    }

    public function properties(): PropertiesRepo
    {
        return $this->instances[PropertiesRepo::class] ??= new PropertiesRepo($this->data_cache, $this->object_factory);
    }

    private function add(string $model): CacheRepository
    {
        $cached = new CacheRepository(new DatabaseRepository($this->db, $this->g->readModel(Alert::class)));
        if ($this->last_added !== null) {
            $cached->connectCache($this->last_added);
        }
        return $this->last_added = $cached;
    }
}
