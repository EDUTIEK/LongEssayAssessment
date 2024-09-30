<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\DI\Container;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Types\Type;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\BooleanValue;
use ilObjectFactory;
use ilObjLongEssayAssessment;

class EssayImagesJob extends AbstractJob
{
    protected Container $dic;
    protected LongEssayAssessmentDI $local;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->local = LongEssayAssessmentDI::getInstance();
    }

    /**
     * @param Value[] $input
     * @param Observer $observer
     * @return Value
     */
    public function run(array $input, Observer $observer): Value
    {
        $ref_id = (int) $input[0]->getValue();
        $task_id = (int) $input[1]->getValue();
        $writer_id = (int) $input[2]->getValue();
        $essay_id = (int) $input[3]->getValue();
        $with_text = (bool) $input[4]->getValue();

        $object = ilObjectFactory::getInstanceByRefId($ref_id);

        $service = $this->local->getWriterAdminService($task_id);
        $writer = $this->local->getWriterRepo()->getWriterById($writer_id);
        $essay = $this->local->getEssayRepo()->getEssayById($essay_id);

        $count = 0;
        if (!$object instanceof ilObjLongEssayAssessment) {
            $this->dic->logger()->root()->error(sprintf(
                'LongEssayAssessment: object (ref_id %s) not found!',
                $ref_id
            ));
        } elseif ($writer === null) {
            $this->dic->logger()->root()->error(sprintf(
                'LongEssayAssessment: %s (ref_id %s) writer (writer_id %s) not found!',
                $object->getTitle(),
                $ref_id,
                $writer_id
            ));
        } elseif ($essay === null) {
            $this->dic->logger()->root()->error(sprintf(
                'LongEssayAssessment: %s (ref_id %s) %s (writer_id %s) essay (essay_id %s) not found!',
                $object->getTitle(),
                $ref_id,
                $writer->getPseudonym(),
                $writer_id,
                $essay_id
            ));
        } else {
            $count = $service->createEssayImages($object, $essay, $writer, $with_text);
            $this->dic->logger()->root()->info(sprintf(
                'LongEssayAssessment %s (ref_id %s) %s (writer_id %s) %s page images created.',
                $object->getTitle(),
                $object->getRefId(),
                $writer->getPseudonym(),
                $writer_id,
                $count
            ));
        }

        $output = new IntegerValue();
        $output->setValue($count);
        return $output;
    }

    public function isStateless(): bool
    {
        return false;
    }

    public function getExpectedTimeOfTaskInSeconds(): int
    {
        return 60;
    }

    /**
     * @return Type[]
     */
    public function getInputTypes(): array
    {
        return
            [
                new SingleType(IntegerValue::class),  // ref id
                new SingleType(IntegerValue::class),  // task id
                new SingleType(IntegerValue::class),  // writer id
                new SingleType(IntegerValue::class),  // essay id
                new SingleType(BooleanValue::class),  // with text
            ];

    }

    public function getOutputType(): Type
    {
        return new SingleType(IntegerValue::class);
    }
}
