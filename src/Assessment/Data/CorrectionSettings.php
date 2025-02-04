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
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_corr_settings')]
class CorrectionSettings extends \Edutiek\AssessmentService\Assessment\Data\CorrectionSettings
{
    #[Key]
    private int $ass_id = 0;
    private int $required_correctors = 0;
    private float $max_auto_distance = 0;
    private int $mutual_visibility = 0;
    private string $assign_mode = '';
    private int $stitch_when_distance = 0;
    private int $stitch_when_decimals = 0;
    private int $anonymize_correctors = 0;
    private int $reports_enabled = 0;
    private ?DateTimeImmutable $reports_available_start = null;

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
    public function getMaxAutoDistance(): float
    {
        return $this->max_auto_distance;
    }
    public function setMaxAutoDistance(float $max_auto_distance): self
    {
        $this->max_auto_distance = $max_auto_distance;
        return $this;
    }
    public function getMutualVisibility(): int
    {
        return $this->mutual_visibility;
    }
    public function setMutualVisibility(int $mutual_visibility): self
    {
        $this->mutual_visibility = $mutual_visibility;
        return $this;
    }
    public function getAssignMode(): string
    {
        return $this->assign_mode;
    }
    public function setAssignMode(string $assign_mode): self
    {
        $this->assign_mode = $assign_mode;
        return $this;
    }
    public function getStitchWhenDistance(): int
    {
        return $this->stitch_when_distance;
    }
    public function setStitchWhenDistance(int $stitch_when_distance): self
    {
        $this->stitch_when_distance = $stitch_when_distance;
        return $this;
    }
    public function getStitchWhenDecimals(): int
    {
        return $this->stitch_when_decimals;
    }
    public function setStitchWhenDecimals(int $stitch_when_decimals): self
    {
        $this->stitch_when_decimals = $stitch_when_decimals;
        return $this;
    }
    public function getAnonymizeCorrectors(): int
    {
        return $this->anonymize_correctors;
    }
    public function setAnonymizeCorrectors(int $anonymize_correctors): self
    {
        $this->anonymize_correctors = $anonymize_correctors;
        return $this;
    }
    public function getReportsEnabled(): int
    {
        return $this->reports_enabled;
    }
    public function setReportsEnabled(int $reports_enabled): self
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
}
