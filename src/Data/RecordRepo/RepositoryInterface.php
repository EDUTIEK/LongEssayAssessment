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

namespace ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo;

/**
 * @template A of object
 */
interface  RepositoryInterface
{
    /**
     * @return A[]
     */
    public function all(): array;

    /**
     * @return A[]
     */
    public function queryAll(string $query): array;

    /**
     * @return ?A
     */
    public function queryOne(string $query): ?object;

    /**
     * @param A $model
     */
    public function insert(object $model): void;

    /**
     * @param A $model
     */
    public function replace(object $model): void;

    /**
     * @param A $model
     */
    public function update(object $model): void;

    /**
     * @param A $model
     */
    public function delete(object $model): void;

    /**
     * @return int[]
     */
    public function queryIntegers(string $query, string $key): array;

    /**
     * @param array<int|string, string> $row
     * @return A
     */
    public function fromRow(array $row): object;

    /**
     * @param A $model
     * @return array<int|string, array{0: string, 1: string}>
     */
    public function toRowWithTypes(object $model): array;

    /**
     * @param A $model
     * @return array<int|string, string>
     */
    public function toRow(object $model): array;
    public function table(): string;

    /**
     * @return array{db_name: string, class_name: string, db_type: string, class_type: string, sequence: bool, key: true}[]
     */
    public function keyFields(): array;
}
