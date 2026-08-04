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
    private ?DateTimeImmutable $pre_graded = null;
    private ?DateTimeImmutable $revised = null;
    private ?string $revision_text = null;
    private ?float $revision_points = null;
    private bool $require_other_revision = false;

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
        $this->summary_pdf = empty($summary_pdf) ? null : $summary_pdf;
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

    public function getPreGraded(): ?DateTimeImmutable
    {
        return $this->pre_graded;
    }

    public function setPreGraded(?DateTimeImmutable $pre_graded): self
    {
        $this->pre_graded = $pre_graded;
        return $this;
    }

    public function getRevised(): ?DateTimeImmutable
    {
        return $this->revised;
    }

    public function setRevised(?DateTimeImmutable $revised): self
    {
        $this->revised = $revised;
        return $this;
    }

    public function getRevisionText(): ?string
    {
        return $this->revision_text;
    }

    public function setRevisionText(?string $revision_text): self
    {
        $this->revision_text = $revision_text;
        return $this;
    }

    public function getRevisionPoints(): ?float
    {
        return $this->revision_points;
    }

    public function setRevisionPoints(?float $revision_points): self
    {
        $this->revision_points = $revision_points;
        return $this;
    }

    public function getRequireOtherRevision(): bool
    {
        return $this->require_other_revision;
    }

    public function setRequireOtherRevision(bool $require_other_revision): self
    {
        $this->require_other_revision = $require_other_revision;
        return $this;
    }
}
