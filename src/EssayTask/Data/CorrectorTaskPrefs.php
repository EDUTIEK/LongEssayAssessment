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

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_corr_task_prefs')]
class CorrectorTaskPrefs extends \Edutiek\AssessmentService\EssayTask\Data\CorrectorTaskPrefs
{
    private int $task_id = 0;
    private int $corrector_id = 0;
    private bool $criterion_copy = false;

    public function getTaskId(): int
    {
        return $this->task_id;
    }
    public function setTaskId(int $task_id): self
    {
        $this->task_id = $task_id;
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
    public function getCriterionCopy(): bool
    {
        return $this->criterion_copy;
    }
    public function setCriterionCopy(bool $criterion_copy): self
    {
        $this->criterion_copy = $criterion_copy;
        return $this;
    }
}
