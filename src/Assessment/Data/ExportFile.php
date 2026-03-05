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

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use Edutiek\AssessmentService\Assessment\Data\ExportType;

#[Table(name: 'xlas_as_exp_file')]
class ExportFile extends \Edutiek\AssessmentService\Assessment\Data\ExportFile
{
    #[Key]
    #[Sequence]
    private int $id;
    private int $ass_id = 0;
    private string $file_id = '';
    private string $type = ExportType::RESULTS->value;


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }

    public function getFileId(): string
    {
        return $this->file_id;
    }

    public function setFileId(string $file_id): self
    {
        $this->file_id = $file_id;
        return $this;
    }

    public function getType(): ExportType
    {
        return ExportType::tryFrom($this->type);
    }

    public function setType(ExportType $type): self
    {
        $this->type = $type->value;
        return $this;
    }
}
