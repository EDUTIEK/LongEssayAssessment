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

class WriterAdminService extends BaseService
{
    /** @var WriterRepository */
    protected $writerRepo;

    /** @var EssayRepository */
    protected $essayRepo;

    /** @var TaskRepository */
    protected $taskRepo;

    /** @var DataService */
    protected $dataService;

    /**
     * @inheritDoc
     */
    public function __construct(int $task_id)
    {
        parent::__construct($task_id);

        $this->writerRepo = $this->localDI->getWriterRepo();
        $this->correctorRepo = $this->localDI->getCorrectorRepo();
        $this->essayRepo = $this->localDI->getEssayRepo();
        $this->taskRepo = $this->localDI->getTaskRepo();
        $this->dataService = $this->localDI->getDataService($this->task_id);
    }

    /**
     * Get or create a writer object for an ILIAS user
     * @param int $user_id
     * @return Writer
     */
    public function getOrCreateWriterFromUserId(int $user_id) : Writer
    {
        $writer = $this->writerRepo->getWriterByUserId($user_id, $this->task_id);
        if (!isset($writer)) {
            $writer = new Writer();
            $writer->setUserId($user_id)
                ->setTaskId($this->task_id)
                ->setPseudonym($this->plugin->txt('participant') . ' ' . $user_id);
            $this->writerRepo->save($writer);
        }
        return $writer;
    }


    public function createLogExport()
    {
        $csv = new \ilCSVWriter();
        $csv->setSeparator(';');

        $csv->addColumn($this->plugin->txt('log_time'));
        $csv->addColumn($this->plugin->txt('log_category'));
        $csv->addColumn($this->plugin->txt('log_alert_to'));
        $csv->addColumn($this->plugin->txt('log_content'));


        $entries = [];
        foreach ($this->taskRepo->getLogEntriesByTaskId($this->task_id) as $logEntry) {
            $entries[$logEntry->getTimestamp() . ' log' . $logEntry->getId()] = $logEntry;
        }
        foreach ($this->taskRepo->getAlertsByTaskId($this->task_id) as $alert) {
            $entries[$alert->getShownFrom() . ' alert' . $alert->getId()] = $alert;
        }
        sort($entries);

        foreach ($entries as $entry) {
            $csv->addRow();
            if ($entry instanceof LogEntry) {
                $csv->addColumn($entry->getTimestamp());
                $csv->addColumn($this->plugin->txt('log_cat_' . $entry->getCategory()));
                $csv->addColumn('');
                $csv->addColumn($entry->getEntry());
            }
            elseif ($entry instanceof Alert) {
                $to = $this->plugin->txt('log_alert_to_all');
                if (!empty($writer = $this->writerRepo->getWriterById((int) $entry->getWriterId())) && !empty($writer->getUserId())) {
                    $to = \ilObjUser::_lookupFullname($writer->getUserId());
                }
                $csv->addColumn($entry->getShownFrom());
                $csv->addColumn($this->plugin->txt('log_cat_alert'));
                $csv->addColumn($to);
                $csv->addColumn($alert->getMessage());
            }
        }

        $storage = $this->dic->filesystem()->temp();
        $basedir = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
        $file = 'xlas/'. (new UUID)->uuid4AsString() . '.csv';
        $storage->write($file, $csv->getCSVString());

        return $basedir . '/' . $file;
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
        $tempdir = 'xlas/'. (new UUID)->uuid4AsString();
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
            }
            else {
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
        \ilUtil::zip($basedir . '/' . $zipdir, $zipfile);

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
    public function getWritersByStatus() {

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
            }
            elseif (!$writing_over) {
                $writing++;
            }
            else {
                $after++;
            }
        }

