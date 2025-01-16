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

#[Table(name: 'xlas_as_token')]
class Token extends \Edutiek\AssessmentService\Assessment\Data\Token
{
    #[Key]
    private int $id = 0;
    private int $user_id = 0;
    private string $token = '';
    private string $ip = '';
    private string $purpose = '';
    private int $ass_id = 0;
    private ?DateTimeImmutable $valid_until = null;

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
    public function getToken(): string
    {
        return $this->token;
    }
    public function setToken(string $token): void
    {
        $this->token = $token;
    }
    public function getIp(): string
    {
        return $this->ip;
    }
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }
    public function getPurpose(): string
    {
        return $this->purpose;
    }
    public function setPurpose(string $purpose): void
    {
        $this->purpose = $purpose;
    }
    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): void
    {
        $this->ass_id = $ass_id;
    }
    public function getValidUntil(): ?DateTimeImmutable
    {
        return $this->valid_until;
    }
    public function setValidUntil(?DateTimeImmutable $valid_until): void
    {
        $this->valid_until = $valid_until;
    }
}
