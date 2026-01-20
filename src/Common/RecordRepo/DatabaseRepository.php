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

use DateTimeZone;
use ilDBConstants;
use DateTime;
use DateTimeImmutable;
use Exception;
use ilDBInterface;
use PDOStatement;

/**
 * @template A of object
 * @implements RepositoryInterface<A>
 */
class DatabaseRepository implements RepositoryInterface
{
    private DateTimeZone $time_zone;
    private array $dehydrated = [];
    private ?array $keys = null;

    public function __construct(
        private readonly ilDBInterface $db,
        private readonly array $model
    ) {
        $this->time_zone = new DateTimeZone(date_default_timezone_get());
    }

    public function new(): object
    {
        return new $this->model['class']();
    }

    public function all(): array
    {
        return $this->queryAll($this->sqlSelect());
    }

    public function queryAll(string $query): array
    {
        return array_map($this->fromRow(...), $this->queryAllRaw($query));
    }

    public function queryOne(string $query): ?object
    {
        $row = $this->db->fetchAssoc($this->db->query($query . ' LIMIT 0, 1'));
        return $row ? $this->fromRow($row) : null;
    }

    public function insert(object $model): void
    {
        $this->updateSequence($model);
        $this->db->insert($this->table(), $this->toRowWithTypes($model));
    }

    public function replace(object $model): void
    {
        $this->updateSequence($model);

        $row = $this->toRowWithTypes($model);

        $keys = array_column($this->keyFields(), 'db_name');
        $keys = array_combine($keys, array_fill(0, count($keys), null));

        $this->db->replace(
            $this->table(),
            array_intersect_key($row, $keys),
            array_diff_key($row, $keys)
        );
    }

    public function update(object $model): void
    {
        $row = $this->toRowWithTypes($model);

        $keys = array_column($this->keyFields(), 'db_name');
        $keys = array_combine($keys, array_fill(0, count($keys), null));

        $this->db->update(
            $this->table(),
            array_diff_key($row, $keys),
            array_intersect_key($row, $keys),
        );
    }

    public function delete(object $model): void
    {
        $row = $this->toRowWithTypes($model);

        $where = [];
        foreach ($this->keyFields() as $field) {
            $where[] = $this->db->quoteIdentifier($field['db_name']) . ' = ' . $this->db->quote(...array_reverse($row[$field['db_name']]));
        }

        $this->db->manipulate($this->sqlDelete(join(' AND ', $where)));
    }

    public function queryIntegersBy($conditions, string $key): array
    {
        return $this->queryIntegers($this->sqlSelectKey($key, $this->where($conditions)), $key);
    }

    public function queryIntegers(string $query, string $key): array
    {
        return array_map(intval(...), array_column($this->queryAllRaw($query), $key));
    }

    public function queryStrings(string $query, string $key): array
    {
        return array_map(strval(...), array_column($this->queryAllRaw($query), $key));
    }

    public function fromRow(array $row, ?object $instance = null): object
    {
        $instance ??= new $this->model['class']();
        $set = (function ($key, $value) {
            $this->$key = $value;
        })->bindTo($instance, $instance);

        foreach ($this->model['properties'] as $property_name => $field) {
            if (isset($row[$field['db_name']])) {
                $set($property_name, $this->dbToClassValue($row[$field['db_name']], $field['class_type']));
            }
        }
        return $instance;
    }

    public function toRowWithTypes(object $model): array
    {
        $get = (function (string $key) {
            return $this->$key;
        })->bindTo($model, $model);

        $row = [];
        foreach ($this->model['properties'] as $property_name => $field) {
            $row[$field['db_name']] = [$field['db_type'], $this->classToDbValue($get($property_name), $field['class_type'])];
        }

        return $row;
    }

    public function toRow(object $model): array
    {
        $row = $this->toRowWithTypes($model);
        return array_combine(array_keys($row), array_column($row, 0));
    }

    public function table(): string
    {
        return $this->model['db_name'];
    }

    public function keyFields(): array
    {
        return $this->keys ??= array_filter($this->model['properties'], fn(array $p) => $p['key']);
    }

    public function hasBy($conditions): bool
    {
        $result = $this->db->query($this->sqlHas($this->where($conditions)));
        $row = $this->db->fetchAssoc($result);
        return (bool) $row['found'];
    }

    public function countBy($conditions): int
    {
        $result = $this->db->query($this->sqlCount($this->where($conditions)));
        $row = $this->db->fetchAssoc($result);
        return (int) ($row['count'] ?? 0);
    }

    public function queryAllBy($conditions, array $order = []): array
    {
        return $this->queryAll($this->sqlSelect($this->where($conditions), $this->order($order)));
    }

    public function queryOneBy($conditions): ?object
    {
        return $this->queryOne($this->sqlSelect($this->where($conditions)));
    }

    public function deleteAllBy($conditions): void
    {
        $this->db->manipulate($this->sqlDelete($this->where($conditions)));
    }

    public function where($conditions): string
    {
        if (is_string($conditions)) {
            return $conditions;
        }
        return join(' AND ', array_map($this->equals(...), array_keys($conditions), array_values($conditions)));
    }

