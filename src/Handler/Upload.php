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

use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\FileUpload\Handler\FileInfoResult;
use ILIAS\FileUpload\Handler\BasicFileInfoResult;
use Closure;
use ILIAS\ResourceStorage\Services as ILIASResourceStorage;
use Edutiek\AssessmentService\Task\Api\ForClients as TaskApi;

class Upload implements UploadHandler
{
    /**
     * @param Closure(string): string $link
     */
    public function __construct(
        private readonly ILIASResourceStorage $storage,
        private readonly TaskApi $task_api,
        private readonly Closure $link,
    ) {
    }

    public function getFileIdentifierParameterName(): string
    {
        return UploadHandler::DEFAULT_FILE_ID_PARAMETER;
    }

    public function getUploadURL(): string
    {
        return $this->to('upload');
    }

    public function getFileRemovalURL(): string
    {
        return $this->to('rm');
    }

    public function getExistingFileInfoURL(): string
    {
        return $this->to('info');
    }

    public function getInfoForExistingFiles(array $file_ids): array
    {
        return [];
    }

    public function getInfoResult(string $identifier): ?FileInfoResult
    {
        [$task_id, $resource_id] = array_map('intval', explode(':', $identifier));
        $resource = $this->task_api->resource($task_id)->one($resource_id);
        $ili_resource_id = $this->storage->manage()->find($resource->getFileId());
        if($ili_resource_id === null) {
            throw new \ilException('resource id not found');
        }

        $ili_res = $this->storage->manage()->getResource($ili_resource_id);

        return new BasicFileInfoResult(
            $identifier,
            $identifier,
            $resource->getTitle(),
            $ili_res->getFullSize(),
            $ili_res->getCurrentRevision()->getInformation()->getMimeType()
        );
    }

    public function supportsChunkedUploads(): bool
    {
        return false;
    }

    private function to(string $cmd): string
    {
        return ($this->link)($cmd);
    }
}
