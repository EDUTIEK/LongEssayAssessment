<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\LiveStatusPanel;

class Property
{
    public function __construct(
        private string $id,
        private string $title,
        private int $initial_value,
        private ?string $filter_url = null,
    ){
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }


    public function getFilterUrl(): ?string
    {
        return $this->filter_url;
    }

    public function getInitialValue(): int
    {
        return $this->initial_value;
    }
}