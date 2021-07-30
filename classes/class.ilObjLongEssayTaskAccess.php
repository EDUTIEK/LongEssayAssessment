<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use \ILIAS\Plugin\LongEssayTask\Models\ObjectSettings;

/**
 * Please do not create instances of large application classes
 * Write small methods within this class to determine the status.
 */
class ilObjLongEssayTaskAccess extends ilObjectPluginAccess
{
	/**
	 * Checks whether a user may invoke a command or not
	 * (this method is called by ilAccessHandler::checkAccess)
	 *
	 * Please do not check any preconditions handled by
	 * ilConditionHandler here. Also don't do usual RBAC checks.
	 *
	 * @param       string $a_cmd command (not permission!)
	 * @param       string $a_permission permission
	 * @param       int $a_ref_id reference id
	 * @param       int $a_obj_id object id
	 * @param 		int $a_user_id user id (default is current user)
	 * @return bool true, if everything is ok
	 */
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = 0)
	{
	    global $DIC;

		if ($a_user_id == 0) {
			$a_user_id = $DIC->user()->getId();
		}

		if (empty($a_obj_id)) {
		    $a_obj_id  = ilObject::_lookupObjectId($a_ref_id);
        }

		switch ($a_permission)
		{
			case "read":
				if (!self::checkOnline($a_obj_id) &&
					!$DIC->access()->checkAccessOfUser($a_user_id, "write", "", $a_ref_id))
				{
					return false;
				}
				break;
		}

		return true;
	}

	/**
     * Check if the object is online
	 * @param integer $a_id
	 * @return bool
	 */
	static function checkOnline($a_id)
	{
		$objectSettings = ObjectSettings::findOrGetInstance($a_id);
		return $objectSettings->isOnline();
	}
}
