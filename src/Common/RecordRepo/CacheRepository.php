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

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo;

/**
 * @template A of object
 * @implements RepositoryInterface<A>
 */
class CacheRepository implements RepositoryInterface
{
    private array $cache = [];

    /**
     * @var CacheRepository[]
     */
    private array $others = [];

    /**
     * @param RepositoryInterface<A> $r
     */
    public function __construct(private readonly RepositoryInterface $r)
    {
    }

    public function new(): object
    {
        return $this->r->new();
    }

    public function all(): array
    {
        return $this->cache(__FUNCTION__, []);
    }

    public function queryAllRaw(string $query): array
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    public function queryAll(string $query): array
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    public function queryAllBy(array $conditions): array
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    public function queryOne(string $query): ?object
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    public function queryOneBy(array $conditions): ?object
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    public function insert(object $model): void
    {
        $this->r->insert($model);
        $this->clearCache();
    }

    public function replace(object $model): void
    {
        $this->r->replace($model);
        $this->clearCache();
    }

    public function update(object $model): void
    {
        $this->r->update($model);
        $this->clearCache();
    }

    public function delete(object $model): void
    {
        $this->r->delete($model);
        $this->clearCache();
    }

    public function deleteAllBy(array $conditions): void
    {
        $this->r->deleteAllBy($conditions);
        $this->clearCache();
    }

    public function queryIntegers(string $query, string $key): array
    {
        return $this->cache(__FUNCTION__, func_get_args(), [$query]);
    }

    public function queryStrings(string $query, string $key): array
    {
        return $this->cache(__FUNCTION__, func_get_args(), [$query]);
    }

    public function fromRow(array $row): object
    {
        return $this->r->fromRow($row);
    }

    public function toRowWithTypes(object $model): array
    {
        return $this->r->toRowWithTypes($model);
    }

    public function toRow(object $model): array
    {
        return $this->r->toRow($model);
    }

    public function table(): string
    {
        return $this->r->table();
    }

    public function where(array $conditions): string
    {
        return $this->r->where($conditions);
    }

    public function keyFields(): array
    {
        return $this->r->keyFields();
    }

    public function clearCache(): void
    {
        $this->clearExcept([]);
    }

    /**
     * Call this method to connect another CacheRepository to this.
     * This will clear the cache of all explicit and implicit connected caches whenever the cache is cleared for this or a connected repository.
     *
     * E.g.:
     * $a->connectCache($b);
     * $b->connectCache($c);
     * $a->clearCache(); // This will clear all three caches.
     * $c->clearCache(); // This will also clear all three caches.
     *
     * @param CacheRepository $other
     */
    public function connectCache(CacheRepository $other): void
    {
        if (!in_array($other, $this->others, true)) {
            $this->others[] = $other;
        }
        if (!in_array($this, $other->others, true)) {
            $other->others[] = $this;
        }
    }

    /**
     * @param CacheRepository[] $cleared
     * @return CacheRepository[]
     */
    private function clearExcept(array $cleared): array
    {
        if (in_array($this, $cleared, true)) {
            return $cleared;
        }
        $this->cache = [];
        $cleared[] = $this;
        return array_reduce(
            $this->others,
            fn(array $cleared, CacheRepository $other) => $other->clearExcept($cleared),
            $cleared
        );
    }

    private function cache(string $method, array $args, ?array $cache = null)
    {
        $key = $this->key($cache ?? $args);
        return $this->cache[$method][$key] ??= $this->r->$method(...$args);
    }

    private function key(array $keys): string
    {
        return md5(json_encode($keys));
    }
}
