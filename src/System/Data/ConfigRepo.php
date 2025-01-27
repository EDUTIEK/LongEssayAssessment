<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use Edutiek\AssessmentService\System\Data\Config;
use Edutiek\AssessmentService\System\Data\Setup;
use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\CacheRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\DatabaseRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ILIAS\Plugin\LongEssayAssessment\System\Data\Config as ConfigModel;
use ILIAS\Plugin\LongEssayAssessment\System\Data\Setup as SetupModel;
use ilIniFile;
use ilLongEssayAssessmentPlugin;
use ilLanguage;
use DateTimeZone;

class ConfigRepo implements \Edutiek\AssessmentService\System\Data\ConfigRepo
{
    private RepositoryInterface $config_repo;
    private SetupModel $setup;

    public function __construct(
        ilDBInterface $db,
        Generate $g,
        private readonly ilIniFile $client_ini,
        private readonly ilLongEssayAssessmentPlugin $plugin,
        private readonly \ILIAS\Filesystem\Filesystem $web_fs,
        private readonly ilLanguage $lng
    ) {
        $this->config_repo = new CacheRepository(new DatabaseRepository($db, $g->readModel(ConfigModel::class)));
    }

    public function getConfig(): Config
    {
        foreach ($this->config_repo->all() as $config) {
            return $config;
        }
        return new ConfigModel();
    }

    public function saveConfig(Config $config): void
    {
        $this->config_repo->replace($config);
    }

    public function getSetup(): Setup
    {
        if ($this->setup === null) {
            if ($this->web_fs->hasDir('temp')) {
                $this->web_fs->createDir('temp');
            }
            $this->setup = new SetupModel(
                $this->client_ini->readVariable('client', 'name'),
                $this->getPluginHttpPath() . '/vendor/edutiek/assessment-service/node_modules',
                $this->getPluginHttpPath() . '/rest.php',
                $this->getDefaultPathToGhostscript(),
                ILIAS_ABSOLUTE_PATH . '/' . ILIAS_WEB_DIR . '/' . CLIENT_ID . '/temp',
                ILIAS_WEB_DIR . '/' . CLIENT_ID . '/temp',
                $this->lng->getDefaultLanguage(),
                new DateTimeZone(date_default_timezone_get())
            );
        }

        return $this->setup;
    }

    /**
     * Get the HTTP path to the plugin directory
     * Helper function to handle a different ILIAS_HTTP_PATH
     * when being called from ilias.php or from the rest end points
     */
    private function getPluginHttpPath(): string
    {
        $plugin_path = $this->plugin->getPluginPath();
        $pos = strpos(ILIAS_HTTP_PATH, $plugin_path);

        if ($pos !== false) {
            return substr(ILIAS_HTTP_PATH, 0, $pos + strlen($plugin_path));
        } else {
            return ILIAS_HTTP_PATH . '/' . $plugin_path;
        }
    }

    /**
     * Get the default path of the ghostscript executable
     * This is taken, if Config::getPathToGhostscript is not set
     */
    private function getDefaultPathToGhostscript(): ?string
    {
        if (defined('PATH_TO_GHOSTSCRIPT') && !empty(PATH_TO_GHOSTSCRIPT)) {
            $path = PATH_TO_GHOSTSCRIPT;
        } else {
            $path = '/usr/bin/gs';
        }
        return (is_executable($path) ? $path : null);
    }
}
