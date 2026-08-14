<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Settings;

use Edutiek\AssessmentService\Assessment\Data\NotificationSettings;
use Edutiek\AssessmentService\Assessment\Data\NotificationUser;
use Edutiek\AssessmentService\Assessment\Notification\FullService as NotificationService;
use Edutiek\AssessmentService\System\Language\ReadService as LanguageService;
use Edutiek\AssessmentService\System\User\ReadService as UserService;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\DataTableParent;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;
use Generator;
use InvalidArgumentException;
use Edutiek\AssessmentService\Assessment\Data\NotificationType;
use Edutiek\AssessmentService\System\Entity\FullService as EntityService;
use Edutiek\AssessmentService\Assessment\Data\CorrectionProcedure as CorrectionProcedure;

/**
 * Notification settings
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Settings\NotificationSettingsGUI: ilObjLongEssayAssessmentGUI
 */
class NotificationSettingsGUI extends BaseGUI implements DataTableParent
{
    private NotificationService $notification;
    private LanguageService $service_lang;
    private UserService $users;
    private EntityService $entity_service;
    private CorrectionProcedure $procedure;
    private bool $is_fixed;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);

        $this->procedure = $this->assessment_api->correctionSettings()->get()->getProcedure();
        $this->notification = $this->assessment_api->notification();
        $this->entity_service = $this->system_api->entity();
        $this->service_lang = $this->assessment_api->language($this->user->getId());
        $this->users = $this->system_api->user();

        $this->is_fixed = $this->fixation_gui->isDisabled('tab_notifications', 'notification_settings');
    }

    public function executeCommand()
    {
        $this->initTools(false, true, 'tab_notifications');

        $cmd = $this->ctrl->getCmd('showItems');
        switch ($cmd) {
            case "showItems":
            case "editItem":
            case "updateItem":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    protected function showItems()
    {
        $table = $this->plugin_ui_factory->table()->dataTable("notification_settings", $this);
        $table->setTitle($this->plugin->txt('notification_settings'));
        $table->setDefaultLength(count($this->notification->availableSettings()));
        $table->executeAction();
        if ($this->is_fixed) {
            $table->disableAction(true);
        }
        $this->add($table)->show();
    }

    public function buildItem(NotificationSettings $setting, int $position): NotificationSettingsItem
    {
        return new NotificationSettingsItem(
            $setting->getId(),
            $setting->getType(),
            $setting->getActive(),
            $setting->getSubject(),
            $setting->getBody(),
            $position,
        );
    }

    private function buildFields(NotificationSettingsItem $item): array
    {
        $factory = $this->ui_factory->input()->field();
        $fields = [];

        $fields['active'] = $factory->checkbox(
            $this->plugin->txt('notification_active'),
            $this->plugin->txt('notification_active_info'),
        )->withValue($item->isActive());

        if ($item->getType()->hasConfiguredUsers()) {
            $fields['logins'] = $factory->text(
                $this->plugin->txt('notification_logins'),
                $this->plugin->txt('notification_logins_info'),
            )->withValue($this->userIdsToLoginList(
                array_map(
                    fn(NotificationUser $user) => $user->getUserId(),
                    $this->notification->usersByType($item->getType())
                )
            ));
        }

        $fields['subject'] = $factory->text(
            $this->plugin->txt('notification_subject'),
            $this->plugin->txt('notification_subject_info')
        )->withValue($item->getSubject());

        $fields['body'] = $factory->textarea(
            $this->plugin->txt('notification_body'),
            $this->plugin->txt('notification_body_info')
            . '<br />' . nl2br($this->notification->getPlaceholderInfo($item->getType()))
        )->withValue((string) $item->getBody());

        return ['settings' => $this->ui_factory->input()->field()->section(
            $fields,
            $this->service_lang->txt($item->getType()->titleLangVar($this->procedure)),
            $this->service_lang->txt($item->getType()->descriptionLangVar($this->procedure))
        )];
    }

    private function save(NotificationSettingsItem $item, array $data): void
    {
        $setting = $this->notification->settingsById($item->getId());
        if ($setting === null) {
            throw new InvalidArgumentException('Notification settings with ID ' . $item->getId() . ' not found');
        }

        $setting->setActive((bool) ($data['settings']['active'] ?? false));
        $setting->setSubject((string) ($data['settings']['subject'] ?? ''));
        $setting->setBody((string) ($data['settings']['body'] ?? ''));
        if ($setting->getType()->hasConfiguredUsers()) {
            $this->notification->saveUsers(
                $setting->getType(),
                $this->loginListToUserIds((string) ($data['settings']['logins']))
            );
        }

        $this->entity_service->secure($setting, NotificationSettings::class);
        $this->notification->saveSettings($setting);

        $this->success($this->lng->txt("settings_saved"), true);
        $this->ctrl->redirect($this, "showItems");
    }

    private function recipients(NotificationSettingsItem $item)
    {
        switch ($item->getType()) {
            case NotificationType::WRITER_CORRECTION_FINALIZED:
                return $this->service_lang->txt('writer');
            case NotificationType::CORRECTOR_AUTHORIZATION_REMOVED:
            case NotificationType::CORRECTOR_FIRST_AUTHORIZATION_REMOVED:
            case NotificationType::CORRECTOR_PROCEDURE_STARTED:
            case NotificationType::CORRECTOR_WRITING_CHANGED:
            case NotificationType::CORRECTOR_STITCH_NEEDED:
                return $this->service_lang->txt('corrector');
            default:
                if ($item->getType()->hasConfiguredUsers()) {
                    $list = $this->userIdsToLoginList(
                        array_map(
                            fn(NotificationUser $user) => $user->getUserId(),
                            $this->notification->usersByType($item->getType())
                        )
                    );
                    if (!empty($list)) {
                        return $list;
                    }
                }
        }
        return $this->plugin->txt('notification_recipient_empty');
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

    /**
     * @param NotificationSettingsItem $item
     */
    public function getColumnMapping(Item $item, ?array $additional_parameters): array|\ArrayAccess
    {
        return [
            "position" => $item->getPosition(),
            "type" => $this->service_lang->txt($item->getType()->titleLangVar($this->procedure)),
            "subject" => $item->getSubject(),
            "active" => $item->isActive(),
            'recipients' => $this->recipients($item)
        ];
    }

    public function getColumns(?array $additional_parameters): array
    {
        $tf = $this->ui_factory->table();

        return [
            "position" => $tf->column()->number($this->plugin->txt('notification_position'))->withIsSortable(false),
            "type" => $tf->column()->text($this->plugin->txt('notification_type'))->withIsSortable(false),
            "subject" => $tf->column()->text($this->plugin->txt('notification_subject'))->withIsSortable(false),
            "recipients" => $tf->column()->text($this->plugin->txt('notification_recipient'))->withIsSortable(false),
            "active" => $tf->column()->boolean($this->lng->txt('active'), $this->lng->txt('yes'), $this->lng->txt('no'))->withIsSortable(false),
        ];
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        return count($this->notification->availableSettings());
    }

    public function getTableActions(): array
    {
        return [$this->editAction()];
    }

    public function editAction()
    {
        return $this->plugin_ui_factory->table()->action()->form(
            "edit_item",
            $this->plugin->txt('edit_notification'),
            $this->lng->txt('save'),
            $this->buildFields(...),
            $this->save(...),
            fn(Item $item) => !$this->is_fixed,
            Action\Type::Single
        );
    }

    public function getTableItems(?array $ids = null, ?array $filter_data = null): Generator
    {
        // no ids and filter needed
        $pos = 1;
        foreach ($this->notification->availableSettings() as $setting) {
            if ($ids === null || in_array($setting->getId(), $ids)) {
                yield $this->buildItem($setting, $pos++);
            }
        }
    }

    public function getTableItem(int $id): Item
    {
        $settings = $this->notification->settingsById($id);
        if ($settings === null) {
            throw new InvalidArgumentException('Notification settings with ID ' . $id . ' not found');
        }
        return $this->buildItem($settings, 0);
    }
}
