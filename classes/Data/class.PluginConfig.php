<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * Plugin Configuration
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class PluginConfig extends \ActiveRecord
{
    use ActiveData;

    /**
     * @var bool
     */
    protected $ar_safe_read = false;
    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_plugin_config';



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
     * Read the configuration values
     */
    public function read()
    {
        $this->id = 1;
        parent::read();
    }

    /**
     * Write the configuration values
     */
    public function save()
    {
        $this->id = 1;
        parent::save();
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
     */
    public function setEskriptUrl(string $eskript_url): void
    {
        $this->eskript_url = $eskript_url;
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
     */
    public function setEskriptKey(string $eskript_key): void
    {
        $this->eskript_key = $eskript_key;
    }
}