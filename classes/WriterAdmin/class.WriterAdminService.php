<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use Edutiek\LongEssayAssessmentService\Data\WritingStep;
use Edutiek\LongEssayAssessmentService\Writer\Service;
use ILIAS\Data\UUID\Factory as UUID;
use ILIAS\Plugin\LongEssayAssessment\BaseService;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Alert;
use ILIAS\Plugin\LongEssayAssessment\Data\DataService;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Writer\WriterContext;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayImage;
use ilObjLongEssayAssessment;
use ILIAS\Plugin\LongEssayAssessment\Task\LoggingService;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Filesystem\Filesystem;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common\FileHelper;
use ILIAS\BackgroundTasks\Implementation\Bucket\BasicBucket;
use ILIAS\BackgroundTasks\Implementation\TaskManager\AsyncTaskManager;
use ILIAS\ResourceStorage\Services;
use ILIAS\BackgroundTasks\Implementation\Tasks\NotFoundUserInteraction;

class WriterAdminService extends BaseService
{
    protected WriterRepository $writerRepo;
    protected CorrectorRepository $correctorRepo;
    protected EssayRepository $essayRepo;
    protected TaskRepository $taskRepo;
    protected DataService $dataService;
    protected LoggingService $loggingService;

    protected Filesystem $temp_fs;
    protected FileHelper $file_helper;
    protected Services $resource_storage;

    protected int $task_id;

    /**
     * Constructor
     */
    public function __construct(int $task_id)
    {
        parent::__construct();
        $this->task_id = $task_id;

        $this->writerRepo = $this->localDI->getWriterRepo();
        $this->correctorRepo = $this->localDI->getCorrectorRepo();
        $this->essayRepo = $this->localDI->getEssayRepo();
        $this->taskRepo = $this->localDI->getTaskRepo();
        $this->dataService = $this->localDI->getDataService($this->task_id);
        $this->loggingService = $this->localDI->getLoggingService($this->task_id);

        $this->resource_storage = $this->dic->resourceStorage();
        $this->temp_fs = $this->dic->filesystem()->temp();
        $this->file_helper = $this->localDI->services()->common()->fileHelper();
    }

    /**
     * Get a writer object for an ILIAS user
     * A new writer object is not yet saved, it must be saved with saveNewWriter
     * @param int $user_id
     * @return Writer
     * @see saveNewWriter
     */
    public function getWriterFromUserId(int $user_id) : Writer
    {
        $writer = $this->writerRepo->getWriterByUserIdAndTaskId($user_id, $this->task_id);
        if (!isset($writer)) {
            $writer = new Writer();
            $writer->setUserId($user_id)
                   ->setTaskId($this->task_id);
        }
        return $writer;
    }

    /**
     * save a new writer object
     * This sets the pseudonym which is based on the writer id
     */
    public function saveNewWriter(Writer $writer) : Writer
    {
        $writer->setPseudonym('');
        $this->writerRepo->save($writer);
        // now the id is known
        $writer->setPseudonym($this->plugin->txt('participant') . ' ' .$writer->getId());
        $this->writerRepo->save($writer);
        return $writer;
    }

    /**
     * Get or create a writer object for an ILIAS user
     * A new writer object is already saved
     */
    public function getOrCreateWriterFromUserId(int $user_id) : Writer
    {
        $writer = $this->getWriterFromUserId($user_id);
        if (empty($writer->getId())) {
            $this->saveNewWriter($writer);
        }
        return $writer;
    }

    /**
     * Get an initialized essay for a writer which may not yet be saved
     * @param Writer $writer
     * @return Essay
     */
    public function getEssayForWriter(Writer $writer) : Essay
    {
        $essay = $this->essayRepo->getEssayByWriterIdAndTaskId($writer->getId(), $writer->getTaskId());
        if (!isset($essay)) {
            $essay = new Essay();
            $essay->setWriterId($writer->getId())
                  ->setTaskId($writer->getTaskId())
                  ->setUuid($essay->generateUUID4())
                  ->setRawTextHash('');
        }
        return $essay;
    }

    /**
     * Get an initialized and saved essay for a writer
     * @param Writer $writer
     * @return Essay
     */
    public function getOrCreateEssayForWriter(Writer $writer) : Essay
    {
        $essay = $this->getEssayForWriter($writer);
        if (empty($essay->getId())) {
            $this->essayRepo->save($essay);
        }
        return $essay;
    }

    /**
     * Get the writing of an essay as PDF string
     */
    public function getWritingAsPdf(ilObjLongEssayAssessment $object, Writer $repoWriter, bool $anonymous = false, bool $rawContent = false) : string
    {
        $context = new WriterContext();
        $context->init((string) $repoWriter->getUserId(), (string) $object->getRefId());

        $writingTask = $context->getWritingTask();
        if ($anonymous) {
            $writingTask = $writingTask->withWriterName($repoWriter->getPseudonym());
        }
        $writtenEssay = $context->getWrittenEssay();

        $service = new Service($context);
        return $service->getWritingAsPdf($writingTask, $writtenEssay, $rawContent);
    }


