<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Common\Http;

use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use ILIAS\Refinery\Factory as Refinery;

class RequestVariables
{
    private ArrayBasedRequestWrapper $wrapper;
    private Refinery $refinery;

    public function __construct(ArrayBasedRequestWrapper $wrapper, Refinery $refinery)
    {
        $this->wrapper = $wrapper;
        $this->refinery = $refinery;
    }

    public function has(string $key): bool
    {
        return $this->wrapper->has($key);
    }

    public function bool(string $key, ?string $default = null): ?bool
    {
        if ($this->wrapper->has($key)) {
            return $this->wrapper->retrieve($key, $this->refinery->kindlyTo()->bool());
        }
        return $default;
    }

    public function integer(string $key, ?int $default = null): ?int
    {
        if ($this->wrapper->has($key)) {
            return $this->wrapper->retrieve($key, $this->refinery->kindlyTo()->int());
        }
        return $default;
    }

    public function float(string $key, ?string $default = null): ?float
    {
        if ($this->wrapper->has($key)) {
            return $this->wrapper->retrieve($key, $this->refinery->kindlyTo()->float());
        }
        return $default;
    }

    public function string(string $key, ?string $default = null): ?string
    {
        if ($this->wrapper->has($key)) {
            return $this->wrapper->retrieve($key, $this->refinery->kindlyTo()->string());
        }
        return $default;
    }
}
