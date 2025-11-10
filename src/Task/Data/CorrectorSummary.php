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

use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_ta_corr_summary')]
class CorrectorSummary extends \Edutiek\AssessmentService\Task\Data\CorrectorSummary
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private int $task_id = 0;
    private int $writer_id = 0;
    private int $corrector_id = 0;
    private ?string $summary_text = null;
    private ?string $summary_pdf = null;
    private ?float $points = null;
    private ?DateTimeImmutable $last_change = null;
    private ?DateTimeImmutable $corection_authorized = null;
    private ?int $correction_authorized_by = null;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
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
    public function getSummaryText(): ?string
    {
        return $this->summary_text;
    }
    public function setSummaryText(?string $summary_text): self
    {
        $this->summary_text = $summary_text;
        return $this;
    }
    public function getSummaryPdf(): ?string
    {
        return $this->summary_pdf;
    }
    public function setSummaryPdf(?string $summary_pdf): self
    {
        $this->summary_pdf = $summary_pdf;
        return $this;
    }
    public function getPoints(): ?float
    {
        return $this->points;
    }
    public function setPoints(?float $points): self
    {
        $this->points = $points;
        return $this;
    }
    public function getLastChange(): ?DateTimeImmutable
    {
        return $this->last_change;
    }
    public function setLastChange(?DateTimeImmutable $last_change): self
    {
        $this->last_change = $last_change;
        return $this;
    }
    public function getCorrectionAuthorized(): ?DateTimeImmutable
    {
        return $this->corection_authorized;
    }
    public function setCorrectionAuthorized(?DateTimeImmutable $corection_authorized): self
    {
        $this->corection_authorized = $corection_authorized;
        return $this;
    }
    public function getCorrectionAuthorizedBy(): ?int
    {
        return $this->correction_authorized_by;
    }
    public function setCorrectionAuthorizedBy(?int $correction_authorized_by): self
    {
        $this->correction_authorized_by = $correction_authorized_by;
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
