<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use DateTimeZone;
use Edutiek\AssessmentService\System\Config\CronJobId;

readonly class Setup extends \Edutiek\AssessmentService\System\Data\Setup
{
    public function __construct(
        private string $system_name,
        private string $frontends_base_url,
        private string $backend_url,
        private string $default_path_to_ghostscript,
        private string $absolute_temp_path,
        private string $absolute_artifacts_path,
        private string $default_language,
        private DateTimeZone $default_timezone,
        private array $active_cron_jobs
    ) {
    }

    public function getSystemName(): string
    {
        return $this->system_name;
    }

    public function getFrontendsBaseUrl(): string
    {
        return $this->frontends_base_url;
    }

    public function getBackendUrl(): string
    {
        return $this->backend_url;
    }

    public function getAbsoluteTempPath(): string
    {
        return $this->absolute_temp_path;
    }

    public function getAbsoluteArtifactsPath(): string
    {
        return $this->absolute_artifacts_path;
    }

    public function getDefaultPathToGhostscript(): ?string
    {
        return $this->default_path_to_ghostscript;
    }

    public function getDefaultLanguage(): string
    {
        return $this->default_language;
    }

    public function getDefaultTimezone(): DateTimeZone
    {
        return $this->default_timezone;
    }

    public function isCronJobActive(CronJobId $job_id): bool
    {
        return in_array($job_id, $this->active_cron_jobs);
    }
}
