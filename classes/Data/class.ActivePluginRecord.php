<?php


namespace ILIAS\Plugin\LongEssayTask\Data;

use ActiveRecord;
use arConnector;
use ilLongEssayTaskPlugin;

/**
 * Base class for the active records in the plugin
 *
 * @package ILIAS\Plugin\LongEssayTask\Data
 */
abstract class ActivePluginRecord extends ActiveRecord
{
    const REPOSITORY_CLASS = 'ilObjLongEssayTaskAccess';

    /**
     * @var bool
     */
    protected $ar_safe_read = false;

    /**
     * @var ilLongEssayTaskPlugin
     */
    protected $plugin;

    /**
     * ActivePluginRecord constructor.
     * Initializes the plugin object
     *
     * @param int $primary_key
     * @param arConnector|null $connector
     */
    public function __construct($primary_key = 0, arConnector $connector = null)
    {
        parent::__construct($primary_key, $connector);

        $this->plugin = ilLongEssayTaskPlugin::getInstance();
    }

    /**
     * Overridden to declare the specific return type for type hints in PHPStorm
     * @param       $primary_key
     * @param array $add_constructor_args
     * @return ActiveRecord | static
     */
    public static function findOrGetInstance($primary_key, array $add_constructor_args = array())
    {
        return parent::findOrGetInstance($primary_key, $add_constructor_args);
    }

}