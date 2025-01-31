<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

class Properties extends \Edutiek\AssessmentService\Assessment\Data\Properties
{
    private string $title;
    private string $description;

    public function __construct(private readonly int $ass_id)
    {
    }

    public function getAssId(): int
    {
        return $this->ass_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Properties
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Properties
    {
        $this->description = $description;
        return $this;
    }
}
