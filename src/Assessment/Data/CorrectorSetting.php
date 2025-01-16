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
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_corr_setting')]
class CorrectorSetting extends \Edutiek\AssessmentService\Assessment\Data\CorrectorSetting
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
    public function setAssId(int $ass_id): void
    {
        $this->ass_id = $ass_id;
    }
    public function getRequiredCorrectors(): int
    {
        return $this->required_correctors;
    }
    public function setRequiredCorrectors(int $required_correctors): void
    {
        $this->required_correctors = $required_correctors;
    }
    public function getMaxAutoDistance(): float
    {
        return $this->max_auto_distance;
    }
    public function setMaxAutoDistance(float $max_auto_distance): void
    {
        $this->max_auto_distance = $max_auto_distance;
    }
    public function getMutualVisibility(): int
    {
        return $this->mutual_visibility;
    }
    public function setMutualVisibility(int $mutual_visibility): void
    {
        $this->mutual_visibility = $mutual_visibility;
    }
    public function getAssignMode(): string
    {
        return $this->assign_mode;
    }
    public function setAssignMode(string $assign_mode): void
    {
        $this->assign_mode = $assign_mode;
    }
    public function getStitchWhenDistance(): int
    {
        return $this->stitch_when_distance;
    }
    public function setStitchWhenDistance(int $stitch_when_distance): void
    {
        $this->stitch_when_distance = $stitch_when_distance;
    }
    public function getStitchWhenDecimals(): int
    {
        return $this->stitch_when_decimals;
    }
    public function setStitchWhenDecimals(int $stitch_when_decimals): void
    {
        $this->stitch_when_decimals = $stitch_when_decimals;
    }
    public function getAnonymizeCorrectors(): int
    {
        return $this->anonymize_correctors;
    }
    public function setAnonymizeCorrectors(int $anonymize_correctors): void
    {
        $this->anonymize_correctors = $anonymize_correctors;
    }
    public function getReportsEnabled(): int
    {
        return $this->reports_enabled;
    }
    public function setReportsEnabled(int $reports_enabled): void
    {
        $this->reports_enabled = $reports_enabled;
    }
    public function getReportsAvailableStart(): ?DateTimeImmutable
    {
        return $this->reports_available_start;
    }
    public function setReportsAvailableStart(?DateTimeImmutable $reports_available_start): void
    {
        $this->reports_available_start = $reports_available_start;
    }
}
