<?php

namespace ILIAS\Plugin\LongEssayAssessment\Cron;

use Edutiek\AssessmentService\System\Config\CronJobId;
use ilLongEssayAssessmentPlugin;
use ilObjUser;
use ILIAS\Cron\CronJob;
use ILIAS\Cron\Job\Schedule\JobScheduleType;
use ILIAS\Cron\Job\JobResult;

class ReviewNotification extends CronJob
{
    public const id = CronJobId::REVIEW_NOTIFICATION->value;

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

    public function getDefaultScheduleType(): JobScheduleType
    {
        return JobScheduleType::IN_HOURS;
    }

    public function getDefaultScheduleValue(): ?int
    {
        return 1;
    }

    public function run(): JobResult
    {
        $result = $this->plugin->dic()->cron($this->user->getId())->reviewNotifications()->run();

        if ($result->isOk()) {
            $cron_result = new JobResult();
            $cron_result->setStatus(JobResult::STATUS_OK);
        } else {
            $cron_result = new JobResult();
            $cron_result->setStatus(JobResult::STATUS_FAIL);
            $cron_result->setMessage(implode(' | ', $result->failures()));
        }

        return $cron_result;
    }
}
