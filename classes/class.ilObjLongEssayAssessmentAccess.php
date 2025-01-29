<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectSettings;

/**
 * Please do not create instances of large application classes
 * Write small methods within this class to determine the status.
 */
class ilObjLongEssayAssessmentAccess extends ilObjectPluginAccess
{
    /**
     * Checks whether a user may invoke a command or not
     * (this method is called by ilAccessHandler::checkAccess)
     *
     * Please do not check any preconditions handled by
     * ilConditionHandler here. Also don't do usual RBAC checks.
     */
    public function _checkAccess(string $cmd, string $permission, int $ref_id, int $obj_id, ?int $user_id = null): bool
    {
        global $DIC;

        if (empty($user_id)) {
            $user_id = $DIC->user()->getId();
        }

        switch ($permission) {
            case "read":
                if (!self::checkOnline($obj_id) &&
                    !$DIC->access()->checkAccessOfUser($user_id, "write", "", $ref_id)) {
                    return false;
                }
                break;

            default:
                return true;
        }

        return true;
    }

    /**
     * Check if the object is online
     */
    public static function checkOnline(int $obj_id): bool
    {
        global $DIC;
        $db = $DIC->database();

        $result = $db->query("SELECT online FROM xlas_as_settings WHERE ass_id = "
            . $db->quote($obj_id, ilDBConstants::T_INTEGER));

        if ($row = $db->fetchAssoc($result)) {
            return (bool) $row['online'] ?? false;
        }
        return false;
    }
}
