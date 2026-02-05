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
use Edutiek\AssessmentService\System\Api\HasHtml;

#[Table(name: 'xlas_ta_corr_template')]
class CorrectorTemplate extends \Edutiek\AssessmentService\Task\Data\CorrectorTemplate
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private int $task_id = 0;
    private int $corrector_id = 0;
    private int $shared = 0;
    private ?string $content = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCorrectorId(): int
    {
        return $this->corrector_id;
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

    public function setCorrectorId(int $corrector_id): self
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }

    public function getShared(): bool
    {
        return (bool) $this->shared;
    }

    public function setShared(bool $shared): self
    {
        $this->shared = (int) $shared;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

}
