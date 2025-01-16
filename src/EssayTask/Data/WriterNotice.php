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

#[Table(name: 'xlas_et_writer_notice')]
class WriterNotice extends \Edutiek\AssessmentService\EssayTask\Data\WriterNotice
{
    #[Key]
    private int $id = 0;
    private int $essay_id = 0;
    private int $note_no = 0;
    private ?string $note_text = null;
    private ?DateTimeImmutable $last_change = null;

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
    public function getNoteNo(): int
    {
        return $this->note_no;
    }
    public function setNoteNo(int $note_no): void
    {
        $this->note_no = $note_no;
    }
    public function getNoteText(): ?string
    {
        return $this->note_text;
    }
    public function setNoteText(?string $note_text): void
    {
        $this->note_text = $note_text;
    }
    public function getLastChange(): ?DateTimeImmutable
    {
        return $this->last_change;
    }
    public function setLastChange(?DateTimeImmutable $last_change): void
    {
        $this->last_change = $last_change;
    }
}
