<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\File;

use Edutiek\AssessmentService\System\File\Storage;
use Edutiek\AssessmentService\System\Data\FileInfo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo as FileInfoModel;
use ILIAS\ResourceStorage\Manager\Manager;
use ILIAS\ResourceStorage\Consumer\Consumers;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\ResourceStorage\Resource\InfoResolver\StreamInfoResolver;
use ILIAS\ResourceStorage\Resource\ResourceBuilder;

/**
 * Adapter of the ILIAs resource storage (IRSS) service for the assessment-service
 * Hides the complexity of the ILIAS IRSS
 */
readonly class StorageAdapter implements Storage
{
    public function __construct(
        private Manager $manager,
        private Consumers $consumers,
        private ResourceBuilder $resource_builder,
        private Stakeholder $stakeholder
    ) {
    }

    public function getFileInfo(string $id): ?FileInfo
    {
        $resource_id = $this->manager->find($id);
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

    public function getFileStream(string $id): mixed
    {
        $resource_id = $this->manager->find($id);
        if ($resource_id !== null) {
            $resource = $this->manager->getResource($resource_id);
            return $this->consumers->stream($resource_id)->getStream()->detach();
        }
        return null;
    }

    public function saveFile(mixed $stream, ?FileInfo $info): ?FileInfo
    {
        $stream_object = Streams::ofResource($stream);
        $info = $info ?? new FileInfoModel();

        $resource_id = $this->manager->find($info->getId() ?? '');
        if ($resource_id === null) {
            $info_resolver = new WrappedInfoResolver($info, new StreamInfoResolver(
                $stream_object,
                1,
                $this->stakeholder->getOwnerOfNewResources(),
                '',
                null
            ));

            $resource = $this->resource_builder->newFromStream(
                $stream,
                $info_resolver
            );

            $resource->addStakeholder($this->stakeholder);
            $this->resource_builder->store($resource);
            $resource_id = $resource->getIdentification();
        } else {
            $resource = $this->manager->getResource($resource_id);

            $info_resolver = new WrappedInfoResolver($info, new StreamInfoResolver(
                $stream_object,
                $resource->getMaxRevision(true) + 1,
                $this->stakeholder->getOwnerOfNewResources(),
                $resource->getCurrentRevision()->getTitle(),
                null
            ));

            $this->resource_builder->replaceWithStream(
                $resource,
                $stream,
                $info_resolver
            );

            $resource->addStakeholder($this->stakeholder);
            $this->resource_builder->store($resource);
        }

        return $this->getFileInfo((string) $resource_id);
    }

    public function deleteFile(string $id): void
    {
        $resource_id = $this->manager->find($id);
        if ($resource_id !== null) {
            $this->manager->remove($resource_id, $this->stakeholder);
        }
    }
}