    /**
     * Create an export file for the writing steps
     * @param \ilObjLongEssayAssessment $object
     * @param Writer $repoWriter
     * @param string $dirname   name of the directory inside the zip file
     * @return string path to the zip file or null if the export can't be produced
     */
    public function createWritingStepsExport(\ilObjLongEssayAssessment $object, Writer $repoWriter, string $dirname) : ?string
    {
        $repoEssay = $this->essayRepo->getEssayByWriterIdAndTaskId($repoWriter->getId(), $object->getId());
        if (empty($repoEssay)) {
            return null;
        }

        $context = new WriterContext();
        $context->init((string) $repoWriter->getUserId(), (string) $object->getRefId());
        $service = new Service($context);

        $storage = $this->dic->filesystem()->temp();
        $basedir = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
        $tempdir = 'xlas/'. (new UUID())->uuid4AsString();
        $zipdir = $tempdir . '/' . $dirname;
        $storage->createDir($zipdir);

        $before = '';
        $toc = '';
        $steps = $this->essayRepo->getWriterHistoryStepsByEssayId($repoEssay->getId());
        $index = 0;
        foreach ($steps  as $step) {
            $filename = 'step' .  sprintf('%09d', $index) . '.html';

            $nav = '<a href="index.html">Index</a> | Step ' . $index . ' ('. $step->getTimestamp() . ')';
            if ($index > 0) {
                $nav .= ' | <a href="step' . sprintf('%09d', $index -1) . '.html">Previous</a>';
            }
            if ($index < count($steps) - 1) {
                $nav .= ' | <a href="step' . sprintf('%09d', $index +1) . '.html">Next</a>';
            }

            $toc .= '<a href="step' . sprintf('%09d', $index) . '.html">Step ' . $index . '</a> '
                    . ' ('. $step->getTimestamp() . ')';
            if ($step->isIsDelta()) {
                $toc .= " - Incremental<br>\n";
            } else {
                $toc .= " - Full<br>\n";
            }

            $writingStep = new WritingStep(
                $this->dataService->dbTimeToUnix($step->getTimestamp()),
                $step->getContent(),
                $step->isIsDelta(),
                $step->getHashBefore(),
                $step->getHashAfter()
            );

            $html = $nav .'<hr>' . $service->getWritingDiffHtml($before, $writingStep);
            $storage->write($zipdir . '/'. $filename, $html);

            $before = $service->getWritingDiffResult($before, $writingStep);
            $index++;
        }
        $storage->write($zipdir . '/index.html', $toc);

        $zipfile = $basedir . '/' . $tempdir . '/export.zip';
        \ilFileUtils::zip($basedir . '/' . $zipdir, $zipfile);

        $storage->deleteDir($zipdir);
        return $zipfile;

        // check if that can be used without abolute path
        // then also the tempdir can be deleted
        //$delivery = new \ilFileDelivery()
    }


    /**
     * Get all writers by their writing status
     * @return Writer[][] (indexed by status and writer id)
     * @see DataService::writingStatus()
     */
    public function getWritersByStatus()
    {

        $essays = [];
        foreach ($this->essayRepo->getEssaysByTaskId($this->task_id) as $essay) {
            $essays[$essay->getWriterId()] = $essay;
        }

        $writers = [
            Essay::WRITING_STATUS_EXCLUDED => [],
            Essay::WRITING_STATUS_NOT_WRITTEN => [],
            Essay::WRITING_STATUS_NOT_AUTHORIZED => [],
            Essay::WRITING_STATUS_AUTHORIZED => [],
        ];
        foreach($this->writerRepo->getWritersByTaskId($this->task_id) as $writer) {
            $status = $this->dataService->writingStatus($essays[$writer->getId()] ?? null);
            $writers[$status][$writer->getId()] = $writer;
        }
        return $writers;
    }


    /**
     * Count the writers that still can authorize their essay
     *
     * @return int[]     [not started, writing possible, writing ended]
     */
    public function countPotentialAuthorizations() : array
    {
        $settings = $this->taskRepo->getTaskSettingsById($this->task_id);
        $writing_end = $this->dataService->dbTimeToUnix($settings->getWritingEnd());

        $extensions = [];
        foreach ($this->writerRepo->getTimeExtensionsByTaskId($this->task_id) as $extension) {
            $extensions[$extension->getWriterId()] = $extension;
        }

        $writers = $this->getWritersByStatus();

        /** @var Writer[] $open */
        $open = array_merge($writers[Essay::WRITING_STATUS_NOT_WRITTEN], $writers[Essay::WRITING_STATUS_NOT_AUTHORIZED]);

        $before = 0;
        $writing = 0;
        $after = 0;

        foreach($open as $writer) {
            $writing_over = false;
            if (isset($writing_end)) {
                $individual_end = $writing_end;
                if (!empty($extension = ($extensions[$writer->getId()]) ?? null)) {
                    $individual_end += 60 * $extension->getMinutes();
                }

                if ($individual_end <= time()) {
                    $writing_over = true;
                }
            }

            if (isset($writers[Essay::WRITING_STATUS_NOT_WRITTEN][$writer->getId()])) {
                if (!$writing_over) {
                    $before++;
                }
            } elseif (!$writing_over) {
                $writing++;
            } else {
                $after++;
            }
        }

        return [$before, $writing, $after];
    }

