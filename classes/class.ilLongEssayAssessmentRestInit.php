<?php

/**
 * Extended ILIAS initialisation for REST calls
 * An initialized user is needed for access handling
 */
class ilLongEssayAssessmentRestInit extends ilInitialisation
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

        self::initAccessHandling();
        self::initLanguage();
    }
}
