<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

class ObjectProperties extends \Edutiek\AssessmentService\Assessment\Data\ObjectProperties
{
    private string $title;
    private string $description;

    public function __construct(private readonly int $ass_id)
    {
    }

    public function getAssessmentId(): int
    {
        return $this->ass_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): ObjectProperties
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): ObjectProperties
    {
        $this->description = $description;
        return $this;
    }
}
