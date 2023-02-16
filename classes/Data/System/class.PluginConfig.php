<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\System;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * Plugin Configuration
 * Single record: id is always 0
 *
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class PluginConfig extends RecordData
{
    protected const tableName = 'xlas_plugin_config';
    protected const hasSequence = false;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'writer_url' => 'text',
        'corrector_url' => 'text',
        'eskript_url' => 'text',
        'eskript_key' => 'text',
        'primary_color' => 'text',
        'primary_text_color' => 'text',
        'simulate_offline' => 'integer'
    ];

    protected int $id = 0;
    protected ?string $writer_url;
    protected ?string $corrector_url;
    protected ?string $eskript_url;
    protected ?string $eskript_key;
    protected ?string $primary_color;
    protected ?string $primary_text_color;
    protected int $simulate_offline = 0;


    public static function model()
    {
        return new self();
    }

    public function getWriterUrl(): string
    {
        return (string) $this->writer_url;
    }

    public function setWriterUrl(string $writer_url): PluginConfig
    {
        $this->writer_url = $writer_url;
        return $this;
    }

    public function getCorrectorUrl(): string
    {
        return (string) $this->corrector_url;
    }

    public function setCorrectorUrl(string $corrector_url): PluginConfig
    {
        $this->corrector_url = $corrector_url;
        return $this;
    }

    public function getEskriptUrl(): string
    {
        return (string) $this->eskript_url;
    }

    public function setEskriptUrl(string $eskript_url): PluginConfig
    {
        $this->eskript_url = $eskript_url;
        return $this;
    }

    public function getEskriptKey(): string
    {
        return (string) $this->eskript_key;
    }

    public function setEskriptKey(string $eskript_key): PluginConfig
    {
        $this->eskript_key = $eskript_key;
        return $this;
    }

    public function getPrimaryColor(): string
    {
        return (string) $this->primary_color;
    }

    public function setPrimaryColor(string $primary_color): PluginConfig
    {
        $this->primary_color = $primary_color;
        return $this;
    }

    public function getPrimaryTextColor(): string
    {
        return (string) $this->primary_text_color;
    }

    public function setPrimaryTextColor(string $primary_text_color): PluginConfig
    {
        $this->primary_text_color = $primary_text_color;
        return $this;
    }

    public function getSimulateOffline(): bool
    {
        return (bool) $this->simulate_offline;
    }

    public function setSimulateOffline(bool $simulate_offline): void
    {
        $this->simulate_offline = (int) $simulate_offline;
    }
}