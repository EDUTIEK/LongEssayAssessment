<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Log;

use ilComponentLogger;
use Psr\Log\LoggerInterface;

readonly class LogAdapter implements LoggerInterface
{
    public function __construct(
        private ilComponentLogger $log
    ) {
    }

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->log->emergency((string) $message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->log->alert((string) $message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->log->critical((string) $message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->log->error((string) $message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->log->warning((string) $message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->log->notice((string) $message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->log->info((string) $message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->log->debug((string) $message, $context);
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->info((string) $message, $context);
    }
}