    /**
     * @param array|string|int|bool|float|null $right
     */
    private function equals(string $left, $right): string
    {
        $quote_id = $this->db->quoteIdentifier(...);

        if ($right instanceof Value) {
            return $right->condition($quote_id($left));
        }
        return match (gettype($right)) {
            'integer', 'string', 'float' => $quote_id($left) . ' = ' . $this->db->quote($right, ilDBConstants::T_TEXT),
            'boolean' => $quote_id($left) . ' = ' . $this->db->quote((int) $right, ilDBConstants::T_INTEGER),
            'array' => $this->db->in($left, array_map(fn($x) => is_bool($x) ? (int) $x : $x, $right), false, ilDBConstants::T_TEXT),
            'NULL' => $quote_id($left) . ' IS NULL',
            default => throw new Exception('Unsupported type: ' . gettype($right)),
        };
    }

    private function sqlCount(string $where = '1'): string
    {
        return 'SELECT COUNT(*) as count FROM ' . $this->db->quoteIdentifier($this->table()) . ' WHERE ' . $where;
    }

    private function sqlSelectKey(string $key, string $where = '1'): string
    {
        return 'SELECT ' . $this->db->quoteIdentifier($key) . ' FROM ' . $this->db->quoteIdentifier($this->table()) . ' WHERE ' . ($where ?: '1') ;
    }

    private function sqlSelect(string $where = '1', string $order = ''): string
    {
        return 'SELECT * FROM ' . $this->db->quoteIdentifier($this->table()) . ' WHERE ' . ($where ?: '1') . ($order ? ' ORDER BY ' . $order : '');
    }

    private function sqlDelete(string $where): string
    {
        return 'DELETE FROM ' . $this->db->quoteIdentifier($this->table()) . ' WHERE ' . $where;
    }

    private function sqlHas(string $where): string
    {
        return 'SELECT EXISTS (SELECT 1 FROM ' . $this->db->quoteIdentifier($this->table()) . ' WHERE ' . $where . ') AS found';
    }

    /**
     * @return array<int|string, mixed>[]
     */
    public function queryAllRaw(string $query): array
    {
        $result = $this->db->query($query);
        return $this->db->fetchAll($result);
    }

    private function dbToClassValue($value, string $type)
    {
        return match ($this->handleNullable($type, $value)) {
            null => null,
            'string' => (string) $value,
            'int' => (int) $value,
            'bool' => (bool) $value,
            'float' => (float) $value,
            DateTime::class => new DateTime($value, $this->time_zone),
            DateTimeImmutable::class => new DateTimeImmutable($value, $this->time_zone),
            default => throw new Exception('Unsupported type: ' . $type),
        };
    }

    private function classToDbValue($value, string $type): ?string
    {
        return match ($this->handleNullable($type, $value)) {
            null => null,
            'string', 'int', 'bool', 'float', => (string) $value,
            DateTime::class, DateTimeImmutable::class => $value->setTimezone($this->time_zone)->format('Y-m-d H:i:s'),
            default => throw new Exception('Unsupported type: ' . $type),
        };
    }

    /**
     * @param A $model
     */
    private function updateSequence(object $model): void
    {
        $fields = array_filter($this->model['properties'], fn(array $field) => $field['sequence']);
        if ($fields === []) {
            return;
        }

        $property = key($fields);
        $field = current($fields);
        $table = $this->table();
        $db = $this->db;
        $convert = fn(int $val) => $this->dbToClassValue((string) $val, $field['class_type']);

        (function () use ($db, $property, $table, $convert): void {
            if (empty($this->$property)) {
                $this->$property = $convert($db->nextId($table));
            }
        })->bindTo($model, $model)();
    }

    private function handleNullable(string $type, $value): ?string
    {
        if ($type[0] === '?') {
            if ($value === null) {
                return null;
            }
            return substr($type, 1);
        }
        return $type;
    }

    /**
     * @param array<string, 'asc'|'desc'> $order
     */
    private function order(array $order): string
    {
        if ([] !== array_filter($order, fn($dir) => !in_array(strtolower($dir), ['asc', 'desc'], true))) {
            throw new Exception('Invalid order key given. Only ASC and DESC are allowed');
        }

        $parts = [];
        foreach ($order as $key => $dir) {
            $parts[] = $this->db->quoteIdentifier($key) . ' ' . $dir;
        }

        return join(', ', $parts);
    }

    public function dehydratedInstance(mixed $key_value): ?object
    {
        if (count($keys = $this->keyFields()) > 1) {
            throw new Exception('DatabaseRepository Hydration does not support multiple key fields.');
        }
        $key = array_pop($keys)['db_name'];
        if ($key_value === null) {
            return null;
        }

        return $this->dehydrated[$key_value] ??= $this->fromRow([$key => $key_value]);
    }

    public function hydrate(): void
    {
        if (count($keys = $this->keyFields()) > 1) {
            throw new Exception('DatabaseRepository Hydration does not support multiple key fields.');
        }
        $key = array_pop($keys)['db_name'];

        if (count($this->dehydrated) <= 0) {
            return;
        }

        $query = 'SELECT * FROM ' . $this->db->quoteIdentifier($this->table()) . ' WHERE '
            . $this->where([$key => array_keys($this->dehydrated)]);

        $raw = $this->queryAllRaw($query);

        foreach ($raw as $row) {
            $object = $this->dehydrated[$row[$key]];
            $this->fromRow($row, $object);
            unset($this->dehydrated[$row[$key]]);
        }
    }
}
