<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\Cron\CronJob;
use ilCronJobResult;

class ReviewNotificationCronJob extends CronJob
{

    public static function id(): string
    {
        return "xlas_review_notification";
    }
    public function runJob(): ilCronJobResult
    {
        $this->global_dic->logger()->xlas()->debug("Review Notification CronJob started");
        $taskRepo = $this->local_dic->getTaskRepo();
        $objectRepo = $this->local_dic->getObjectRepo();
        $objects = \ilObject2::_getObjectsByType("xlas");
        $this->global_dic->logger()->xlas()->debug("Found " . count($objects) . " objects.");

        foreach ($objects as $object) {
            $settings = $objectRepo->getObjectSettingsById($object['obj_id']);
            if(!empty($settings) && $settings->isOnline()) {
                $service = $this->local_dic->getCorrectorAdminService($settings->getObjId());
                $valid_ref = null;
                foreach(\ilObject2::_getAllReferences($object['obj_id']) as $ref_id) {
                    $this->global_dic->logger()->xlas()->debug("Try to send Review Notifications with reference {$ref_id}.");
                    $service->sendPendingReviewNotifications($ref_id); // This is okay, because the access to a specific ref_id is checked and mails are only send once
                }
            }
        }
        $this->global_dic->logger()->xlas()->debug("Review Notification CronJob ended");
        $result = new ilCronJobResult();
        $result->setStatus(ilCronJobResult::STATUS_OK);
        return $result;
    }

    public function getId(): string
    {
        return self::id();
    }

    public function getTitle(): string
    {
        return $this->plugin->txt("review_notification_cron_job");
    }

    public function getDescription(): string
    {
        return $this->plugin->txt("review_notification_cron_job_desc");
    }

    public function hasAutoActivation(): bool
    {
        return true;
    }

    public function hasFlexibleSchedule(): bool
    {
        return true;
    }

    public function getDefaultScheduleType(): int
    {
        return self::SCHEDULE_TYPE_IN_HOURS;
    }

    public function getDefaultScheduleValue(): ?int
    {
        return 1;
    }


}
