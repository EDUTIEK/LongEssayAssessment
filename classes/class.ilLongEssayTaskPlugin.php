<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayTask\Data\PluginConfig;

/**
 * Basic plugin file
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class ilLongEssayTaskPlugin extends ilRepositoryObjectPlugin
{
     const ID = "xlet";

    /** @var Container */
    protected $dic;

    /** @var self */
    protected static $instance;


    /**
     * Constructor.
     */
    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    /**
     * Get the Plugin name
     * must correspond to the plugin subdirectory
     * @return string
     */
    public function getPluginName()
	{
		return "LongEssayTask";
	}

    /**
     * @inheritdoc
     */
    public function getParentTypes()
    {
        return array("cat", "crs", "grp", "fold");
    }

    /**
     * @inheritdoc
     */
    public function allowCopy()
    {
        return false;
    }

    /**
     * Uninstall custom data of this plugin
     */
    protected function uninstallCustom()
    {
        $this->dic->database()->dropTable('xlet_plugin_config');
    }

    /**
     * Get the plugin instance
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the plugin configuration with loaded values
     */
    public function getConfig(): PluginConfig
    {
        // caching is already done by ActiveRecord
        $config = new PluginConfig();
        $config->read();
        return $config;
    }


    /**
     * Check if the current user has administrative access
     * @return bool
     */
    public function hasAdminAccess()
    {
        return $this->dic->rbac()->system()->checkAccess("visible", SYSTEM_FOLDER_ID);
    }


    /**
     * Get a plugin text and use the variable, if not translated
     * @param string $a_var
     * @return string
     */
    public function txt(string $a_var) : string
    {
        $txt = parent::txt($a_var);
        if (substr($txt, 0, 5) == '-rep_') {
            return $a_var;
        }
        return $txt;
    }
}