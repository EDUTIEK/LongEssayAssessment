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

#[Table(name: 'xlas_et_writer_history')]
class WriterHistory extends \Edutiek\AssessmentService\EssayTask\Data\WriterHistory
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private int $essay_id = 0;
    private ?DateTimeImmutable $timestamp = null;
    private ?string $content = null;
    private int $is_delta = 0;
    private ?string $hash_before = null;
    private ?string $hash_after = null;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getEssayId(): int
    {
        return $this->essay_id;
    }
    public function setEssayId(int $essay_id): self
    {
        $this->essay_id = $essay_id;
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
    public function getContent(): ?string
    {
        return $this->content;
    }
    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }
    public function getIsDelta(): int
    {
        return $this->is_delta;
    }
    public function setIsDelta(int $is_delta): self
    {
        $this->is_delta = $is_delta;
        return $this;
    }
    public function getHashBefore(): ?string
    {
        return $this->hash_before;
    }
    public function setHashBefore(?string $hash_before): self
    {
        $this->hash_before = $hash_before;
        return $this;
    }
    public function getHashAfter(): ?string
    {
        return $this->hash_after;
    }
    public function setHashAfter(?string $hash_after): self
    {
        $this->hash_after = $hash_after;
        return $this;
    }
}
