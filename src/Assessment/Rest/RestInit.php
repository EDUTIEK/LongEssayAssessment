<?php

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Rest;

use ilObjUser;

class RestInit extends \ilInitialisation
{
    public static function initRestUser(int $user_id)
    {
        global $DIC;

        // fix for missing ilUser in REST calls
        if (!$DIC->offsetExists('ilUser')) {
            $GLOBALS['ilUser'] = new ilObjUser($user_id);
            $DIC['ilUser'] = function ($c) {
                return $GLOBALS['ilUser'];
            };
        }

        self::initAccessHandling();
        // for UserDataHelper and sending notifications
        self::initHTML();
        self::initLanguage();
    }
}
