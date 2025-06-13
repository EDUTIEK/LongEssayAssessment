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

namespace ILIAS\Plugin\LongEssayAssessment\UI\Viewer;

use ILIAS\UI\Implementation\Component\ComponentHelper;
use ILIAS\UI\Component\Component;
use Exception;

abstract class Media implements Component
{
    use ComponentHelper;

    /**
     * @return string[]
     */
    public static abstract function supportedMimeTypes(): array;

    public function __construct(private string $url, private string $mime_type, private ?string $caption = null)
    {
        if (!in_array($mime_type, static::supportedMimeTypes())) {
            throw new Exception('Unsupported mime ' . $mime_type . ' type for ' . static::class . '.');
        }
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }
}
