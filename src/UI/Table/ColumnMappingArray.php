<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

abstract class ColumnMappingArray implements \ArrayAccess
{
    /**
     * return match($key) mapping to resolve the key to its output
     *
     * @param string $key
     * @return mixed
     */
    abstract public function map(string $key) : mixed;

    public function keys(): ?array
    {
        return null;
    }

    public function offsetExists(mixed $offset): bool
    {
        $keys = $this->keys();
        return $keys !== null ? in_array($offset, $keys) : true;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->map($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException("Cannot set a value to an ColumnMappingArray.");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException("Cannot unset a value to an ColumnMappingArray.");
    }
}
