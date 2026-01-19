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

use DateTimeImmutable;
use Edutiek\AssessmentService\Assessment\Data\AssignMode;
use Edutiek\AssessmentService\Assessment\Data\CorrectionApproximation;
use Edutiek\AssessmentService\Assessment\Data\CorrectionProcedure;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;
use Edutiek\AssessmentService\Assessment\Data\Pseudonymization;

#[Table(name: 'xlas_as_corr_settings')]
class CorrectionSettings extends \Edutiek\AssessmentService\Assessment\Data\CorrectionSettings
{
    #[Key]
    private int $ass_id = 0;
    private int $required_correctors = 1;
    private bool $no_manual_decimals = false;
    private float $max_auto_distance = 0;
    private bool $mutual_visibility = false;
    private bool $wait_for_first = false;
    private string $assign_mode = '';
    private bool $procedure_when_distance = false;
    private bool $procedure_when_decimals = false;
    private string $procedure = CorrectionProcedure::NONE->value;
    private string $approximation = CorrectionApproximation::ONE->value;
    private bool $revision_between = false;
    private bool $stitch_after_procedure = false;
    private bool $undo_authorization = false;
    private bool $instant_status = false;
    private string $pseudonymization = Pseudonymization::WRITER_ID->value;
    private bool $anonymize_correctors = false;
    private bool $reports_enabled = false;
    private ?DateTimeImmutable $reports_available_start = null;
    private ?int $max_points = null;

    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }
    public function getRequiredCorrectors(): int
    {
        return $this->required_correctors;
    }
    public function setRequiredCorrectors(int $required_correctors): self
    {
        $this->required_correctors = $required_correctors;
        return $this;
    }
    public function getNoManualDecimals(): bool
    {
        return $this->no_manual_decimals;
    }
    public function setNoManualDecimals(bool $no_manual_decimals): self
    {
        $this->no_manual_decimals = $no_manual_decimals;
        return $this;
    }
    public function getMaxAutoDistance(): float
    {
        return $this->max_auto_distance;
    }
    public function setMaxAutoDistance(float $max_auto_distance): self
    {
        $this->max_auto_distance = $max_auto_distance;
        return $this;
    }
    public function getMutualVisibility(): bool
    {
        return $this->mutual_visibility;
    }
    public function setMutualVisibility(bool $mutual_visibility): self
    {
        $this->mutual_visibility = $mutual_visibility;
        return $this;
    }
    public function getWaitForFirst(): bool
    {
        return $this->wait_for_first;
    }
    public function setWaitForFirst(bool $wait_for_first): self
    {
        $this->wait_for_first = $wait_for_first;
        return $this;
    }
    public function getAssignMode(): AssignMode
    {
        return AssignMode::tryFrom($this->assign_mode) ?? AssignMode::RANDOM_EQUAL;
    }
    public function setAssignMode(AssignMode $assign_mode): self
    {
        $this->assign_mode = $assign_mode->value;
        return $this;
    }
    public function getProcedureWhenDistance(): bool
    {
        return $this->procedure_when_distance;
    }
    public function setProcedureWhenDistance(bool $procedure_when_distance): self
    {
        $this->procedure_when_distance = $procedure_when_distance;
        return $this;
    }
    public function getProcedureWhenDecimals(): bool
    {
        return $this->procedure_when_decimals;
    }
    public function setProcedureWhenDecimals(bool $procedure_when_decimals): self
    {
        $this->procedure_when_decimals = $procedure_when_decimals;
        return $this;
    }
    public function getProcedure(): CorrectionProcedure
    {
        return CorrectionProcedure::tryFrom($this->procedure) ?? CorrectionProcedure::NONE;
    }
    public function setProcedure(CorrectionProcedure $procedure): self
    {
        $this->procedure = $procedure->value;
        return $this;
    }
    public function getApproximation(): CorrectionApproximation
    {
        return CorrectionApproximation::tryFrom($this->approximation) ?? CorrectionApproximation::DECIDE;
    }
    public function setApproximation(CorrectionApproximation $approximation): self
    {
        $this->approximation = $approximation->value;
        return $this;
    }
    public function getRevisionBetween(): bool
    {
        return $this->revision_between;
    }
    public function setRevisionBetween(bool $revision_between): self
    {
        $this->revision_between = $revision_between;
        return $this;
    }
    public function getStitchAfterProcedure(): bool
    {
        return $this->stitch_after_procedure;
    }
    public function setStitchAfterProcedure(bool $stitch_after_procedure): self
    {
        $this->stitch_after_procedure = $stitch_after_procedure;
        return $this;
    }
    public function getUndoAuthorization(): bool
    {
        return $this->undo_authorization;
    }
    public function setUndoAuthorization(bool $undo_authorization): self
    {
        $this->undo_authorization = $undo_authorization;
        return $this;
    }
    public function getInstantStatus(): bool
    {
        return $this->instant_status;
    }
    public function setInstantStatus(bool $instant_status): self
    {
        $this->instant_status = $instant_status;
        return $this;
    }
    public function getPseudonymization(): Pseudonymization
    {
        return Pseudonymization::tryFrom($this->pseudonymization) ?? Pseudonymization::WRITER_ID;
    }
    public function setPseudonymization(Pseudonymization $pseudonymization): self
    {
        $this->pseudonymization = $pseudonymization->value;
        return $this;
    }
    public function getAnonymizeCorrectors(): bool
    {
        return $this->anonymize_correctors;
    }
    public function setAnonymizeCorrectors(bool $anonymize_correctors): self
    {
        $this->anonymize_correctors = $anonymize_correctors;
        return $this;
    }
    public function getReportsEnabled(): bool
    {
        return $this->reports_enabled;
    }
    public function setReportsEnabled(bool $reports_enabled): self
    {
        $this->reports_enabled = $reports_enabled;
        return $this;
    }
    public function getReportsAvailableStart(): ?DateTimeImmutable
    {
        return $this->reports_available_start;
    }
    public function setReportsAvailableStart(?DateTimeImmutable $reports_available_start): self
    {
        $this->reports_available_start = $reports_available_start;
        return $this;
    }

    public function getMaxPoints(): ?int
    {
        return $this->max_points;
    }

    public function setMaxPoints(?int $max_points): self
    {
        $this->max_points = $max_points;
        return $this;
    }
}
