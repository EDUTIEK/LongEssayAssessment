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

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_corr_comm')]
class CorrectorComment extends \Edutiek\AssessmentService\EssayTask\Data\CorrectorComment
{
    #[Key]
    private int $id = 0;
    private int $essay_id = 0;
    private ?string $comment = null;
    private int $start_position = 0;
    private int $end_position = 0;
    private string $rating = '';
    private int $corrector_id = 0;
    private int $parent_number = 0;
    private ?string $marks = null;

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
    public function getComment(): ?string
    {
        return $this->comment;
    }
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
    public function getStartPosition(): int
    {
        return $this->start_position;
    }
    public function setStartPosition(int $start_position): self
    {
        $this->start_position = $start_position;
        return $this;
    }
    public function getEndPosition(): int
    {
        return $this->end_position;
    }
    public function setEndPosition(int $end_position): self
    {
        $this->end_position = $end_position;
        return $this;
    }
    public function getRating(): string
    {
        return $this->rating;
    }
    public function setRating(string $rating): self
    {
        $this->rating = $rating;
        return $this;
    }
    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }
    public function setCorrectorId(int $corrector_id): self
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }
    public function getParentNumber(): int
    {
        return $this->parent_number;
    }
    public function setParentNumber(int $parent_number): self
    {
        $this->parent_number = $parent_number;
        return $this;
    }
    public function getMarks(): ?string
    {
        return $this->marks;
    }
    public function setMarks(?string $marks): self
    {
        $this->marks = $marks;
        return $this;
    }
}
