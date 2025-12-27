<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\Log;

use Edutiek\AssessmentService\System\Log\FullService;
use ilComponentLogger;

readonly class LogAdapter implements FullService
{
    public function __construct(
        private ilComponentLogger $logger
    ) {
    }

    public function error(string $message): void
    {
        $this->logger->error($message);
    }

    public function info(string $message): void
    {
        $this->logger->info($message);
    }

    public function debug(string $message): void
    {
        $this->logger->debug($message);
    }
}
