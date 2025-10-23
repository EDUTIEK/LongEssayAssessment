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

#[Table(name: 'xlas_ta_writer_anno')]
class WriterAnnotation extends \Edutiek\AssessmentService\Task\Data\WriterAnnotation
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private int $task_id = 0;
    private int $writer_id = 0;
    private int $resource_id = 0;
    private string $mark_key = '';
    private ?string $mark_value = null;
    private ?string $comment = null;
    private int $parent_number = 0;
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
    public function getWriterId(): int
    {
        return $this->writer_id;
    }

    public function setWriterId(int $writer_id): self
    {
        $this->writer_id = $writer_id;
        return $this;
    }
    public function getResourceId(): int
    {
        return $this->resource_id;
    }

    public function setResourceId(int $resource_id): self
    {
        $this->resource_id = $resource_id;
        return $this;
    }

    public function getMarkKey(): string
    {
        return $this->mark_key;
    }

    public function setMarkKey(string $mark_key): self
    {
        $this->mark_key = $mark_key;
        return $this;
    }

    public function getMarkValue(): ?string
    {
        return $this->mark_value;
    }

    public function setMarkValue(?string $mark_value): self
    {
        $this->mark_value = $mark_value;
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
    public function getParentNumber(): int
    {
        return $this->parent_number;
    }

    public function setParentNumber(int $parent_number): self
    {
        $this->parent_number = $parent_number;
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
