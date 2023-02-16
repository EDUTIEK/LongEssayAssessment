<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Object;

use ILIAS\Plugin\LongEssayAssessment\Data\ActivePluginRecord;

/**
 * Plugin Configuration
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class PluginConfig extends ActivePluginRecord
{
    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $id;
    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $writer_url = '';
    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $corrector_url = '';
    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $eskript_url = '';
    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $eskript_key = '';

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $primary_color = '';

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $primary_text_color = '';

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $simulate_offline = 0;



    /**
     * @var string
     */
    protected $connector_container_name = 'xlas_plugin_config';

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return PluginConfig
     */
    public function setId(int $id): PluginConfig
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getWriterUrl(): string
    {
        return (string) $this->writer_url;
    }

    /**
     * @param string $writer_url
     * @return PluginConfig
     */
    public function setWriterUrl(string $writer_url): PluginConfig
    {
        $this->writer_url = $writer_url;
        return $this;
    }

    /**
     * @return string
     */
    public function getCorrectorUrl(): string
    {
        return (string) $this->corrector_url;
    }

    /**
     * @param string $corrector_url
     * @return PluginConfig
     */
    public function setCorrectorUrl(string $corrector_url): PluginConfig
    {
        $this->corrector_url = $corrector_url;
        return $this;
    }

    /**
     * @return string
     */
    public function getEskriptUrl(): string
    {
        return (string) $this->eskript_url;
    }

    /**
     * @param string $eskript_url
     * @return PluginConfig
     */
    public function setEskriptUrl(string $eskript_url): PluginConfig
    {
        $this->eskript_url = $eskript_url;
        return $this;
    }

    /**
     * @return string
     */
    public function getEskriptKey(): string
    {
        return (string) $this->eskript_key;
    }

    /**
     * @param string $eskript_key
     * @return PluginConfig
     */
    public function setEskriptKey(string $eskript_key): PluginConfig
    {
        $this->eskript_key = $eskript_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrimaryColor(): string
    {
        return (string) $this->primary_color;
    }

    /**
     * @param string $primary_color
     * @return PluginConfig
     */
    public function setPrimaryColor(string $primary_color): PluginConfig
    {
        $this->primary_color = $primary_color;
        return $this;
    }


    /**
     * @return string
     */
    public function getPrimaryTextColor(): string
    {
        return (string) $this->primary_text_color;
    }

    /**
     * @param string $primary_text_color
     * @return PluginConfig
     */
    public function setPrimaryTextColor(string $primary_text_color): PluginConfig
    {
        $this->primary_text_color = $primary_text_color;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSimulateOffline(): bool
    {
        return (bool) $this->simulate_offline;
    }

    /**
     * @param int $simulate_offline
     */
    public function setSimulateOffline(bool $simulate_offline): void
    {
        $this->simulate_offline = (int) $simulate_offline;
    }
}