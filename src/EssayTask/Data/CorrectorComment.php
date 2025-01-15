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

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_corr_comm')]
class CorrectorComment extends \Edutiek\AssessmentService\EssayTask\Data\CorrectorComment
{
    #[Key]
    private int $id;
    private int $essay_id;
    private ?string $comment;
    private int $start_position;
    private int $end_position;
    private string $rating;
    private int $corrector_id;
    private int $parent_number;
    private int $points;
    private ?string $mark;
    private ?string $marks;

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
    public function getComment(): ?string
    {
        return $this->comment;
    }
    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }
    public function getStartPosition(): int
    {
        return $this->start_position;
    }
    public function setStartPosition(int $start_position): void
    {
        $this->start_position = $start_position;
    }
    public function getEndPosition(): int
    {
        return $this->end_position;
    }
    public function setEndPosition(int $end_position): void
    {
        $this->end_position = $end_position;
    }
    public function getRating(): string
    {
        return $this->rating;
    }
    public function setRating(string $rating): void
    {
        $this->rating = $rating;
    }
    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }
    public function setCorrectorId(int $corrector_id): void
    {
        $this->corrector_id = $corrector_id;
    }
    public function getParentNumber(): int
    {
        return $this->parent_number;
    }
    public function setParentNumber(int $parent_number): void
    {
        $this->parent_number = $parent_number;
    }
    public function getPoints(): int
    {
        return $this->points;
    }
    public function setPoints(int $points): void
    {
        $this->points = $points;
    }
    public function getMark(): ?string
    {
        return $this->mark;
    }
    public function setMark(?string $mark): void
    {
        $this->mark = $mark;
    }
    public function getMarks(): ?string
    {
        return $this->marks;
    }
    public function setMarks(?string $marks): void
    {
        $this->marks = $marks;
    }
}
