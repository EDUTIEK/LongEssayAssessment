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
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_ta_corr_prefs')]
class CorrectorPrefs extends \Edutiek\AssessmentService\Task\Data\CorrectorPrefs
{
    #[Key]
    private int $corrector_id = 0;
    private float $essay_page_zoom = 0;
    private float $essay_text_zoom = 0;
    private float $summary_text_zoom = 0;
    private ?string $default_shape = null;
    private bool $display_labels = false;
    private bool $select_words = true;
    private ?string $filter_grading_status = null;
    private ?int $filter_assigned_position = null;

    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }
    public function setCorrectorId(int $corrector_id): self
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }
    public function getEssayPageZoom(): float
    {
        return $this->essay_page_zoom;
    }
    public function setEssayPageZoom(float $essay_page_zoom): self
    {
        $this->essay_page_zoom = $essay_page_zoom;
        return $this;
    }
    public function getEssayTextZoom(): float
    {
        return $this->essay_text_zoom;
    }
    public function setEssayTextZoom(float $essay_text_zoom): self
    {
        $this->essay_text_zoom = $essay_text_zoom;
        return $this;
    }
    public function getSummaryTextZoom(): float
    {
        return $this->summary_text_zoom;
    }
    public function setSummaryTextZoom(float $summary_text_zoom): self
    {
        $this->summary_text_zoom = $summary_text_zoom;
        return $this;
    }
    public function getDefaultShape(): ?string
    {
        return $this->default_shape;
    }
    public function setDefaultShape(?string $default_shape): self
    {
        $this->default_shape = $default_shape;
        return $this;
    }
    public function getDisplayLabels(): bool
    {
        return $this->display_labels;
    }
    public function setDisplayLabels(bool $display_labels): self
    {
        $this->display_labels = $display_labels;
        return $this;
    }
    public function getSelectWords(): bool
    {
        return $this->select_words;
    }
    public function setSelectWords(bool $select_words): self
    {
        $this->select_words = $select_words;
        return $this;
    }
    public function getFilterGradingStatus(): ?string
    {
        return $this->filter_grading_status;
    }
    public function setFilterGradingStatus(?string $filter_grading_status): self
    {
        $this->filter_grading_status = $filter_grading_status;
        return $this;
    }
    public function getFilterAssignedPosition(): ?int
    {
        return $this->filter_assigned_position;
    }
    public function setFilterAssignedPosition(?int $filter_assigned_position): self
    {
        $this->filter_assigned_position = $filter_assigned_position;
        return $this;
    }
}
