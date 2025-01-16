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

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_as_corrector')]
class Corrector extends \Edutiek\AssessmentService\Assessment\Data\Corrector
{
    #[Key]
    private int $id = 0;
    private int $user_id = 0;
    private ?string $correction_report = null;
    private int $ass_id = 0;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getUserId(): int
    {
        return $this->user_id;
    }
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }
    public function getCorrectionReport(): ?string
    {
        return $this->correction_report;
    }
    public function setCorrectionReport(?string $correction_report): void
    {
        $this->correction_report = $correction_report;
    }
    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): void
    {
        $this->ass_id = $ass_id;
    }
}
