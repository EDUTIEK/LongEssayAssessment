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
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_writer_history')]
class WriterHistory extends \Edutiek\AssessmentService\EssayTask\Data\WriterHistory
{
    #[Key]
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
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getEssayId(): int
    {
        return $this->essay_id;
    }
    public function setEssayId(int $essay_id): void
    {
        $this->essay_id = $essay_id;
    }
    public function getTimestamp(): ?DateTimeImmutable
    {
        return $this->timestamp;
    }
    public function setTimestamp(?DateTimeImmutable $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
    public function getContent(): ?string
    {
        return $this->content;
    }
    public function setContent(?string $content): void
    {
        $this->content = $content;
    }
    public function getIsDelta(): int
    {
        return $this->is_delta;
    }
    public function setIsDelta(int $is_delta): void
    {
        $this->is_delta = $is_delta;
    }
    public function getHashBefore(): ?string
    {
        return $this->hash_before;
    }
    public function setHashBefore(?string $hash_before): void
    {
        $this->hash_before = $hash_before;
    }
    public function getHashAfter(): ?string
    {
        return $this->hash_after;
    }
    public function setHashAfter(?string $hash_after): void
    {
        $this->hash_after = $hash_after;
    }
}
