<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\System;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * Plugin Configuration
 * This configuration is set in the plugin administration and is identical for all objects
 * There is only a single record with an id of 0
 * This id is hard coded and never get or set by other classes
 * See getters for details of the properties
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
    protected ?string $writer_url = null;
    protected ?string $corrector_url = null;
    protected ?string $eskript_url = null;
    protected ?string $eskript_key = null;
    protected ?string $primary_color = '04427E';
    protected ?string $primary_text_color = 'FFFFFF';
    protected int $simulate_offline = 0;


    public static function model()
    {
        return new self();
    }

    /**
     * URL of the writer web app
     * This can be set for development purposes
     * Otherwise the built-in app is used
     */
    public function getWriterUrl(): string
    {
        return (string) $this->writer_url;
    }

    public function setWriterUrl(string $writer_url): PluginConfig
    {
        $this->writer_url = $writer_url;
        return $this;
    }

    /**
     * URL of the corrector web app
     * This can be set for development purposes
     * Otherwise the built-in app is used
     */
    public function getCorrectorUrl(): string
    {
        return (string) $this->corrector_url;
    }

    public function setCorrectorUrl(string $corrector_url): PluginConfig
    {
        $this->corrector_url = $corrector_url;
        return $this;
    }

    /**
     * Get the primary background color for buttons in the web app
     * e.g. '04427E'
     */
    public function getPrimaryColor(): string
    {
        return (string) $this->primary_color;
    }

    public function setPrimaryColor(string $primary_color): PluginConfig
    {
        $this->primary_color = $primary_color;
        return $this;
    }

    /**
     * Get the primary text color for buttons in the web app
     * e.g. 'FFFFFF'
     */
    public function getPrimaryTextColor(): string
    {
        return (string) $this->primary_text_color;
    }

    public function setPrimaryTextColor(string $primary_text_color): PluginConfig
    {
        $this->primary_text_color = $primary_text_color;
        return $this;
    }

    /**
     * Respond to REST calls
     */
    public function getSimulateOffline(): bool
    {
        return (bool) $this->simulate_offline;
    }

    public function setSimulateOffline(bool $simulate_offline): void
    {
        $this->simulate_offline = (int) $simulate_offline;
    }
}