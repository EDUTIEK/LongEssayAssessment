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
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_ta_writer_comment')]
class WriterComment extends \Edutiek\AssessmentService\Task\Data\WriterComment
{
    #[Key]
    private int $id = 0;
    private int $task_id = 0;
    private ?string $comment = null;
    private int $start_position = 0;
    private int $end_position = 0;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
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
}
