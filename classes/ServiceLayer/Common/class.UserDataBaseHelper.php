<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common;

class UserDataBaseHelper
{
    private array $user_data = [];

    public function __construct()
    {
    }

    public function preload(array $usr_ids)
    {
        $this->loadUserData($usr_ids);
    }

    public function getNames(array $user_ids) : array
    {
        $this->loadUserData($user_ids);
        $names = [];

        foreach($user_ids as $user_id) {
            if(is_int($user_id)) {
                $names[$user_id] = $this->getPresentation($user_id);
            }
        }
        return $names;
    }

    /**
     * Get the login names of users given by their ids
     * @param int[] $user_ids
     * @return string[]
     */
    public function getLogins(array $user_ids): array
    {
        $this->loadUserData($user_ids);

        $logins = [];
        foreach($user_ids as $user_id) {
            if(is_int($user_id)) {
                $logins[$user_id] = $this->getLogin($user_id);
            }
        }
        return $logins;
    }

    public function getFullname(int $user_id, string $default = "") : string
    {
        $row = $this->getUserData($user_id);

        if($row === null) {
            return $default;
        }

        return ($row->title ? $row->title . " " : "") . ($row->firstname ? $row->firstname . " " : "") . ($row->lastname ?? "");
    }

    public function getLogin(int $user_id, string $default = "") : string
    {
        $row = $this->getUserData($user_id);

        if($row === null) {
            return $default;
        }
        return $row->login;
    }
    public function getPresentation(int $user_id, bool $omit_login = false, string $default = "") : string
    {
        $row = $this->getUserData($user_id);

        if($row === null) {
            return $default;
        }

        $pres = '';
        $title = '';
        if ($row->title) {
            $title = $row->title . ' ';
        }

        $pres = $title . $row->lastname;
        if (strlen($row->firstname)) {
            $pres .= (', ' . $row->firstname . ' ');
        }

        if (!($omit_login)) {
            $pres .= '[' . $row->login . ']';
        }

        return $pres;
    }

    public function getUserData(int $user_id) : ?object
    {
        if(!array_key_exists($user_id, $this->user_data)) {
            $this->loadUserData([$user_id]);
            if(!array_key_exists($user_id, $this->user_data)) {
                return null;
            }
        }
        return $this->user_data[$user_id];
    }


    /**
     * Load needed Usernames From DB
     */
    private function loadUserData(array $user_ids)
    {
        $user_ids = array_diff(array_unique($user_ids), array_keys($this->user_data));
        $user_data = \ilUserUtil::getNamePresentation($user_ids, true, true, "", true, false, true, true);

        foreach($user_data as $user) {
            $this->user_data[$user['id']] = new class(...$user) {
                public function __construct(
                    public int $id,
                    public string $title,
                    public string $lastname,
                    public string $firstname,
                    public string $img,
                    public string $link,
                    public string $login,
                    public bool $public_profile
                ) {
                }
            };
        }
    }
}
