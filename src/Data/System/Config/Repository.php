<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\System\Config;

use Edutiek\AssessmentService\System\Config\Config;
use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\CacheRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\DatabaseRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\RepositoryInterface;
use ILIAS\Plugin\LongEssayAssessment\Data\System\Config\Config as Model;


class Repository implements \Edutiek\AssessmentService\System\Config\Repository
{
    private RepositoryInterface $config_repo;

    public function __construct(ilDBInterface $db, Generate $g)
    {
        $this->config_repo = new CacheRepository(new DatabaseRepository($db, $g->readModel(Model::class)));
    }

    public function readConfig(): Config
    {
        foreach ($this->config_repo->all() as $config) {
            return $config;
        }
        return new Model();
    }

    public function writeConfig(Config $config) : void
    {
        $this->config_repo->replace($config);
    }
}