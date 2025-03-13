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

use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Column;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;
use ILIAS\UI\Implementation\Component\Input\Field\DateTime;
use ilDBConstants;
use Exception;
use LogicException;
use ReflectionClass;
use ReflectionProperty;

class Generate
{
    private ?array $models = null;

    public function __construct(
        private readonly string $artifact_file
    ) {
    }

    public function readModel(string $model): array
    {
        $this->models ??= require $this->artifact_file;
        $this->models[$model] ??= static::readModelFromClass($model);
        return $this->models[$model];
    }

    public static function readModelFromClass(string $model): array
    {
        $r = new ReflectionClass($model);
        $table = static::findName(Table::class, $r->getAttributes());
        if (!$table) {
            throw new LogicException('Missing table name for model: ' . $model);
        }

        $table_name = $table->newInstance()->name();

        $properties = [];
        foreach ($r->getProperties(~ReflectionProperty::IS_STATIC) as $property) {
            $column = static::findName(Column::class, $property->getAttributes())?->newInstance();
            $sequence = (bool) static::findName(Sequence::class, $property->getAttributes());
            $properties[$property->getName()] = [
                'db_name' => $column?->name() ?? $property->getName(),
                'db_type' => $column?->type() ?? static::inferDBType((string) $property->getType()),
                'class_name' => $property->getName(),
                'class_type' => (string) $property->getType() ?: null,
                'sequence' => $sequence,
                'key' => $sequence || static::findName(Key::class, $property->getAttributes()),
            ];
        }

        return [
            'class' => $model,
            'db_name' => $table_name,
            'properties' => $properties,
        ];
    }

    public static function inferDBType(string $php_type): string
    {
        $php_type = $php_type[0] === '?' ? substr($php_type, 1) : $php_type;
        return match ($php_type) {
            'string' => ilDBConstants::T_TEXT,
            'float' => ilDBConstants::T_FLOAT,
            'int' => ilDBConstants::T_INTEGER,
            'bool' => ilDBConstants::T_INTEGER,
            DateTime::class, DateTimeImmutable::class => ilDBConstants::T_TIMESTAMP,
            default => throw new Exception('Cannot infer db type for: ' . var_export($php_type, true)),
        };
    }

    public static function findName(string $name, array $array)
    {
        return static::find(fn($a) => $a->getName() === $name, $array);
    }

    private static function find(callable $predicate, array $array)
    {
        foreach ($array as $value) {
            if ($predicate($value)) {
                return $value;
            }
        }

        return null;
    }
}