    public function authorizeWriting(Essay $essay, int $user_id)
    {
        $datetime = new \ilDateTime(time(), IL_CAL_UNIX);
        if (empty($essay->getEditStarted())) {
            $essay->setEditStarted($datetime->get(IL_CAL_DATETIME));
        }
        $essay->setWritingAuthorized($datetime->get(IL_CAL_DATETIME));
        $essay->setWritingAuthorizedBy($user_id);

        $this->essayRepo->save($essay);

        $writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
        $writer = $writer_repo->getWriterById($essay->getWriterId());
        $this->loggingService->addEntry(LogEntry::TYPE_WRITING_POST_AUTHORIZED, $user_id, $writer->getUserId());
    }


    public function removeAuthorizationWriting(Essay $essay, int $user_id)
    {
        if($essay->getWritingAuthorized() !== null) { // Only actively remove authorization if there was any before
            $essay->setWritingAuthorized(null);
            $essay->setWritingAuthorizedBy(null);

            $this->essayRepo->save($essay);

            $writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
            $writer = $writer_repo->getWriterById($essay->getWriterId());
            $this->loggingService->addEntry(LogEntry::TYPE_WRITING_REMOVE_AUTHORIZATION, $user_id, $writer->getUserId());
        }
    }


    public function handlePDFVersionInput(int $ref_id, Essay $essay, ?string $new_file_id)
    {
        $temp_file = $this->localDI->getUploadTempFile();
        $saved_file_id = $essay->getPdfVersion();

        if ($new_file_id === $saved_file_id) {
            return;
        }

        $resource_id = $saved_file_id ? $this->resource_storage->manage()->find($saved_file_id) : null;

        if ($resource_id === null && $new_file_id !== null) {
            $resource_id = $temp_file->storeTempFileInResources($new_file_id, new PDFVersionResourceStakeholder());
        }
        elseif ($resource_id !== null && $new_file_id !== null) {
            $temp_file->replaceTempFileWithResource($new_file_id, $resource_id, new PDFVersionResourceStakeholder());
        }
        elseif ($resource_id !== null && $new_file_id == null) {
            $this->resource_storage->manage()->remove($resource_id, new PDFVersionResourceStakeholder());
            $resource_id = null;
        }

        $essay->setPdfVersion($resource_id !== null ? (string) $resource_id : null);
        $this->essayRepo->save($essay);
        $this->removeEssayImages($essay->getId());
        $this->purgeCorrectorComments($essay);

        // create page images in background task
        if ($resource_id !== null) {
            $factory = $this->dic->backgroundTasks()->taskFactory();
            $manager = $this->dic->backgroundTasks()->taskManager();

            $task = $factory->createTask(WriterPdfUploadBackgroundJob::class, [$ref_id, $essay->getId()]);
            $interaction = $factory->createTask(WriterPdfUploadBackgroundInteraction::class, [$task, $ref_id, $essay->getId()]);

            $bucket = new BasicBucket();
            $bucket->setUserId($this->dic->user()->getId());
            $bucket->setTitle(sprintf($this->plugin->txt('writer_upload_pdf_bt_processing'),
                $this->resource_storage->manage()->getResource($resource_id)->getCurrentRevision()->getTitle()));
            $bucket->setTask($interaction);

            $manager->run($bucket);
        }
    }

    public function hasCorrectorComments(Essay $essay) : bool
    {
        $essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
        return !empty($essay_repo->getCorrectorCommentsByEssayIdAndCorrectorId($essay->getId(), null));
    }
    
    public function purgeCorrectorComments(Essay $essay)
    {
        $essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
        $essay_repo->deleteCorrectorCommentByEssayId($essay->getId());
    }
    
