<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\BackgroundTask;

use Edutiek\AssessmentService\System\BackgroundTask\SystemManager;
use ILIAS\BackgroundTasks\Implementation\Bucket\BasicBucket;
use ILIAS\BackgroundTasks\Task\TaskFactory;
use ILIAS\BackgroundTasks\TaskManager;
use ilObjUser;
use Edutiek\AssessmentService\System\BackgroundTask\ComponentJob;

class Manager implements SystemManager
{
    public function __construct(
        private readonly TaskFactory $factory,
        private readonly TaskManager $manager,
        private readonly ilObjUser $user
    ) {
    }

    public function create(string $title, string $component, string $job, array $component_args, array $service_args, array $job_args): void
    {
        $task = $this->factory->createTask(Job::class, [
            $component, $job, json_encode($component_args), json_encode($service_args), json_encode($job_args)
        ]);

        $bucket = new BasicBucket();
        $bucket->setUserId($this->user->getId());
        $bucket->setTitle($title);

        if (is_a($job, ComponentJob::class, true) && $job::withDownload()) {
            $download = $this->factory->createTask(Download::class, [$task]);
            $bucket->setTask($download);
        } else {
            $bucket->setTask($task);
        }

        $this->manager->run($bucket);
    }
}
