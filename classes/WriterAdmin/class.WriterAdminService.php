<?php

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use Edutiek\LongEssayService\Data\WritingStep;
use Edutiek\LongEssayService\Writer\Service;
use ILIAS\Data\UUID\Factory as UUID;
use ILIAS\Plugin\LongEssayTask\BaseService;
use ILIAS\Plugin\LongEssayTask\Data\Alert;
use ILIAS\Plugin\LongEssayTask\Data\DataService;
use ILIAS\Plugin\LongEssayTask\Data\EssayRepository;
use ILIAS\Plugin\LongEssayTask\Data\LogEntry;
use ILIAS\Plugin\LongEssayTask\Data\TaskRepository;
use ILIAS\Plugin\LongEssayTask\Data\Writer;
use ILIAS\Plugin\LongEssayTask\Data\WriterRepository;
use ILIAS\Plugin\LongEssayTask\Writer\WriterContext;

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
            $this->writerRepo->createWriter($writer);
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
        $file = 'xlet/'. (new UUID)->uuid4AsString() . '.csv';
        $storage->write($file, $csv->getCSVString());

        return $basedir . '/' . $file;
    }


    /**
     * Create an export file for the writing steps
     * @param \ilObjLongEssayTask $object
     * @param Writer $repoWriter
     * @param string $dirname   name of the directory inside the zip file
     * @return string path to the zip file or null if the export can't be produced
     */
    public function createWritingStepsExport(\ilObjLongEssayTask $object, Writer $repoWriter, string $dirname) : ?string
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
        $tempdir = 'xlet/'. (new UUID)->uuid4AsString();
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


}
