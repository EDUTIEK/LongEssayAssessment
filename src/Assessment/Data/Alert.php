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

#[Table(name: 'xlas_as_alert')]
class Alert extends \Edutiek\AssessmentService\Assessment\Data\Alert
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private ?string $title = null;
    private string $message = '';
    private ?int $writer_id = null;
    private int $ass_id = 0;
    private ?DateTimeImmutable $shown_from = null;
    private ?DateTimeImmutable $shown_until = null;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }
    public function getMessage(): string
    {
        return $this->message;
    }
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }
    public function getWriterId(): ?int
    {
        return $this->writer_id;
    }
    public function setWriterId(?int $writer_id): self
    {
        $this->writer_id = $writer_id;
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
    public function getShownFrom(): ?DateTimeImmutable
    {
        return $this->shown_from;
    }
    public function setShownFrom(?DateTimeImmutable $shown_from): self
    {
        $this->shown_from = $shown_from;
        return $this;
    }
    public function getShownUntil(): ?DateTimeImmutable
    {
        return $this->shown_until;
    }
    public function setShownUntil(?DateTimeImmutable $shown_until): self
    {
        $this->shown_until = $shown_until;
        return $this;
    }
}
