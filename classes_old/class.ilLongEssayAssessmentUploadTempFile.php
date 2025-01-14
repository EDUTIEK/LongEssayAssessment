<?php

namespace ILIAS\Plugin\LongEssayAssessment;

use ILIAS\Data\DataSize;
use ILIAS\Data\UUID\Factory as UUIDFactory;
use ILIAS\Filesystem\Filesystems;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Location;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\ResourceStorage\Revision\Revision;
use ILIAS\ResourceStorage\Services;
use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;

class ilLongEssayAssessmentUploadTempFile
{
    private UUIDFactory $uuid_factory;
    private Services $storage;
    private Filesystems $filesystems;
    private FileUpload $upload;

    /**
     * ilUIDemoFileUploadHandlerGUI constructor.
     */
    public function __construct(Services $storage, Filesystems $filesystems, FileUpload $upload)
    {
        $this->storage = $storage;
        $this->upload = $upload;
        $this->filesystems = $filesystems;
        $this->uuid_factory = new UUIDFactory();
    }

    private function tempFileUUID(): string
    {
        return $this->uuid_factory->uuid4AsString();
    }

    public function storeTempFile(UploadResult $result): string
    {
        $identifier = $this->tempFileUUID();
        $this->upload->moveOneFileTo($result, "", Location::TEMPORARY, $identifier);
        $_SESSION["XLAS_TEMP_FILES"][$identifier] = ["fn" => $result->getName(), "mime" => $result->getMimeType()];
        return $identifier;
    }

    public function removeTempFile(string $identifier)
    {
        if($this->isTempFile($identifier)) {
            $this->filesystems->temp()->delete($identifier);
            unset($_SESSION["XLAS_TEMP_FILES"][$identifier]);
        }
    }

    public function getTempFilename(string $identifier): ?string
    {
        return $this->isTempFile($identifier) ? $_SESSION["XLAS_TEMP_FILES"][$identifier]["fn"] : null;
    }

    public function getTempMimeType(string $identifier): ?string
    {
        return $this->isTempFile($identifier) ? $_SESSION["XLAS_TEMP_FILES"][$identifier]["mime"] : null;
    }

    public function getTempFileSize(string $identifier): ?int
    {
        return $this->isTempFile($identifier) ? $this->filesystems->temp()->getSize($identifier, DataSize::Byte)->getSize() : null;
    }

    public function isTempFile(string $identifier): bool
    {
        return isset($_SESSION["XLAS_TEMP_FILES"]) && isset($_SESSION["XLAS_TEMP_FILES"][$identifier]);
    }

    public function storeTempFileInResources(string $identifier, AbstractResourceStakeholder $stakeholder): ?ResourceIdentification
    {
        if($this->isTempFile($identifier)) {
            $stream = $this->filesystems->temp()->readStream($identifier);
            $title = $this->getTempFilename($identifier);
            $id = $this->storage->manage()->stream($stream, $stakeholder, $title);
            return $id;
        }
        return null;
    }

    public function replaceTempFileWithResource(string $identifier, ResourceIdentification $resource_identification, AbstractResourceStakeholder $stakeholder): ?Revision
    {
        if($this->isTempFile($identifier)) {
            $stream = $this->filesystems->temp()->readStream($identifier);
            $title = $this->getTempFilename($identifier);
            $id = $this->storage->manage()->replaceWithStream($resource_identification, $stream, $stakeholder, $title);

            $this->removeTempFile($identifier);
            return $id;
        }
        return null;
    }

    public function addTempFileToResourceRevision(string $identifier, ResourceIdentification $resource_identification, AbstractResourceStakeholder $stakeholder): ?Revision
    {
        if($this->isTempFile($identifier)) {
            $stream = $this->filesystems->temp()->readStream($identifier);
            $title = $this->getTempFilename($identifier);
            $id = $this->storage->manage()->appendNewRevisionFromStream($resource_identification, $stream, $stakeholder, $title);
            $this->removeTempFile($identifier);
            return $id;
        }
        return null;
    }
}
