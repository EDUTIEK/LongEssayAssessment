<?php

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_sy_config')]
class Config extends \Edutiek\AssessmentService\System\Data\Config
{
    #[Key]
    private int $id = 0;

    private ?string $writer_url = null;
    private ?string $corrector_url = null;
    private ?string $primary_color = '04427E';
    private ?string $primary_text_color = 'FFFFFF';
    private bool $simulate_offline = false;
    private ?string $path_to_ghostscript = null;


    public static function model()
    {
        return new self();
    }

    public function getWriterUrl(): ?string
    {
        return $this->writer_url;
    }

    public function setWriterUrl(?string $writer_url): Config
    {
        $this->writer_url = $writer_url;
        return $this;
    }

    public function getCorrectorUrl(): ?string
    {
        return $this->corrector_url;
    }

    public function setCorrectorUrl(?string $corrector_url): Config
    {
        $this->corrector_url = $corrector_url;
        return $this;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primary_color;
    }

    public function setPrimaryColor(?string $primary_color): Config
    {
        $this->primary_color = $primary_color;
        return $this;
    }

    public function getPrimaryTextColor(): ?string
    {
        return $this->primary_text_color;
    }

    public function setPrimaryTextColor(?string $primary_text_color): Config
    {
        $this->primary_text_color = $primary_text_color;
        return $this;
    }

    public function getSimulateOffline(): bool
    {
        return $this->simulate_offline;
    }

    public function setSimulateOffline(bool $simulate_offline): Config
    {
        $this->simulate_offline = $simulate_offline;
        return $this;
    }

    public function getPathToGhostscript(): ?string
    {
        return $this->path_to_ghostscript;
    }

    public function setPathToGhostscript(?string $path_to_ghostscript): Config
    {
        $this->path_to_ghostscript = $path_to_ghostscript;
        return $this;
    }
}