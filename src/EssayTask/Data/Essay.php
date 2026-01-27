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

namespace ILIAS\Plugin\LongEssayAssessment\EssayTask\Data;

use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_essay')]
class Essay extends \Edutiek\AssessmentService\EssayTask\Data\Essay
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private string $uuid = '';
    private int $writer_id = 0;
    private ?string $written_text = null;
    private string $raw_text_hash = '';
    private ?string $pdf_version = null;
    private int $task_id = 0;
    private ?DateTimeImmutable $last_change = null;
    private int $service_version = 0;
    private ?DateTimeImmutable $first_change = null;
    private int $pdf_from_written_text = 0;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getUuid(): string
    {
        return $this->uuid;
    }
    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }
    public function getWriterId(): int
    {
        return $this->writer_id;
    }
    public function setWriterId(int $writer_id): self
    {
        $this->writer_id = $writer_id;
        return $this;
    }
    public function getWrittenText(): ?string
    {
        return $this->written_text;
    }
    public function setWrittenText(?string $written_text): self
    {
        $this->written_text = $written_text;
        return $this;
    }
    public function getRawTextHash(): string
    {
        return $this->raw_text_hash;
    }
    public function setRawTextHash(string $raw_text_hash): self
    {
        $this->raw_text_hash = $raw_text_hash;
        return $this;
    }
    public function getPdfVersion(): ?string
    {
        return $this->pdf_version;
    }
    public function setPdfVersion(?string $pdf_version): self
    {
        $this->pdf_version = $pdf_version;
        return $this;
    }
    public function getTaskId(): int
    {
        return $this->task_id;
    }
    public function setTaskId(int $task_id): self
    {
        $this->task_id = $task_id;
        return $this;
    }
    public function getLastChange(): ?DateTimeImmutable
    {
        return $this->last_change;
    }
    public function setLastChange(?DateTimeImmutable $last_change): self
    {
        $this->last_change = $last_change;
        return $this;
    }
    public function getServiceVersion(): int
    {
        return $this->service_version;
    }
    public function setServiceVersion(int $service_version): self
    {
        $this->service_version = $service_version;
        return $this;
    }
    public function getFirstChange(): ?DateTimeImmutable
    {
        return $this->first_change;
    }
    public function setFirstChange(?DateTimeImmutable $first_change): self
    {
        $this->first_change = $first_change;
        return $this;
    }

    public function hasPdfFromWrittenText(): bool
    {
        return (bool) $this->pdf_from_written_text;
    }

    public function setPdfFromWrittenText(bool $pdf_from_written_text): self
    {
        $this->pdf_from_written_text = (int) $pdf_from_written_text;
        return $this;
    }
}
