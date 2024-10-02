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
use ILIAS\BackgroundTasks\Implementation\Bucket\State;
use ilLongEssayAssessmentPlugin;
use ilLogger;

class WriterPdfUploadBackgroundJob extends AbstractJob
{
    protected Container $dic;
    protected ilLogger $logger;
    protected LongEssayAssessmentDI $local;


    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->logger = $DIC->logger()->xlas();
        $this->local = LongEssayAssessmentDI::getInstance();
        ilLongEssayAssessmentPlugin::initAutoload();
    }

    /**
     * @param Value[] $input
     * @param Observer $observer
     * @return Value
     */
    public function run(array $input, Observer $observer): Value
    {
        $ref_id = (int) $input[0]->getValue();
        $essay_id = (int) $input[1]->getValue();

        $object = ilObjectFactory::getInstanceByRefId($ref_id);
        $essay = $this->local->getEssayRepo()->getEssayById($essay_id);
        $writer = $this->local->getWriterRepo()->getWriterById($essay->getWriterId());
        $service = $this->local->getWriterAdminService($essay->getTaskId());

        $success = new BooleanValue();
        $success->setValue(false);
        if (!$object instanceof ilObjLongEssayAssessment) {
            $this->logger->error(sprintf(
                'LongEssayAssessment: object (ref_id %s) not found!',
                $ref_id
            ));
        } elseif ($essay === null) {
            $this->logger->error(sprintf(
                'LongEssayAssessment: %s (ref_id %s) essay (essay_id %s) not found!',
                $object->getTitle(),
                $ref_id,
                $essay_id
            ));
        } elseif ($writer === null) {
            $this->logger->error(sprintf(
                'LongEssayAssessment: %s (ref_id %s) writer (writer_id %s) not found!',
                $object->getTitle(),
                $ref_id,
                $essay->getId()
            ));
        } else {
            $count = $service->createEssayImages($object, $essay, $writer, !empty($essay->getWrittenText()));
            $this->logger->info(sprintf(
                'LongEssayAssessment %s (ref_id %s) %s (writer_id %s) %s page images created.',
                $object->getTitle(),
                $object->getRefId(),
                $writer->getPseudonym(),
                $writer->getId(),
                $count
            ));
            $success->setValue(true);
        }

        $observer->notifyState(State::FINISHED);
        $observer->notifyPercentage($this, 100);
        return $success;
    }

    public function isStateless(): bool
    {
        return true;
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
                new SingleType(IntegerValue::class),  // essay id
            ];

    }

    public function getOutputType(): Type
    {
        return new SingleType(BooleanValue::class);
    }
}
