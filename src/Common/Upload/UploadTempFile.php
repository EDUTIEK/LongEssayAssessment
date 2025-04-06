<?php

namespace ILIAS\Plugin\LongEssayAssessment;

use ILIAS\Data\DataSize;
use ILIAS\Data\UUID\Factory as UUIDFactory;
use ILIAS\Filesystem\Filesystems;
use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Location;
use ilSession;

class UploadTempFile
{
    private const SESSION_PREFIX = 'XLAS_TEMP_FILE_';
    private UUIDFactory $uuid_factory;

    public function __construct(
        private Filesystems $filesystems,
        private FileUpload $upload
    ) {
        $this->upload = $upload;
        $this->filesystems = $filesystems;
        $this->uuid_factory = new UUIDFactory();
    }

    public function store(UploadResult $result): string
    {
        $identifier = $this->uuid_factory->uuid4AsString();
        $this->upload->moveOneFileTo($result, "", Location::TEMPORARY, $identifier);
        ilSession::set(self::SESSION_PREFIX . $identifier, $result->getName());
        return $identifier;
    }

    public function delete(string $identifier)
    {
        if ($this->has($identifier)) {
            $this->filesystems->temp()->delete($identifier);
            ilSession::clear(self::SESSION_PREFIX . $identifier);
        }
    }

    public function getName(string $identifier): ?string
    {
        if ($this->has($identifier)) {
            return ilSession::get(self::SESSION_PREFIX . $identifier) ?? null;
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
            return $this->filesystems->temp()->getSize($identifier, DataSize::Byte)->getSize();
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
