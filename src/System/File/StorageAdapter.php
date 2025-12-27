<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\File;

use Edutiek\AssessmentService\System\Data\FileInfo;
use Edutiek\AssessmentService\System\File\Storage;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo as FileInfoModel;
use ILIAS\ResourceStorage\Consumer\Consumers;
use ILIAS\ResourceStorage\Manager\Manager;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;
use Psr\Http\Message\StreamInterface as Stream;

/**
 * Adapter of the ILIAS resource storage (IRSS) service for the assessment-service
 * Hides the complexity of the ILIAS IRSS
 */
readonly class StorageAdapter implements Storage
{
    public function __construct(
        private Manager $manager,
        private Consumers $consumers,
        private ResourceStakeholder $stakeholder
    ) {
    }

    public function hasFile(?string $id): bool
    {
        $resource_id = $this->manager->find($id ?? '');
        return $resource_id !== null;
    }

    public function getFileInfo(?string $id): ?FileInfo
    {
        $resource_id = $this->manager->find($id ?? '');
        if ($resource_id !== null) {
            $resource = $this->manager->getResource($resource_id);

            return (new FileInfoModel())
                ->setId($id)
                ->setFileName($resource->getCurrentRevision()->getTitle())
                ->setMimeType($resource->getCurrentRevision()->getInformation()->getMimeType())
                ->setSize($resource->getCurrentRevision()->getInformation()->getSize());
        }
        return null;
    }

    public function getFileStream(?string $id): mixed
    {
        $resource_id = $this->manager->find($id ?? '');
        if ($resource_id !== null) {
            return $this->consumers->stream($resource_id)->getStream()->detach();
        }
        return null;
    }

    public function saveFile(mixed $input, ?FileInfo $info = null): ?FileInfo
    {
        if ($input instanceof Stream) {
            $stream_object = Streams::ofPsr7Stream($input);
        } elseif (is_string($input)) {
            $stream_object = Streams::ofString($input);
        } elseif (is_resource($input)) {
            $stream_object = Streams::ofResource($input);
        }

        $info = $info ?? new FileInfoModel();

        $resource_id = $this->manager->find($info->getId() ?? '');
        if ($resource_id === null) {
            $resource_id = $this->manager->stream(
                $stream_object,
                $this->stakeholder,
                $info->getFileName() ?? ''
            );
        } else {
            $this->manager->replaceWithStream(
                $resource_id,
                $stream_object,
                $this->stakeholder,
                $info->getFileName()
            );
        }

        return $this->getFileInfo((string) $resource_id);
    }

    public function deleteFile(?string $id): void
    {
        if (!$id) {
            return;
        }
        $resource_id = $this->manager->find($id ?? '');
        if ($resource_id !== null) {
            $this->manager->remove($resource_id, $this->stakeholder);
        }
    }

    public function getReadablePath(?string $id): ?string
    {
        if (!$id) {
            return null;
        }

        try {
            $resource_id = $this->manager->find($id);
            return $this->consumers->stream($resource_id)->getStream()->getMetadata('uri');
        } catch (\Exception $e) {
            return null;
        }
    }
}
