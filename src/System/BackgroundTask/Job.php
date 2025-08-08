<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\BackgroundTask;

use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ILIAS\BackgroundTasks\Types\Type;
use ilLongEssayAssessmentPlugin;

class Job extends AbstractJob
{
    /**
     * @param Value[] $input
     * @param Observer $observer
     * @return Value
     */
    public function run(array $input, Observer $observer): Value
    {
        $dic = ilLongEssayAssessmentPlugin::getInstance()->dic();

        // return $this->wrapScalar();
        $input = array_map(fn($x) => $x->getValue(), $input);
        $dic->{$input[0]}(...json_decode($input[1]))->backgroundTask($input[2])->run(...json_decode($input[3]));
        // (new ($input[0]))->run(...);

        return $this->wrapScalar(0);
    }

    public function getExpectedTimeOfTaskInSeconds(): int
    {
        return 60;
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
        return new SingleType(IntegerValue::class);
    }

    public function isStateless(): bool
    {
        return true;
    }
}
