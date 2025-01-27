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

class Setup extends \Edutiek\AssessmentService\System\Data\Setup
{
    public function __construct(
        private readonly string $system_name,
        private readonly string $frontends_base_url,
        private readonly string $backend_url,
        private readonly string $default_path_to_ghostscript,
        private readonly string $absolute_temp_path,
        private readonly string $relative_temp_path,
        private readonly string $default_language,
        private readonly DateTimeZone $default_timezone
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

    public function getRelativeTempPath(): string
    {
        return $this->relative_temp_path;
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
}
