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
use Edutiek\AssessmentService\Assessment\Data\CorrectionStatus;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;
use Edutiek\AssessmentService\Assessment\Data\WritingStatus;

/**
 * Tracking of client information from the writer web app
 */
#[Table(name: 'xlas_as_writer_client')]
class WriterClient extends \Edutiek\AssessmentService\Assessment\Data\WriterClient
{
    #[Key]
    private int $writer_id = 0;
    #[Key]
    private int $token_id = 0;
    private ?string $session_id = null;
    private DateTimeImmutable $first_access;
    private DateTimeImmutable $last_access;
    private ?string $ip = null;
    private ?string $user_agent = null;
    private ?string $platform = null;
    private ?float $battery = null;
    private ?bool $hidden = null;

    public function getWriterId(): int
    {
        return $this->writer_id;
    }

    public function setWriterId(int $writer_id): static
    {
        $this->writer_id = $writer_id;
        return $this;
    }

    /**
     * Access token created for the web app
     */
    public function getTokenId(): int
    {
        return $this->token_id;
    }

    public function setTokenId(int $token_id): static
    {
        $this->token_id = $token_id;
        return $this;
    }

    /**
     * PHP session id when the token was created
     */
    public function getSessionId(): ?string
    {
        return $this->session_id;
    }

    public function setSessionId(?string $session_id): static
    {
        $this->session_id = isset($session_id) ? substr($session_id, 0, 260) : null;
        return $this;
    }

    public function getFirstAccess(): DateTimeImmutable
    {
        return $this->first_access;
    }

    public function setFirstAccess(DateTimeImmutable $first_access): static
    {
        $this->first_access = $first_access;
        return $this;
    }

    public function getLastAccess(): DateTimeImmutable
    {
        return $this->last_access;
    }

    public function setLastAccess(DateTimeImmutable $last_access): static
    {
        $this->last_access = $last_access;
        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = isset($ip) ? substr($ip, 0, 50) : null;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(?string $user_agent): static
    {
        $this->user_agent = isset($user_agent) ? substr($user_agent, 0, 600) : null;
        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): static
    {
        $this->platform = isset($platform) ? substr($platform, 0, 50) : null;
        return $this;
    }

    public function getBattery(): ?float
    {
        return $this->battery;
    }

    public function setBattery(?float $battery): static
    {
        $this->battery = $battery;
        return $this;
    }

    public function getHidden(): ?bool
    {
        return $this->hidden;
    }

    public function setHidden(?bool $hidden): static
    {
        $this->hidden = $hidden;
        return $this;
    }
}
