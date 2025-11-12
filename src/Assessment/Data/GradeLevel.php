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

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_grade_level')]
class GradeLevel extends \Edutiek\AssessmentService\Assessment\Data\GradeLevel
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private float $min_points = 0;
    private string $grade = '';
    private ?string $code = null;
    private bool $passed = false;
    private int $ass_id = 0;
    private ?string $statement = null;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getMinPoints(): float
    {
        return $this->min_points;
    }
    public function setMinPoints(float $min_points): self
    {
        $this->min_points = $min_points;
        return $this;
    }
    public function getGrade(): string
    {
        return $this->grade;
    }
    public function setGrade(string $grade): self
    {
        $this->grade = $grade;
        return $this;
    }
    public function getCode(): ?string
    {
        return $this->code;
    }
    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }
    public function getPassed(): bool
    {
        return $this->passed;
    }
    public function setPassed(bool $passed): self
    {
        $this->passed = $passed;
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

    public function getStatement(): ?string
    {
        return $this->statement;
    }
    public function setStatement(?string $statement): self
    {
        $this->statement = $statement;
        return $this;
    }
}
