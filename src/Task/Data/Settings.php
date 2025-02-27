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

use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskType;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_ta_settings')]
class Settings extends \Edutiek\AssessmentService\Task\Data\Settings
{
    #[Key]
    private int $task_id = 0;
    private int $ass_id = 0;
    private string $title = '';
    private string $task_type = TaskType::ESSAY->value;
    private ?string $instructions = null;
    private ?string $solution = null;
    private int $position = 0;

    public function getTaskId(): int
    {
        return $this->task_id;
    }
    public function setTaskId(int $task_id): self
    {
        $this->task_id = $task_id;
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
    public function getPosition() : int
    {
        return $this->position;
    }
    public function setPosition(int $position) : self
    {
        $this->position = $position;
        return $this;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
    public function getTaskType() : TaskType
    {
        return TaskType::from($this->task_type);
    }
    public function setTaskType(TaskType $type) : self
    {
        $this->task_type = $type->value;
        return $this;
    }
    public function getInstructions(): ?string
    {
        return $this->instructions;
    }
    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }
    public function getSolution(): ?string
    {
        return $this->solution;
    }
    public function setSolution(?string $solution): self
    {
        $this->solution = $solution;
        return $this;
    }
}
