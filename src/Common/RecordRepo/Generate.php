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
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Column;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;
use ILIAS\UI\Implementation\Component\Input\Field\DateTime;
use ilDBConstants;
use Exception;
use LogicException;
use ReflectionClass;
use ReflectionProperty;

class Generate
{
    public function __construct(
        private string $artifact_directory
    ) {}

    public function readModelFromClass(string $model): array
    {
        $r = new ReflectionClass($model);
        $table = $this->findName(Table::class, $r->getAttributes());
        if (!$table) {
            throw new LogicException('Missing table name for model: ' . $model);
        }

        $table_name = $table->newInstance()->name();

        $properties = [];
        foreach ($r->getProperties(~ReflectionProperty::IS_STATIC) as $property) {
            $column = $this->findName(Column::class, $property->getAttributes())?->newInstance();
            $sequence = (bool) $this->findName(Sequence::class, $property->getAttributes());
            $properties[$property->getName()] = [
                'db_name' => $column?->name() ?? $property->getName(),
                'db_type' => $column?->type() ?? $this->inferDBType((string) $property->getType()),
                'class_name' => $property->getName(),
                'class_type' => (string) $property->getType() ?: null,
                'sequence' => $sequence,
                'key' => $sequence || $this->findName(Key::class, $property->getAttributes()),
            ];
        }

        return [
            'class' => $model,
            'db_name' => $table_name,
            'properties' => $properties,
        ];
    }

    public function writeModelToFile(string $model): void
    {
        $this->writeArtifact($model, $this->readModelFromClass($model));
    }

    public function readModelFromFile(string $model): ?array
    {
        return $this->readArtifact($model);
    }

    public function readModel(string $model): array
    {
        $data = $this->readModelFromFile($model);
        if ($data) {
            return $data;
        }

        $data = $this->readModelFromClass($model);
        $this->writeArtifact($model, $data);

        return $data;
    }

    public function writeArtifact(string $key, $content): void
    {
        if (!is_dir($this->artifact_directory)) {
            mkdir($this->artifact_directory);
        }

        file_put_contents($this->artifactPath($key), '<?php return ' . var_export($content, true) . ';');
    }

    public function readArtifact(string $key)
    {
        $path = $this->artifactPath($key);
        if (!file_exists($path)) {
            return null;
        }

        return require $path;
    }

    public function artifactPath(string $key): string
    {
        return $this->artifact_directory . '/' . md5($key) . '.php';
    }

    public function inferDBType(string $php_type): string
    {
        return match ($php_type) {
            'string', '?string' => ilDBConstants::T_TEXT,
            'float', '?float' => ilDBConstants::T_FLOAT,
            'int', '?int' => ilDBConstants::T_INTEGER,
            'bool', '?bool' => ilDBConstants::T_INTEGER,
            DateTime::class, DateTimeImmutable::class => ilDBConstants::T_TIMESTAMP,
            default => throw new Exception('Cannot infer db type for: ' . var_export($php_type, true)),
        };
    }

    private function findName(string $name, array $array)
    {
        return $this->find(fn($a) => $a->getName() === $name, $array);
    }

    private function find(callable $predicate, array $array)
    {
        foreach ($array as $value) {
            if ($predicate($value)) {
                return $value;
            }
        }

        return null;
    }
}
