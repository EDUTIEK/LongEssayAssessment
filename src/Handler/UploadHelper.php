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

namespace ILIAS\Plugin\LongEssayAssessment\Handler;

use ILIAS\DI\Container;
use ILIAS\Filesystem\Stream\Streams;

class UploadHelper
{
    public function __construct(private readonly Container $dic)
    {
    }

    public function exitWithJson($value): never
    {
        // ... The content type cannot be set to application/json, because the components/ILIAS/UI/src/templates/js/Input/Field/file.js:392
        //     does not expect that the content type is correct and parses it again ...
        $this->dic->http()->saveResponse($this->dic->http()->response()/* ->withHeader('Content-Type', 'application/json') */->withBody(
            Streams::ofString(json_encode($value))
        ));

        $this->dic->http()->sendResponse();
        $this->dic->http()->close();
    }

    public function okJson($file_id): array
    {
        return ['status' => 1, 'file_id' => $file_id];
    }

    public function errorJson(string $message): array
    {
        return ['status' => 'error', 'message' => $message];
    }
}
