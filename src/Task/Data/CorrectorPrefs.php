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
}
