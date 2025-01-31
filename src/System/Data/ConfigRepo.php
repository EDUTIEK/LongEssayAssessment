<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use Edutiek\AssessmentService\System\Data\Config as ConfigInterface;

readonly class ConfigRepo implements \Edutiek\AssessmentService\System\Data\ConfigRepo
{
    public function __construct(
        private RepositoryInterface $repo
    ) {
    }

    public function one(): Config
    {
        foreach ($this->repo->all() as $config) {
            return $config;
        }
        return new Config();
    }

    public function save(ConfigInterface $config): void
    {
        $this->repo->replace($config);
    }

}
