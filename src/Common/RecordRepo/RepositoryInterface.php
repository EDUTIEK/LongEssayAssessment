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
 */
interface RepositoryInterface
{
    /**
     * Create a new entity with uninitialized properties
     * @return A
     */
    public function new(): ?object;

    /**
     * Get all entities of this repo
     * @return A[]
     */
    public function all(): array;

    /**
     * Get all entities found by an SQL query
     * @return A[]
     */
    public function queryAll(string $query): array;

    /**
     * Query all entities based on an array of conditions
     * @return A[]
     * @see where
     */
    public function queryAllBy(array $conditions): array;

    /**
     * Do a raw database query and return the assoc record arrays
     * @return array[]
     */
    public function queryAllRaw(string $query): array;

    /**
     * Get the first entity found by an SQL query
     * @return ?A
     */
    public function queryOne(string $query): ?object;

    /**
     * Get the first entity found by an array of conditions
     * @return ?A
     * @see where
     */
    public function queryOneBy(array $conditions): ?object;

    /**
     * Insert a new entity
     * It should not yet exist there
     * A sequence key is automatically created
     * @param A $model
     */
    public function insert(object $model): void;

    /**
     * Replace an entity
     * An existing entity is updated
     * A new entity is inserted with automatically created sequence key
     * @param A $model
     */
    public function replace(object $model): void;

    /**
     * Update an existing entity
     * The key of the entity must be set in the model
     * @param A $model
     */
    public function update(object $model): void;

    /**
     * Delete an existing entity
     * @param A $model
     */
    public function delete(object $model): void;

    /**
     * Delete all entities by an array of conditions
     * @param A $model
     * @see where
     */
    public function deleteAllBy(array $conditions): void;

    /**
     * @return int[]
     */
    public function queryIntegers(string $query, string $key): array;

    /**
     * Get a model object with properties set from a database row array
     * @param array<int|string, string> $row
     * @return A
     */
    public function fromRow(array $row): object;

    /**
     * Create a database row array with type information from the model's properties
     * @param A $model
     * @return array<int|string, array{0: string, 1: string}>
     */
    public function toRowWithTypes(object $model): array;

    /**
     * Create a database row array from the model's properties
     * @param A $model
     * @return array<int|string, string>
     */
    public function toRow(object $model): array;

    /**
     * Get the database table name of the model
     */
    public function table(): string;

    /**
     * Get information about the key fields of the database table
     * @return array{db_name: string, class_name: string, db_type: string, class_type: string, sequence: bool, key: true}[]
     */
    public function keyFields(): array;

    /**
     * Build an SQL condition (without WHERE) based on an array of conditions
     *  - Conditions are an array [ (string) property_name => (string|array|null) property_value(s), ...]
     *  - property_name must be the name of a model's property which is mapped to a database field
     *  - property_value must be a scalar value, an array of values or null
     *  - A value is directly compared
     *  - An array is used for an IN clause
     *  - null creates an IS NULL clause
     *  - All given conditions are AND combined
     *  - values are automatically cast and quoted accounting their database field type
     */
    public function where(array $conditions): string;
}
