<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ilLogger;
use ilLongEssayAssessmentPlugin;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\BooleanValue;
use ILIAS\BackgroundTasks\Implementation\Bucket\State;
use ILIAS\BackgroundTasks\Types\Type;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ilObjectFactory;
use ilObjLongEssayAssessment;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\Data\UUID\Factory as UUID;
use ilFileDelivery;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;

class CorrectorAdminCreateCorrectionsExportJob extends AbstractJob
{

    private Container $dic;
    private ilLogger $logger;
    private LongEssayAssessmentDI $localDI;
    private TaskRepository $taskRepo;
    private EssayRepository $essayRepo;
    private WriterRepository $writerRepo;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->logger = $DIC->logger()->xlas();
        $this->localDI = LongEssayAssessmentDI::getInstance();
        $this->taskRepo = $this->localDI->getTaskRepo();
        $this->essayRepo = $this->localDI->getEssayRepo();
        $this->writerRepo = $this->localDI->getWriterRepo();
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
        $object = ilObjectFactory::getInstanceByRefId($ref_id);

        $directory_to_zip = new StringValue();

        if (!$object instanceof ilObjLongEssayAssessment) {
            $this->logger->error(sprintf(
                'LongEssayAssessment: object (ref_id %s) not found!',
                $ref_id
            ));
        } else {
            // relative path in the tem storage
            $zipdir = 'xlas/'. (new UUID)->uuid4AsString() . '/' . ilFileDelivery::returnASCIIFilename($object->getTitle());

            $storage = $this->dic->filesystem()->temp();
            $storage->createDir($zipdir);

            $user_data_helper = $this->localDI->services()->common()->userDataHelper();
            $repoTask = $this->taskRepo->getTaskSettingsById($object->getId());
            $writerAdminService = $this->localDI->getWriterAdminService($repoTask->getTaskId());
            $correctorAdminService = $this->localDI->getCorrectorAdminService($repoTask->getTaskId());

            $correctorAdminService->createResultsExport($zipdir . '/results.csv');

            foreach ($this->essayRepo->getEssaysByTaskId($repoTask->getTaskId()) as $repoEssay) {
                if ($repoEssay->getWritingAuthorized()) {
                    $repoWriter = $this->writerRepo->getWriterById($repoEssay->getWriterId());

                    $subdir = ilFileDelivery::returnASCIIFilename($repoWriter->getPseudonym());
                    $storage->createDir($zipdir . '/' . $subdir);

                    $filename = $subdir . '-writing.pdf';
                    $storage->write($zipdir . '/' . $subdir. '/'. $filename, $writerAdminService->getWritingAsPdf($object, $repoWriter, true));

                    $filename = $subdir . '-correction.pdf';
                    $storage->write($zipdir . '/' . $subdir. '/'. $filename, $correctorAdminService->getCorrectionAsPdf($object, $repoWriter, null, false, true));
                }
            }

            $directory_to_zip->setValue(ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp/' . $zipdir);
        }

        $observer->notifyState(State::FINISHED);
        $observer->notifyPercentage($this, 100);

        return $directory_to_zip;
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
        return [
            new SingleType(IntegerValue::class),  // ref_id
        ];
    }

    public function getOutputType(): Type
    {
        return new SingleType(StringValue::class); // absolute path of directory to zip
    }
}