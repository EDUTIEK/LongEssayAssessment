<?php

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use ILIAS\Data\UUID\Factory as UUID;
use ILIAS\Plugin\LongEssayTask\BaseService;
use ILIAS\Plugin\LongEssayTask\Data\Alert;
use ILIAS\Plugin\LongEssayTask\Data\DataService;
use ILIAS\Plugin\LongEssayTask\Data\EssayRepository;
use ILIAS\Plugin\LongEssayTask\Data\LogEntry;
use ILIAS\Plugin\LongEssayTask\Data\TaskRepository;
use ILIAS\Plugin\LongEssayTask\Data\WriterRepository;

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

}