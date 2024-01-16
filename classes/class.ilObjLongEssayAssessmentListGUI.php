<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponding ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 */
class ilObjLongEssayAssessmentListGUI extends ilObjectPluginListGUI
{

	/**
	 * Init type
	 */
	function initType() {
		$this->setType(ilLongEssayAssessmentPlugin::ID);
	}

	/**
	 * Get name of gui class handling the commands
	 */
	function getGuiClass() : string
    {
		return "ilObjLongEssayAssessmentGUI";
	}

	/**
	 * Get commands
	 */
	function initCommands() : array
    {
		return array
		(
			array(
				"permission" => "read",
				"cmd" => "standardCommand",
				"default" => true),
			array(
				"permission" => "write",
				"cmd" => "jumpToOrgaSettings",
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
	function getProperties() : array
    {
		$props = array();

		if (!ilObjLongEssayAssessmentAccess::checkOnline($this->obj_id))
		{
			$props[] = array("alert" => true, "property" => $this->txt("status"),
				"value" => $this->txt("offline"));
		}

		return $props;
	}
}