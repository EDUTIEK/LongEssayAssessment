<?php

namespace ILIAS\Plugin\LongEssayAssessment\Cron;

use ilCronJobResult;
use ILIAS\Cron\Schedule\CronJobScheduleType;
use ilLongEssayAssessmentPlugin;
use ilObjUser;

class ReviewNotification extends \ilCronJob
{
    /**
     * @var string
     * @see CronJobId::REVIEW_NOTIFICATION
     */
    public const id = 'xlas_review_notification';

    private ilLongEssayAssessmentPlugin $plugin;
    private ilObjUser $user;

    public function __construct()
    {
        global $DIC;
        $this->user = $DIC->user();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
    }

    public function getId(): string
    {
        return self::id;
    }

    public function getTitle(): string
    {
        return $this->plugin->txt('cron_review_notification_title');
    }

    public function getDescription(): string
    {
        return $this->plugin->txt('cron_review_notification_description');
    }

    public function hasAutoActivation(): bool
    {
        return false;
    }

    public function hasFlexibleSchedule(): bool
    {
        return true;
    }

    public function getDefaultScheduleType(): CronJobScheduleType
    {
        return CronJobScheduleType::SCHEDULE_TYPE_IN_HOURS;
    }

    public function getDefaultScheduleValue(): ?int
    {
        return 1;
    }

    public function run(): ilCronJobResult
    {
        $result = $this->plugin->dic()->cron($this->user->getId())->reviewNotifications()->run();

        if ($result->isOk()) {
            $cron_result = new ilCronJobResult();
            $cron_result->setStatus(ilCronJobResult::STATUS_OK);
        } else {
            $cron_result = new ilCronJobResult();
            $cron_result->setStatus(ilCronJobResult::STATUS_FAIL);
            $cron_result->setMessage(implode(' | ', $result->failures()));
        }

        return $cron_result;
    }
}
