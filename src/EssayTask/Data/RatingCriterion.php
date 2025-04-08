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
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_rating_crit')]
class RatingCriterion extends \Edutiek\AssessmentService\EssayTask\Data\RatingCriterion
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private string $title = '';
    private ?string $description = null;
    private int $points = 0;
    private ?int $corrector_id = null;
    private int $task_id = 0;
    private int $general = 0;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
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
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    public function getPoints(): int
    {
        return $this->points;
    }
    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }
    public function getCorrectorId(): ?int
    {
        return $this->corrector_id;
    }
    public function setCorrectorId(?int $corrector_id): self
    {
        $this->corrector_id = $corrector_id;
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
    public function getGeneral(): int
    {
        return $this->general;
    }
    public function setGeneral(int $general): self
    {
        $this->general = $general;
        return $this;
    }
}
