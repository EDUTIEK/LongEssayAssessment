<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\BackgroundTask;

use Edutiek\AssessmentService\System\BackgroundTask\ClientManager as BackgroundTaskManager;
use ILIAS\BackgroundTasks\Implementation\Bucket\BasicBucket;
use ILIAS\BackgroundTasks\Task\TaskFactory;
use ILIAS\BackgroundTasks\TaskManager;
use ilObjUser;

class Manager implements BackgroundTaskManager
{
    public function __construct(
        private readonly TaskFactory $factory,
        private readonly TaskManager $manager,
        private readonly ilObjUser $user
    )
    {
    }

    public function run(string $component, array $component_args, string $title, string $job, ...$args): void
    {
        $task = $this->factory->createTask(Job::class, [$component, json_encode($component_args), $job, json_encode($args)]);
        // $interaction = $factory->createTask(ILIASInteractive::class, [$task]);

        $bucket = new BasicBucket();
        $bucket->setUserId($this->user->getId());
        $bucket->setTitle($title);
        $bucket->setTask($task);

        $this->manager->run($bucket);
    }
}
