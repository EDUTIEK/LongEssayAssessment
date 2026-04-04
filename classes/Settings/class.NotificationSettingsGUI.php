<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\Assessment\Data\NotificationUser;
use Edutiek\AssessmentService\Assessment\Notification\FullService as NotificationService;
use Edutiek\AssessmentService\System\Language\ReadService as LanguageService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\UI\Component\Input\Container\Form\Standard;

/**
 * Notification settings
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\NotificationSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class NotificationSettingsGUI extends BaseGUI
{
    private NotificationService $notification;
    private LanguageService $lang;
    private UserService $users;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->notification = $this->assessment_api->notification();
        $this->lang = $this->assessment_api->language($this->user->getId());
        $this->users = $this->system_api->user();
    }

    public function executeCommand()
    {
        $this->initTools(false, true);

        $cmd = $this->ctrl->getCmd('editSettings');
        switch ($cmd) {
            case "editSettings":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    protected function editSettings()
    {
        $form = $this->buildForm();
        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $data = $form->getData();

            $result = $form->getInputGroup()->getContent();
            if ($result->isOK()) {
                $this->updateSettings($data);
            }
        }

        $this->add($form)->show();
    }

    private function buildForm(): Standard
    {
        $factory = $this->ui_factory->input()->field();

        $fields = [];
        foreach ($this->notification->allSettings() as $setting) {
            $sub = [];

            if ($setting->getType()->hasConfiguredUsers()) {
                $sub['logins'] = $factory->text(
                    $this->plugin->txt('notification_logins'),
                    $this->plugin->txt('notification_logins_info'),
                )->withValue($this->userIdsToLoginList(
                    array_map(
                        fn(NotificationUser $user) => $user->getUserId(),
                        $this->notification->usersByType($setting->getType())
                    )
                ));
            }

            $sub['subject'] = $factory->text(
                $this->plugin->txt('notification_subject'),
                $this->plugin->txt('notification_subject_info')
            )->withValue($setting->getSubject());

            $sub['body'] = $factory->textarea(
                $this->plugin->txt('notification_body'),
                $this->plugin->txt('notification_body_info')
                . '<br />' . nl2br($this->notification->getPlaceholderInfo($setting->getType()))
            )->withValue($setting->getBody());

            $fields[$setting->getType()->value] = $factory->optionalGroup(
                $sub,
                $this->lang->txt($setting->getType()->titleLangVar()),
                $this->lang->txt($setting->getType()->descriptionLangVar())
            );
            // strange but effective
            if (!$setting->isActive()) {
                $fields[$setting->getType()->value] = $fields[$setting->getType()->value]->withValue(null);
            }
        }

        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this),
            $fields
        );
    }

    private function updateSettings(array $data): void
    {
        foreach ($this->notification->allSettings() as $setting) {
            $sub = $data[$setting->getType()->value] ?? null;
            if (is_array($sub)) {
                $setting->setActive(true);
                $setting->setSubject((string) $sub['subject']);
                $setting->setBody((string) $sub['body']);
                $this->notification->saveSettings($setting);

                if ($setting->getType()->hasConfiguredUsers()) {
                    $this->notification->saveUsers(
                        $setting->getType(),
                        $this->loginListToUserIds((string) $sub['logins'])
                    );
                }
            } else {
                $setting->setActive(false);
                $this->notification->saveSettings($setting);
            }
        }

        $this->success($this->lng->txt("settings_saved"), true);
        $this->ctrl->redirect($this, "editSettings");
    }

    /**
     * @param int[] $user_ids
     */
    private function userIdsToLoginList(array $user_ids): string
    {
        $logins = [];
        foreach ($user_ids as $user_id) {
            $login = $this->users->getLoginByUserId($user_id);
            if (!empty($login)) {
                $logins[] = $login;
            }
        }
        return implode(', ', $logins);
    }

    /**
     * @return int[]
     */
    private function loginListToUserIds(string $logins): array
    {
        $ids = [];
        foreach ($this->splitList($logins) as $login) {
            $id = $this->users->getUserIdByLogin($login);
            if (!empty($id)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * @return string[]
     */
    private function splitList(string $list): array
    {
        $entries = [];
        $raw = explode(",", $list);
        foreach ($raw as $entry) {
            $entry = trim($entry);
            if (!empty($entry)) {
                $entries[] = $entry;
            }
        }
        return $entries;
    }
}
