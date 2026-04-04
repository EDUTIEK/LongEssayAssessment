<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ilCronManager;
use ilIniFile;
use ilLongEssayAssessmentPlugin;
use ilLanguage;
use DateTimeZone;
use Edutiek\AssessmentService\System\Config\CronJobId;

class SetupRepo implements \Edutiek\AssessmentService\System\Data\SetupRepo
{
    private const TEMP_DIR = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
    private const ARTI_DIR = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/xlas';

    private ?Setup $setup = null;

    public function __construct(
        private readonly ilIniFile $client_ini,
        private readonly ilLanguage $lng,
        private readonly ilCronManager $cron,
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

            $this->setup = new Setup(
                $this->client_ini->readVariable('client', 'name'),
                ILIAS_HTTP_PATH
                    . '/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment'
                    . '/vendor/edutiek/assessment-service/node_modules',
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
