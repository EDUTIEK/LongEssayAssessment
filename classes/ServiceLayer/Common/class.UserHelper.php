<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common;

use ILIAS\Plugin\LongEssayAssessment\BaseService;

use ilObjUser;

class UserHelper extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(
    ) {
        parent::__construct();

    }

    /**
     * Get the login names of users given by their ids
     * @param int[] $ids
     * @return string[]
     */
    public function getLoginsByIds(array $user_ids): array
    {
        $logins = [];
        foreach(ilObjUser::_getUserData($user_ids) as $data) {
            $logins[] = $data['login'];
        }
        return $logins;
    }
}