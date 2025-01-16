<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_log_entry')]
class LogEntry extends \Edutiek\AssessmentService\Assessment\Data\LogEntry
{
    #[Key]
    private int $id = 0;
    private ?DateTimeImmutable $timestamp = null;
    private string $category = '';
    private ?string $entry = null;
    private int $ass_id = 0;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getTimestamp(): ?DateTimeImmutable
    {
        return $this->timestamp;
    }
    public function setTimestamp(?DateTimeImmutable $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
    public function getCategory(): string
    {
        return $this->category;
    }
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }
    public function getEntry(): ?string
    {
        return $this->entry;
    }
    public function setEntry(?string $entry): void
    {
        $this->entry = $entry;
    }
    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): void
    {
        $this->ass_id = $ass_id;
    }
}
