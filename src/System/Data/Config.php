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

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_sy_config')]
class Config extends \Edutiek\AssessmentService\System\Data\Config
{
    public const DEFAULT_HASH_ALGO = 'sha256';

    #[Key]
    private int $id = 0;
    private ?string $writer_url = null;
    private ?string $corrector_url = null;
    private ?string $primary_color = '04427E';
    private ?string $primary_text_color = 'FFFFFF';
    private bool $simulate_offline = false;
    private ?string $path_to_ghostscript = null;
    private string $hash_algo = self::DEFAULT_HASH_ALGO;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getWriterUrl(): ?string
    {
        return $this->nullify($this->writer_url);
    }
    public function setWriterUrl(?string $writer_url): self
    {
        $this->writer_url = $writer_url;
        return $this;
    }
    public function getCorrectorUrl(): ?string
    {
        return $this->nullify($this->corrector_url);
    }
    public function setCorrectorUrl(?string $corrector_url): self
    {
        $this->corrector_url = $corrector_url;
        return $this;
    }
    public function getPrimaryColor(): ?string
    {
        return $this->nullify($this->primary_color);
    }
    public function setPrimaryColor(?string $primary_color): self
    {
        $this->primary_color = $primary_color;
        return $this;
    }
    public function getPrimaryTextColor(): ?string
    {
        return $this->nullify($this->primary_text_color);
    }
    public function setPrimaryTextColor(?string $primary_text_color): self
    {
        $this->primary_text_color = $primary_text_color;
        return $this;
    }
    public function getSimulateOffline(): bool
    {
        return $this->simulate_offline;
    }
    public function setSimulateOffline(bool $simulate_offline): self
    {
        $this->simulate_offline = $simulate_offline;
        return $this;
    }
    public function getPathToGhostscript(): ?string
    {
        return $this->nullify($this->path_to_ghostscript);
    }
    public function setPathToGhostscript(?string $path_to_ghostscript): self
    {
        $this->path_to_ghostscript = $path_to_ghostscript;
        return $this;
    }
    public function getHashAlgo(): string
    {
        return $this->hash_algo;
    }
    public function setHashAlgo(string $algo): self
    {
        $this->hash_algo = $algo;
        return $this;
    }

    private function nullify(?string $string): ?string
    {
        return $string === '' ?  null : $string;
    }
}
