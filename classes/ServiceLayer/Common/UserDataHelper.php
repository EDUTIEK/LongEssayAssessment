<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common;

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\UI\Component\Symbol\Icon\Icon;
use ILIAS\UI\Component\Image\Image;
use ILIAS\UI\Component\Component;

class UserDataHelper
{
    /**
     * @var array
     */
    private array $user_data = [];

    private \ilLanguage $lng;
    private \ILIAS\UI\Factory $uiFactory;

    public function __construct(
        \ilLanguage $lng,
        \ILIAS\UI\Factory $uiFactory
    ) {
        $this->lng = $lng;
        $this->uiFactory = $uiFactory;
    }

    public function preload(array $usr_ids)
    {
        $this->loadUserData($usr_ids);
    }

    public function getUserIcon(int $user_id, ?Icon $default = null): ?Icon
    {
        $row = $this->getUserData($user_id);

        if($row === null) {
            return $default;
        }

        return $this->uiFactory->symbol()->icon()->custom($row->img, $this->lng->txt('icon') . ' ' . $this->lng->txt('user_picture'), "medium");

    }

    public function getUserImage(int $user_id, ?Image $default = null): ?Image
    {
        $row = $this->getUserData($user_id);

        if($row === null) {
            return $default;
        }

        return $this->uiFactory->image()->standard($row->img, $this->lng->txt('icon') . ' ' . $this->lng->txt('user_picture'));
    }

    public function getUserProfileLink(int $user_id, string $profile_back_link = '', bool $use_legacy = true, ?Component $default = null) : ?Component
    {
        $row = $this->getUserData($user_id);

        if($row === null) {
            return $default;
        }

        if ($row->public_profile) {
            $back = '';
            if ($profile_back_link != '') {
                $back = '?back_url=' . rawurlencode($profile_back_link);
            }
            return $this->uiFactory->link()->standard($this->getPresentation($user_id), $row->link . $back);
        }
        return $use_legacy ? $this->uiFactory->legacy($this->getPresentation($user_id)) : $default;
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

    private function getUserData(int $user_id) : ?object
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
