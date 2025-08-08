<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

class FileInfo extends \Edutiek\AssessmentService\System\Data\FileInfo
{
    private ?string $id = null;
    private ?string $mime;
    private ?string $name = null;
    private ?int $size;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): FileInfo
    {
        $this->id = $id;
        return $this;
    }
    public function getFileName(): ?string
    {
        return $this->name;
    }

    public function setFileName(?string $name): FileInfo
    {
        $this->name = $name;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mime;
    }

    public function setMimeType(?string $mime): FileInfo
    {
        $this->mime = $mime;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): FileInfo
    {
        $this->size = $size;
        return $this;
    }
}