    public function createPdfFromText(ilObjLongEssayAssessment $object, Essay $essay, Writer $writer)
    {
        $essay_repo = LongEssayAssessmentDI::getInstance()->getEssayRepo();
        
        if (empty($essay->getPdfVersion()) && !empty($essay->getWrittenText())) {
            $content = $this->getWritingAsPdf($object, $writer, true, true);
            $stream = Streams::ofString($content);
            $file_id = $this->resource_storage->manage()->stream($stream, new PDFVersionResourceStakeholder(), $this->plugin->txt('pdf_from_text'));
            $essay->setPdfVersion((string) $file_id);
            $essay_repo->save($essay);
            $this->authorizeWriting($essay, $this->dic->user()->getId());
                
            // text is put into the created PDF, so it does not need to be added to the images
            $this->createEssayImages($object, $essay, $writer, false);
        }
    }

    /**
     * Get the page images of an essay with pdf version, create them if they don't yet exist
     * @return EssayImage[]
     */
    public function getOrCreateEssayImages(ilObjLongEssayAssessment $object, Essay $essay): array
    {
        if ($essay->getPdfVersion() === null) {
            return [];
        }

        $images = $this->essayRepo->getEssayImagesByEssayID($essay->getId());
        if (empty($images)) {
            $writer = $this->writerRepo->getWriterById($essay->getWriterId());
            if ($writer !== null) {
                $this->createEssayImages($object, $essay, $writer, !empty($essay->getWrittenText()));
                $images = $this->essayRepo->getEssayImagesByEssayID($essay->getId());
            }
        }
        return $images;
    }

    /**
     * Create images from the essay text or an uploaded PDF
     * @param bool $with_text add the written text to the images
     * @return int  number of created images
     */
    public function createEssayImages(ilObjLongEssayAssessment $object, Essay $essay, Writer $writer, bool $with_text = true) : int
    {
        $pdfs = [];
        if (!empty($essay->getPdfVersion())) {

            if ($with_text && !empty($essay->getWrittenText())) {
                $fs = $this->dic->filesystem()->temp();
                $writing_pdf = 'xlas/' . (new UUID())->uuid4AsString() . '.pdf';
                $fs->put($writing_pdf, $this->getWritingAsPdf($object, $writer, true, true));
                $pdfs[] = $fs->readStream($writing_pdf)->detach();
            }
            
            $resource_id = $this->resource_storage->manage()->find($essay->getPdfVersion());
            if (!empty($resource_id)) {
                $pdfs[] = $this->resource_storage->consume()->stream($resource_id)->getStream()->detach();
            }
        }
        
        if (!empty($pdfs)) {
            $context = new WriterContext();
            $context->init((string) $writer->getUserId(), (string) $object->getRefId());
            $service = new Service($context);

            $relative_workdir = 'xlas_'.bin2hex(random_bytes(8));
            $this->temp_fs->createDir($relative_workdir);
            $absolute_workdir = $this->file_helper->getAbsoluteTempDir() . '/' . $relative_workdir;

            $page_images = $service->createPageImagesFromPdfs($pdfs, PATH_TO_GHOSTSCRIPT, $absolute_workdir);
            $repo_images = [];

            $page = 1;
            foreach ($page_images as $image) {
                $stream = Streams::ofResource($image->getImage());
                $file_id = $this->resource_storage->manage()->stream($stream, new EssayImageResourceStakeholder());
                
                $thumb_id = null;
                if (!empty($image->getThumbnail())) {
                    $thumb_stream = Streams::ofResource($image->getThumbnail());
                    $thumb_id = $this->resource_storage->manage()->stream($thumb_stream, new EssayImageResourceStakeholder());
                }

                $repo_images[] = new EssayImage(
                    0,
                    $essay->getId(),
                    $page++,
                    (string) $file_id,
                    $image->getMime(),
                    $image->getWidth(),
                    $image->getHeight(),
                    (string) $thumb_id,
                    $image->getThumbMime(),
                    $image->getThumbWidth(),
                    $image->getThumbHeight(),
                );

            }

            // this is an atomic operation to avoid race conditions between background task and creation on demand
            $deleted = $this->essayRepo->replaceEssayImagesByEssayId($essay->getId(), $repo_images);

            $this->temp_fs->deleteDir($relative_workdir);
            $this->purgeImageFiles($deleted);

            return count($repo_images);
        }
        return 0;
    }

    public function removeEssayImages(int $essay_id)
    {
        // this is an atomic operation to avoid race conditions between background task and deletion
        $deleted = $this->essayRepo->replaceEssayImagesByEssayId($essay_id, []);
        $this->purgeImageFiles($deleted);
    }

    /**
     * Delete the file resources of deleted essay images
     * @param EssayImage[] $images
     */
    protected function purgeImageFiles(array $images): void
    {
        foreach ($images as $image) {
            if($identifier = $this->resource_storage->manage()->find($image->getFileId())) {
                $this->resource_storage->manage()->remove($identifier, new EssayImageResourceStakeholder());
            }
            if($identifier = $this->resource_storage->manage()->find($image->getThumbId())) {
                $this->resource_storage->manage()->remove($identifier, new EssayImageResourceStakeholder());
            }
        }
    }
}
