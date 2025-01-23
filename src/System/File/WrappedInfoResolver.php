<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\File;

use ILIAS\ResourceStorage\Resource\InfoResolver\AbstractInfoResolver;
use DateTimeImmutable;
use ILIAS\ResourceStorage\Resource\InfoResolver\InfoResolver;
use Edutiek\AssessmentService\System\Data\FileInfo;

/**
 * Wrapper of the IRSS stream info resolver
 * Uses the FileInfo object with precedence
 */
class WrappedInfoResolver extends AbstractInfoResolver implements InfoResolver
{
    public function __construct(
        private FileInfo $info,
        private InfoResolver $resolver,
    ) {
    }

    public function getFileName(): string
    {
        $this->info->getFileName() ?? $this->resolver->getFileName();
    }

    public function getMimeType(): string
    {
        return $this->info->getMimeType() ?? $this->resolver->getMimeType();
    }

    public function getSuffix(): string
    {
        return $this->resolver->getSuffix();
    }

    public function getCreationDate(): DateTimeImmutable
    {
        return $this->resolver->getCreationDate();
    }

    public function getSize(): int
    {
        return $this->info->getSize() ?? $this->resolver->getSize();
    }
}
