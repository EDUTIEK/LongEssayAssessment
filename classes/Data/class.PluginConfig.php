<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

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
     */
    protected $connector_container_name = 'xlet_plugin_config';

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
        return $this->writer_url;
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
        return $this->corrector_url;
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
        return $this->eskript_url;
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
        return $this->eskript_key;
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
}