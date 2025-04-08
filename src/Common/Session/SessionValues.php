<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Common\Session;

use ilSession;

/**
 * Session storage for individual classes, assessments or tasks
 */
class SessionValues
{
    public function __construct(
        private readonly string $class,
        private readonly?int $ass_id = null,
        private ?int $task_id = null
    ) {
    }

    public function has(string $key)
    {
        return ilSession::has($this->key($key));
    }

    public function get(string $key)
    {
        return ilSession::get($this->key($key));
    }

    public function set(string $key, mixed $value)
    {
        ilSession::set($this->key($key), $value);
    }

    public function unset(string $key)
    {
        ilSession::clear($this->key($key));
    }

    private function key(string $key): string
    {
        return serialize([$this->class, $this->ass_id, $this->task_id, $key]);
    }
}
