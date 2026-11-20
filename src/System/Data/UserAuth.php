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

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use DateTimeImmutable;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_sy_user_auth')]
class UserAuth extends \Edutiek\AssessmentService\System\Data\UserAuth
{
    #[Key]
    private int $user_id = 0;
    private ?string $reset_code = null;
    private ?DateTimeImmutable $reset_valid_until = null;
    private ?string $factor_init_code = null;


    public function getUserId(): int
    {
        return $this->user_id;
    }
    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }
    public function getResetCode(): string
    {
        return $this->reset_code;
    }
    public function setResetCode(?string $code): self
    {
        $this->reset_code = $code;
        return $this;
    }
    public function getResetValidUntil(): ?DateTimeImmutable
    {
        return $this->reset_valid_until;
    }
    public function setResetValidUntil(?DateTimeImmutable $reset_valid_until): self
    {
        $this->reset_valid_until = $reset_valid_until;
        return $this;
    }
    public function getFactorInitCode(): string
    {
        return $this->factor_init_code;
    }
    public function setFactorInitCode(?string $code): self
    {
        $this->factor_init_code = $code;
        return $this;
    }
}
