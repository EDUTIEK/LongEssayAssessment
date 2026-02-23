<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\BackgroundTask;

use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Types\Type;
use ilLongEssayAssessmentPlugin;

class Job extends AbstractJob
{
    /**
     * Run a cron job of an assessment system component
     *
     * @param Value[] $input
     * @param Observer $observer
     * @return Value    wrapped file id if the job creates a file
     *
     * @todo: component name is implicitly used as a dic function to create the component
     */
    public function run(array $input, Observer $observer): Value
    {
        $dic = ilLongEssayAssessmentPlugin::getInstance()->dic();

        $component = $input[0]->getValue();                     // component that runs the job e.g. 'essayTask'
        $job = $input[1]->getValue();                           // class name of the job in the component e.g. 'GenerateEssayImages'

        $component_args = json_decode($input[2]->getValue());   // args to initialize the component e.g. ass_id, user_id
        $service_args = json_decode($input[3]->getValue());     // args to initialize the service e.g. context_id
        $args = json_decode($input[4]->getValue());             // args provided for the run() function of the job

        $file_id = $dic->{$component}(...$component_args)->backgroundTasks(...$service_args)->run($job, $args);

        // job may have created a file for download
        return $this->wrapScalar((string) $file_id);
    }

    public function getExpectedTimeOfTaskInSeconds(): int
    {
        return 3600;
    }

    public function getInputTypes(): array
    {
        return [
            new SingleType(StringValue::class),
            new SingleType(StringValue::class),
            new SingleType(StringValue::class),
            new SingleType(StringValue::class),
        ];

    }

    public function getOutputType(): Type
    {
        return new SingleType(StringValue::class);
    }

    public function isStateless(): bool
    {
        return true;
    }
}
