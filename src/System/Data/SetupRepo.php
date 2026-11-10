<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ilIniFile;
use ilLongEssayAssessmentPlugin;
use ilLanguage;
use DateTimeZone;
use Edutiek\AssessmentService\System\Config\CronJobId;
use Edutiek\AssessmentService\System\Config\Frontend;
use ILIAS\Cron\Job\JobManager;

class SetupRepo implements \Edutiek\AssessmentService\System\Data\SetupRepo
{
    private const TEMP_DIR = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
    private const ARTI_DIR = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/xlas';

    private ?Setup $setup = null;

    public function __construct(
        private readonly ilIniFile $client_ini,
        private readonly ilLanguage $lng,
        private readonly JobManager $cron,
    ) {
    }

    public function one(): Setup
    {
        if ($this->setup === null) {
            if (!is_dir(self::TEMP_DIR)) {
                mkdir(self::TEMP_DIR);
            }

            if (!is_dir(self::ARTI_DIR)) {
                mkdir(self::ARTI_DIR);
            }

            $job_ids = [];
            foreach (CronJobId::all() as $job_id) {
                if ($this->cron->isJobActive($job_id->value)) {
                    $job_ids[] = $job_id;
                }
            }

            $apps_config = $this->readAppsConfig();

            $this->setup = new Setup(
                $this->client_ini->readVariable('client', 'name'),
                $this->frontendBaseUrl($apps_config, Frontend::WRITER),
                $this->frontendBaseUrl($apps_config, Frontend::CORRECTOR),
                ILIAS_HTTP_PATH . '/xlas_rest.php',
                $this->getDefaultPathToGhostscript(),
                self::TEMP_DIR,
                self::ARTI_DIR,
                $this->lng->getDefaultLanguage(),
                new DateTimeZone(date_default_timezone_get()),
                $job_ids
            );
        }

        return $this->setup;
    }


    /**
     * Base URL of a published frontend
     * The URL has no trailing slash
     * @param array<string, mixed> $config
     */
    private function frontendBaseUrl(array $config, Frontend $frontend): string
    {
        $public_path = $config[$frontend->value]['publicPath'] ?? null;
        if (!is_string($public_path) || $public_path === '') {
            throw new \RuntimeException(
                "Missing publicPath for '{$frontend->value}' in the LongEssayAssessment apps config."
            );
        }

        return rtrim(ILIAS_HTTP_PATH, '/') . '/' . trim($public_path, '/');
    }

    /**
     * Prefer a local apps.config.json over apps.config.default.json
     * @return array<string, mixed>
     */
    private function readAppsConfig(): array
    {
        $plugin = dirname(__DIR__, 3);
        $local = $plugin . '/apps.config.json';
        $default = $plugin . '/apps.config.default.json';
        $path = is_file($local) ? $local : $default;

        if (!is_file($path)) {
            throw new \RuntimeException('LongEssayAssessment apps config was not found.');
        }

        $config = json_decode((string) file_get_contents($path), true);
        if (!is_array($config)) {
            throw new \RuntimeException("LongEssayAssessment apps config '$path' is not valid JSON.");
        }

        return $config;
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
