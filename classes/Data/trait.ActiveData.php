<?php


namespace ILIAS\Plugin\LongEssayTask\Data;


trait ActiveData
{
    /**
     * Overridden to declare the specific return type
     * @param       $primary_key
     * @param array $add_constructor_args
     * @return \ActiveRecord | static
     */
    public static function findOrGetInstance($primary_key, array $add_constructor_args = array())
    {
        return parent::findOrGetInstance($primary_key,  $add_constructor_args);
    }

}