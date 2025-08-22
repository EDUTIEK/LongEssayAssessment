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

namespace ILIAS\Plugin\LongEssayAssessment\Task\Data;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_ta_corr_points')]
class CorrectorPoints extends \Edutiek\AssessmentService\Task\Data\CorrectorPoints
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private ?int $comment_id = null;
    private ?int $criterion_id = null;
    private int $task_id = 0;
    private int $writer_id = 0;
    private int $corrector_id = 0;
    private float $points = 0;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getCommentId(): ?int
    {
        return $this->comment_id;
    }
    public function setCommentId(?int $comment_id): self
    {
        $this->comment_id = $comment_id;
        return $this;
    }
    public function getCriterionId(): ?int
    {
        return $this->criterion_id;
    }
    public function setCriterionId(?int $criterion_id): self
    {
        $this->criterion_id = $criterion_id;
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
    public function getPoints(): float
    {
        return $this->points;
    }
    public function setPoints(float $points): self
    {
        $this->points = $points;
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

    public function getWriterId(): int
    {
        return $this->writer_id;
    }

    public function setWriterId(int $writer_id): self
    {
        $this->writer_id = $writer_id;
        return $this;
    }
}
