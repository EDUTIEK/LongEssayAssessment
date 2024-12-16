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

namespace ILIAS\Plugin\LongEssayAssessment\Data;

/**
 * @template A of object
 * @implements Repository<A>
 */
class CacheRepository implements Repository
{
    private array $cache = [];

    /**
     * @param Repository<A> $r
     */
    public function __construct(private readonly Repository $r)
    {
    }

    public function all(): array
    {
        return $this->cache(__FUNCTION__, []);
    }

    public function queryAll(string $query): array
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    public function queryOne(string $query): ?object
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    public function insert(object $record): void
    {
        $this->r->insert($record);
        $this->clearCache();
    }

    public function replace(object $record): void
    {
        $this->r->replace($record);
        $this->clearCache();
    }

    public function update(object $record): void
    {
        $this->r->update($record);
        $this->clearCache();
    }

    public function delete(object $record): void
    {
        $this->r->delete($record);
        $this->clearCache();
    }

    public function queryIntegers(string $query, string $key): array
    {
        return $this->cache(__FUNCTION__, func_get_args(), [$query]);
    }

    public function fromRow(array $row): object
    {
        return $this->r->fromRow($row);
    }

    public function toRowWithTypes(object $instance): array
    {
        return $this->r->toRowWithTypes($instance);
    }

    public function toRow(object $instance): array
    {
        return $this->r->toRow($instance);
    }

    public function table(): string
    {
        return $this->r->table();
    }

    public function keyFields(): array
    {
        return $this->r->keyFields();
    }

    public function clearCache(): void
    {
        $this->cache = [];
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
