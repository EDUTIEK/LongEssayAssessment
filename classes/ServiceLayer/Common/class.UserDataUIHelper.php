<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common;

use ILIAS\UI\Component\Symbol\Icon\Icon;
use ILIAS\UI\Component\Image\Image;
use ILIAS\UI\Component\Component;

class UserDataUIHelper
{
    private \ilLanguage $lng;
    private \ILIAS\UI\Factory $uiFactory;
    private UserDataBaseHelper $userDataHelper;

    public function __construct(
        \ilLanguage $lng,
        \ILIAS\UI\Factory $uiFactory,
        UserDataBaseHelper $userDataHelper
    ) {
        $this->lng = $lng;
        $this->uiFactory = $uiFactory;
        $this->userDataHelper = $userDataHelper;
    }

    public function getUserIcon(int $user_id, ?Icon $default = null): ?Icon
    {
        $row = $this->userDataHelper->getUserData($user_id);

        if($row === null) {
            return $default;
        }

        return $this->uiFactory->symbol()->icon()->custom($row->img, $this->lng->txt('icon') . ' ' . $this->lng->txt('user_picture'), "medium");

    }

    public function getUserImage(int $user_id, ?Image $default = null): ?Image
    {
        $row = $this->userDataHelper->getUserData($user_id);

        if($row === null) {
            return $default;
        }

        return $this->uiFactory->image()->standard($row->img, $this->lng->txt('icon') . ' ' . $this->lng->txt('user_picture'));
    }

    public function getUserProfileLink(int $user_id, string $profile_back_link = '', bool $use_legacy = true, ?Component $default = null) : ?Component
    {
        $row = $this->userDataHelper->getUserData($user_id);

        if($row === null) {
            return $default;
        }

        if ($row->public_profile) {
            $back = '';
            if ($profile_back_link != '') {
                $back = '?back_url=' . rawurlencode($profile_back_link);
            }
            return $this->uiFactory->link()->standard($this->userDataHelper->getPresentation($user_id), $row->link . $back);
        }
        return $use_legacy ? $this->uiFactory->legacy($this->userDataHelper->getPresentation($user_id)) : $default;
    }
}
