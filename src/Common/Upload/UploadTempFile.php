<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Common\Upload;

use ILIAS\Data\DataSize;
use ILIAS\Data\UUID\Factory as UUIDFactory;
use ILIAS\Filesystem\Filesystems;
use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Location;
use ILIAS\Plugin\LongEssayAssessment\Common\Session\SessionValues;
use ilSession;

class UploadTempFile
{
    public function __construct(
        private Filesystems $filesystems,
        private FileUpload $upload,
        private SessionValues $session_values,
        private UUIDFactory $uuid_factory
    ) {
    }

    public function store(UploadResult $result): string
    {
        $identifier = $this->uuid_factory->uuid4AsString();
        $this->upload->moveOneFileTo($result, "", Location::TEMPORARY, $identifier);
        $this->session_values->set($identifier, $result->getName());
        return $identifier;
    }

    public function delete(string $identifier)
    {
        if ($this->has($identifier)) {
            $this->filesystems->temp()->delete($identifier);
            $this->session_values->unset($identifier);
        }
    }

    public function getName(string $identifier): ?string
    {
        if ($this->has($identifier)) {
            return $this->session_values->get($identifier);
        }
        return null;
    }

    public function getMimeType(string $identifier): ?string
    {
        if ($this->has($identifier)) {
            return $this->filesystems->temp()->getMimeType($identifier);
        }
        return null;
    }

    public function getSize(string $identifier): ?int
    {
        if ($this->has($identifier)) {
            return (int) $this->filesystems->temp()->getSize($identifier, DataSize::Byte)->getSize();
        }
        return null;
    }

    public function has(string $identifier): bool
    {
        return $this->filesystems->temp()->has($identifier);
    }

    public function stream(string $identifier): ?FileStream
    {
        if ($this->has($identifier)) {
            return $this->filesystems->temp()->readStream($identifier);
        }
        return null;
    }
}
