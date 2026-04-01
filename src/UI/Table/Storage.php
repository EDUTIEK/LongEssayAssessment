<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ArrayAccess;
use ilObjUser;

readonly class Storage implements ArrayAccess
{
    public function __construct(
        private ilObjUser $user
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->user->getPref($this->key($offset)) !== null;
    }

    public function offsetGet(mixed $offset): mixed
    {
        $value = $this->user->getPref($this->key($offset));
        return $value === null ? null : unserialize($value);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value !== null) {
            $value = serialize($value);
        }
        $this->user->writePref($this->key($offset), $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->user->deletePref($this->key($offset));
    }

    private function key(mixed $offset): string
    {
        // should not exceed 40 characters
        return 'xlas.' . md5((string) $offset);
    }
}
