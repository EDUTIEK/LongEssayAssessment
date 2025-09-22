<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ilIniFile;
use ilLongEssayAssessmentPlugin;
use ilLanguage;
use DateTimeZone;

class SetupRepo implements \Edutiek\AssessmentService\System\Data\SetupRepo
{
    private ?Setup $setup = null;

    public function __construct(
        private readonly ilIniFile $client_ini,
        private readonly \ILIAS\Filesystem\Filesystem $web_fs,
        private readonly ilLanguage $lng
    ) {
    }

    public function one(): Setup
    {
        if ($this->setup === null) {
            if (!$this->web_fs->hasDir('temp')) {
                $this->web_fs->createDir('temp');
            }
            $this->setup = new Setup(
                $this->client_ini->readVariable('client', 'name'),
                ILIAS_HTTP_PATH
                    . '/Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment'
                    . '/vendor/edutiek/assessment-service/node_modules',
                ILIAS_HTTP_PATH . '/xlas_rest.php',
                $this->getDefaultPathToGhostscript(),
                ILIAS_ABSOLUTE_PATH . '/public/' . ILIAS_WEB_DIR . '/' . CLIENT_ID . '/temp',
                ILIAS_WEB_DIR . '/' . CLIENT_ID . '/temp',
                $this->lng->getDefaultLanguage(),
                new DateTimeZone(date_default_timezone_get())
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
