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

namespace ILIAS\Plugin\LongEssayAssessment\Data\EssayTask\Data;

use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_essay')]
class Essay
{
    #[Key]
    private int $id;
    private string $uuid;
    private int $writer_id;
    private ?string $written_text;
    private string $raw_text_hash;
    private ?string $pdf_version;
    private int $task_id;
    private ?DateTimeImmutable $last_change;
    private int $service_version;
    private ?DateTimeImmutable $first_change;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getUuid(): string
    {
        return $this->uuid;
    }
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }
    public function getWriterId(): int
    {
        return $this->writer_id;
    }
    public function setWriterId(int $writer_id): void
    {
        $this->writer_id = $writer_id;
    }
    public function getWrittenText(): ?string
    {
        return $this->written_text;
    }
    public function setWrittenText(?string $written_text): void
    {
        $this->written_text = $written_text;
    }
    public function getRawTextHash(): string
    {
        return $this->raw_text_hash;
    }
    public function setRawTextHash(string $raw_text_hash): void
    {
        $this->raw_text_hash = $raw_text_hash;
    }
    public function getPdfVersion(): ?string
    {
        return $this->pdf_version;
    }
    public function setPdfVersion(?string $pdf_version): void
    {
        $this->pdf_version = $pdf_version;
    }
    public function getTaskId(): int
    {
        return $this->task_id;
    }
    public function setTaskId(int $task_id): void
    {
        $this->task_id = $task_id;
    }
    public function getLastChange(): ?DateTimeImmutable
    {
        return $this->last_change;
    }
    public function setLastChange(?DateTimeImmutable $last_change): void
    {
        $this->last_change = $last_change;
    }
    public function getServiceVersion(): int
    {
        return $this->service_version;
    }
    public function setServiceVersion(int $service_version): void
    {
        $this->service_version = $service_version;
    }
    public function getFirstChange(): ?DateTimeImmutable
    {
        return $this->first_change;
    }
    public function setFirstChange(?DateTimeImmutable $first_change): void
    {
        $this->first_change = $first_change;
    }
}
