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

use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_marked_pdf')]
class MarkedPdf extends \Edutiek\AssessmentService\EssayTask\Data\MarkedPdf
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private int $task_id = 0;
    private int $writer_id = 0;
    private int $corrector_id = 0;
    private string $own_pdf = '';
    private string $sum_pdf = '';


    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
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

    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }

    public function setCorrectorId(int $corrector_id): self
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }

    public function getOwnPdf(): string
    {
        return $this->own_pdf;
    }

    public function setOwnPdf(string $own_pdf): self
    {
        $this->own_pdf = $own_pdf;
        return $this;
    }

    public function getSumPdf(): string
    {
        return $this->sum_pdf;
    }

    public function setSumPdf(string $sum_pdf): self
    {
        $this->sum_pdf = $sum_pdf;
        return $this;
    }
}