        return [$before, $writing, $after];
    }

	public function authorizeWriting(Essay $essay, int $user_id){
		$datetime = new \ilDateTime(time(), IL_CAL_UNIX);
		$essay->setWritingAuthorized($datetime->get(IL_CAL_DATETIME));
		$essay->setWritingAuthorizedBy($user_id);

		$this->essayRepo->save($essay);

		$this->createAuthorizeLogEntry($essay);
	}

	private function createAuthorizeLogEntry(Essay $essay){
		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
		$writer = $writer_repo->getWriterById($essay->getWriterId());

		$lng = $this->dic->language();

		$description = \ilLanguage::_lookupEntry(
			$lng->getDefaultLanguage(),
			$this->plugin->getPrefix(),
			$this->plugin->getPrefix() . "_writing_authorized_log_description"
		);
		$names = \ilUserUtil::getNamePresentation([$writer->getUserId(), $essay->getWritingAuthorizedBy()], false, false, "", true);

		$log_entry = new LogEntry();
		$log_entry->setEntry(sprintf($description, $names[$writer->getUserId()] ?? "unknown", $names[$essay->getWritingAuthorizedBy()] ?? "unknown"))
			->setTaskId($this->task_id)
			->setTimestamp($essay->getWritingAuthorized())
			->setCategory(LogEntry::CATEGORY_AUTHORIZE);

		$task_repo->save($log_entry);
	}

	public function removeAuthorizationWriting(Essay $essay, int $user_id)
	{
		if($essay->getWritingAuthorized() !== null){ // Only actively remove authorization if there was any before
			$essay->setWritingAuthorized(null);
			$essay->setWritingAuthorizedBy(null);

			$this->essayRepo->save($essay);

			$this->createAuthorizationRemoveLogEntry($essay, $user_id);
		}
	}

	private function createAuthorizationRemoveLogEntry(Essay $essay, int $user_id){
		$writer_repo = LongEssayAssessmentDI::getInstance()->getWriterRepo();
		$task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
		$writer = $writer_repo->getWriterById($essay->getWriterId());
		$datetime = new \ilDateTime(time(), IL_CAL_UNIX);

		$lng = $this->dic->language();

		$description = \ilLanguage::_lookupEntry(
			$lng->getDefaultLanguage(),
			$this->plugin->getPrefix(),
			$this->plugin->getPrefix() . "_writing_remove_authorize_log_description"
		);
		$names = \ilUserUtil::getNamePresentation([$writer->getUserId(), $user_id], false, false, "", true);

		$log_entry = new LogEntry();
		$log_entry->setEntry(sprintf($description, $names[$writer->getUserId()] ?? "unknown", $names[$user_id] ?? "unknown"))
			->setTaskId($this->task_id)
			->setTimestamp($datetime->get(IL_CAL_DATETIME))
			->setCategory(LogEntry::CATEGORY_AUTHORIZE);

		$task_repo->save($log_entry);
	}

	public function handlePDFVersionInput(Essay $essay, ?string $new_file_id){
		$temp_file = $this->localDI->getUploadTempFile();
		$saved_file_id = $essay->getPdfVersion();

		if ($new_file_id === null && $saved_file_id !== null){
			$resource_id = $this->dic->resourceStorage()->manage()->find($essay->getPdfVersion());
			$this->dic->resourceStorage()->manage()->remove($resource_id, new PDFVersionResourceStakeholder());
			$essay->setPdfVersion(null);
		}elseif($new_file_id !== $saved_file_id){
			if($saved_file_id == null){
				$resource_id = $temp_file->storeTempFileInResources($new_file_id, new PDFVersionResourceStakeholder());
			}else{
				$resource_id =  $this->dic->resourceStorage()->manage()->find($essay->getPdfVersion());
				if($resource_id !== null){
					$temp_file->replaceTempFileWithResource($new_file_id, $resource_id, new PDFVersionResourceStakeholder());
				}
			}
			$essay->setPdfVersion($resource_id !== null ? (string) $resource_id : null);
		}
		$this->essayRepo->save($essay);
	}
}
