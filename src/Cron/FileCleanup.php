<?php

namespace ILIAS\Plugin\LongEssayAssessment\Cron;

use ilCronJobResult;
use ILIAS\Cron\Schedule\CronJobScheduleType;
use ilLongEssayAssessmentPlugin;
use ilObjUser;

class FileCleanup extends \ilCronJob
{
    /**
     * @var string
     * @see CronJobId::FILE_CLEANUP
     */
    public const id = 'xlas_file_cleanup';

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
        return $this->plugin->txt('cron_file_cleanup_title');
    }

    public function getDescription(): string
    {
        return $this->plugin->txt('cron_file_cleanup_description');
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
        return CronJobScheduleType::SCHEDULE_TYPE_IN_DAYS;
    }

    public function getDefaultScheduleValue(): ?int
    {
        return 1;
    }

    public function run(): ilCronJobResult
    {
        $result = $this->plugin->dic()->cron($this->user->getId())->fileCleanupHandler()->run();

        if ($result->isOk()) {
            $cron_result = new ilCronJobResult();
            $cron_result->setStatus(ilCronJobResult::STATUS_OK);
            $cron_result->setMessage(implode(' | ', $result->notes()));
        } else {
            $cron_result = new ilCronJobResult();
            $cron_result->setStatus(ilCronJobResult::STATUS_FAIL);
            $cron_result->setMessage(implode(' | ', $result->failures()));
        }

        return $cron_result;
    }
}
