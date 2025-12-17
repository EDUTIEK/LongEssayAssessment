<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

readonly class Permissions extends \Edutiek\AssessmentService\Assessment\Data\Permissions
{
    public function __construct(
        private int $ass_id,
        private int $context_id,
        private int $user_id,
        private bool $visible,
        private bool $read,
        private bool $maintain_settings,
        private bool $maintain_content,
        private bool $maintain_writing,
        private bool $maintain_correction,
        private bool $edit_templates,
        private bool $proctor_writer
    ) {
    }

    public function getAssId(): int
    {
        return $this->ass_id;
    }

    public function getContextId(): int
    {
        return $this->context_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function getRead(): bool
    {
        return $this->read;
    }

    public function getMaintainSettings(): bool
    {
        return $this->maintain_settings;
    }

    public function getMaintainContent(): bool
    {
        return $this->maintain_content;
    }

    public function getMaintainWriting(): bool
    {
        return $this->maintain_writing;
    }

    public function getMaintainCorrection(): bool
    {
        return $this->maintain_correction;
    }

    public function getEditTemplates(): bool
    {
        return $this->edit_templates;
    }

    public function getProctorWriting(): bool
    {
        return $this->proctor_writer;
    }

}
