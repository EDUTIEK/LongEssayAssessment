<?php

declare(strict_types = 1);

namespace ILIAS\Plugin\LongEssayAssessment;

/**
 * Basic data of the ILIAS object for the GUI classes
 * Should only have the necessary methods
 */
interface BaseObjectData
{
    public function getId(): int;
    public function getRefId(): int;

    public function getAssId(): int;
    public function getContextId(): int;

    public function getTitle(): string;
    public function getDescription(): string;

    public function getMultiTasks(): bool;
}