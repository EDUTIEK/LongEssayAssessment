<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\Plugin\LongEssayAssessment\Cron\CronJob;
use ilCronJobResult;
use ILIAS\Cron\Schedule\CronJobScheduleType;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;
use ILIAS\Plugin\LongEssayAssessment\Task\ResourceResourceStakeholder;

class FileCleanupCronJob extends CronJob
{
    public static function id(): string
    {
        return "xlas_file_cleanup";
    }
    public function runJob(): ilCronJobResult
    {
        $this->global_dic->logger()->xlas()->debug("File Cleanup CronJob started");

        $this->deleteOrphanedData();
        $count = $this->cleanupStorageFiles();
        $count += $this->cleanupTempWebDir();

        $this->global_dic->logger()->xlas()->debug("File Cleanup CronJob finished");
        $result = new ilCronJobResult();
        $result->setStatus(ilCronJobResult::STATUS_OK);
        $result->setMessage($count . ' files deleted.');
        return $result;
    }

    public function getId(): string
    {
        return self::id();
    }

    public function getTitle(): string
    {
        return $this->plugin->txt("file_cleanup_cron_job");
    }

    public function getDescription(): string
    {
        return $this->plugin->txt("file_cleanup_cron_job_desc");
    }

    public function hasAutoActivation(): bool
    {
        return true;
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

    /**
     * Delete records from resources, essays, and essay images that are not used
     */
    private function deleteOrphanedData(): void
    {
        $tasks = $this->local_dic->getTaskRepo();
        $essays = $this->local_dic->getEssayRepo();

        $tasks->deleteOrphanedResources();
        $essays->deleteOrphanedEssays();
        $essays->deleteOrphanedEssayImages();
    }


    /**
     * Cleanup files in the resource storage that are no longer used by plugin objects
     */
    private function cleanupStorageFiles(): int
    {
        $tasks = $this->local_dic->getTaskRepo();
        $essays = $this->local_dic->getEssayRepo();

        $count = 0;
        $count += $this->cleanupStakeholderFiles(new ResourceResourceStakeholder(), $tasks->getResourceFileIds());
        $count += $this->cleanupStakeholderFiles(new EssayImageResourceStakeholder(), $essays->getEssayImageFileIds());
        $count += $this->cleanupStakeholderFiles(new PDFVersionResourceStakeholder(), $essays->getEssayFileIds());

        return $count;
    }

    /**
     * cleanup the unused files of a resource stakeholder
     * @param string[] $used_ids
     */
    public function cleanupStakeholderFiles(ResourceStakeholder $stakeholder, array $used_ids): int
    {
        $db = $this->global_dic->database();
        $manager = $this->global_dic->resourceStorage()->manage();

        $query = "SELECT DISTINCT i.rid FROM il_resource_info i JOIN il_resource_stkh_u u
                    WHERE u.rid = i.rid
                    AND u.stakeholder_id = %s
                    AND i.creation_date < %s";

        $result = $db->queryF($query, [\ilDBConstants::T_TEXT, \ilDBConstants::T_INTEGER], [$stakeholder->getId(), time() - (24 * 3600)]);

        $count = 0;
        while ($row = $result->fetchRow()) {
            if (!in_array($row['rid'], $used_ids)) {
                if (!empty($id = $manager->find($row['rid']))) {
                    $manager->remove($id, $stakeholder);
                    $count++;
                };
            }
        }
        return $count;
    }


    /**
     * Cleanup files in the web temp directory created by the plugin
     * @see \ILIAS\Plugin\LongEssayAssessment\ServiceContext::cleanupTempWebDir
     */
    private function cleanupTempWebDir(): int
    {
        $fs = $this->global_dic->filesystem()->web();
        if ($fs->hasDir('temp')) {
            $data = $fs->listContents('temp', false);
            $deleted = 0;
            foreach ($data as $file) {
                if (substr(basename($file->getPath()), 0, 3) == 'LAS') {
                    $ts = $fs->getTimestamp($file->getPath());
                    if ($ts->getTimestamp() < (time() - 3600)) {
                        $fs->delete($file->getPath());
                    }
                    $deleted++;
                }
            }
        }
        return $deleted;
    }
}
