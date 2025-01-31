<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use Edutiek\AssessmentService\System\Data\Config;
use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\CacheRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\DatabaseRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ILIAS\Plugin\LongEssayAssessment\System\Data\Config as ConfigModel;

readonly class ConfigRepo implements \Edutiek\AssessmentService\System\Data\ConfigRepo
{

    public function __construct(
        private RepositoryInterface $repo
    ) {
    }

    public function get(): Config
    {
        foreach ($this->repo->all() as $config) {
            return $config;
        }
        return new ConfigModel();
    }

    public function save(Config $config): void
    {
        $this->repo->replace($config);
    }

}
