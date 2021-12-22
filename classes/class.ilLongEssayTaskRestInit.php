<?php

/**
 * Extended ILIAS initialisation for REST calls
 */
class ilLongEssayTaskRestInit extends ilInitialisation
{
    /**
     * Inject a user account
     * @param ilObjUser $user
     */
    public static function initRestUser(ilObjUser $user)
    {
        global $DIC;

        // fix for missing ilUser in REST calls
        if (!$DIC->offsetExists('ilUser')) {
            $GLOBALS['ilUser'] = $user;
            $DIC['ilUser'] = function ($c) {
                return $GLOBALS['ilUser'];
            };
        }

        self::initAccessHandling();;
    }

}