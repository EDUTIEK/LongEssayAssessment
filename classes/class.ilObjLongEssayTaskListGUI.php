<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponding ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 */
class ilObjLongEssayTaskListGUI extends ilObjectPluginListGUI
{

	/**
	 * Init type
	 */
	function initType() {
		$this->setType(ilLongEssayTaskPlugin::ID);
	}

	/**
	 * Get name of gui class handling the commands
	 */
	function getGuiClass()
	{
		return "ilObjLongEssayTaskGUI";
	}

	/**
	 * Get commands
	 */
	function initCommands()
	{
		return array
		(
			array(
				"permission" => "read",
				"cmd" => "standardCommand",
				"default" => true),
			array(
				"permission" => "write",
				"cmd" => "editProperties",
				"txt" => $this->txt("edit"),
				"default" => false)
		);
	}

	/**
	 * Get item properties
	 *
	 * @return        array           array of property arrays:
	 *                                "alert" (boolean) => display as an alert property (usually in red)
	 *                                "property" (string) => property name
	 *                                "value" (string) => property value
	 */
	function getProperties()
	{
		$props = array();

		if (!ilObjLongEssayTaskAccess::checkOnline($this->obj_id))
		{
			$props[] = array("alert" => true, "property" => $this->txt("status"),
				"value" => $this->txt("offline"));
		}

		return $props;
	}
}