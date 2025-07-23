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
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;
use Edutiek\AssessmentService\Assessment\LogEntry\Category as LogEntryCategory;

#[Table(name: 'xlas_as_log_entry')]
class LogEntry extends \Edutiek\AssessmentService\Assessment\Data\LogEntry
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private ?DateTimeImmutable $timestamp = null;
    private string $category = '';
    private ?string $entry = null;
    private int $ass_id = 0;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getTimestamp(): ?DateTimeImmutable
    {
        return $this->timestamp;
    }
    public function setTimestamp(?DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }
    public function getCategory(): LogEntryCategory
    {
        return LogEntryCategory::from($this->category);
    }
    public function setCategory(LogEntryCategory $category): self
    {
        $this->category = $category->value;
        return $this;
    }
    public function getEntry(): ?string
    {
        return $this->entry;
    }
    public function setEntry(?string $entry): self
    {
        $this->entry = $entry;
        return $this;
    }
    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }
}
