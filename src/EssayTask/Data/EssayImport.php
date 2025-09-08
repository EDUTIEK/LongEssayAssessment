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

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;

#[Table(name: 'xlas_et_essay_import')]
class EssayImport extends \Edutiek\AssessmentService\EssayTask\Data\EssayImport
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private string $file_id;
    private ?string $password = null;
    private ?string $expected_hash = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFileId(): string
    {
        return $this->file_id;
    }
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getExpectedHash(): ?string
    {
        return $this->expected_hash;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function setFileId(string $file_id): self
    {
        $this->file_id = $file_id;
        return $this;
    }
    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }
    public function setExpectedHash(?string $hash): self
    {
        $this->expected_hash = $hash;
        return $this;
    }
}
