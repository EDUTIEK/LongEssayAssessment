<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo;

use ilDBInterface;

trait RepositoryFactory
{
    private readonly Generate $g;
    private readonly ilDBInterface $db;

    private array $instances = [];
    private ?CacheRepository $last_added = null;

    /**
     * @template R
     *
     * @param class-string<R> $repo
     * @return R
     */
    private function repo(string $repo, string $model, ...$args): object
    {
        return $this->instances[$repo] ??= new $repo($this->add($model), ...$args);
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
