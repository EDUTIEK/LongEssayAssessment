<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Protocol;

interface Item
{
    public function sortBy() : \DateTimeImmutable;
    public function type() : EntryType;
}