<?php

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\Plugin\LongEssayAssessment\BaseService;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\DataService;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Alert;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;

class LoggingService extends BaseService
{
    private \ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Common\UserDataHelper $user_data_helper;
    /** @var TaskRepository */
    protected $taskRepo;

    /** @var WriterRepository  */
    protected $writerRepo;

    /** @var DataService */
    protected $dataService;

    protected int $task_id;

    /**
     * @inheritDoc
     */
    public function __construct(int $task_id)
    {
        parent::__construct();

        $this->task_id = $task_id;
        $this->taskRepo = $this->localDI->getTaskRepo();
        $this->writerRepo = $this->localDI->getWriterRepo();
        $this->user_data_helper = $this->localDI->services()->common()->userDataHelper();
    }

    /**
     * Write an entry to the log
     * @param string $type  entry type, @see LogEntry
     * @param int|null    $subject_user_id   user id if the user who causes the log entry (active user)
     * @param int|null    $object_user_id    user id of the user for whom the log entry is done (passive user)
     * @param string|null $note              optional note that should be added
     * @return void
     */
    public function addEntry(string $type, ?int $subject_user_id, ?int $object_user_id, ?string $note = null) : void
    {
        $lang = $this->plugin->getDefaultLanguage();
        $timestamp = (new \ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATETIME);
        $category = LogEntry::CATEGORY_BY_TYPE[$type] ?? LogEntry::CATEGORY_NOTE;

        $names = $this->user_data_helper->getNames([$subject_user_id, $object_user_id]);
        $subject = $names[$subject_user_id] ?? $this->plugin->txt('unknown', $lang);
        $object = $names[$object_user_id] ?? $this->plugin->txt('unknown', $lang);

        switch ($type) {
            case LogEntry::TYPE_TIME_EXTENSION:
                $entry = sprintf($this->plugin->txt('log_entry_time_extension', $lang), $object, $subject);
                break;
            case LogEntry::TYPE_WRITER_REMOVAL:
                $entry = sprintf(($this->plugin->txt('log_entry_writer_removal', $lang)), $object, $subject);
                break;
            case LogEntry::TYPE_WRITER_EXCLUSION:
                $entry = sprintf(($this->plugin->txt('log_entry_writer_exclusion', $lang)), $object, $subject);
                break;
            case LogEntry::TYPE_WRITER_REPEAL_EXCLUSION:
                $entry = sprintf($this->plugin->txt('log_entry_writer_repealed_exclusion', $lang), $object, $subject);
                break;
            case LogEntry::TYPE_WRITING_POST_AUTHORIZED:
                $entry = sprintf($this->plugin->txt('log_entry_writing_post_authorized', $lang), $object, $subject);
                break;
            case LogEntry::TYPE_WRITING_REMOVE_AUTHORIZATION:
                $entry = sprintf($this->plugin->txt('log_entry_writing_removed_authorized', $lang), $object, $subject);
                break;
            case LogEntry::TYPE_CORRECTION_REMOVE_AUTHORIZATION:
                $entry = sprintf($this->plugin->txt('log_entry_removed_authorization', $lang), $object, $subject);
                break;
            case LogEntry::TYPE_CORRECTION_REMOVE_OWN_AUTHORIZATION:
                $entry = sprintf($this->plugin->txt('log_entry_removed_own_authorization', $lang), $object, $subject);
                break;
            case LogEntry::TYPE_NOTE:
                $entry = sprintf($this->plugin->txt('log_entry_note', $lang), $subject);
                break;
            default:
                $entry = '';
        }

        $log_entry = new LogEntry();
        $log_entry->setEntry(trim($entry . ' ' .  $note))
                  ->setTaskId($this->task_id)
                  ->setTimestamp($timestamp)
                  ->setCategory($category);

        $this->taskRepo->save($log_entry);
    }


    /**
     * Create the log as a CSV string
     */
    public function createCsv() : string
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
                $csv->addColumn((string) $entry->getTimestamp());
                $csv->addColumn($this->plugin->txt('log_cat_' . $entry->getCategory()));
                $csv->addColumn('');
                $csv->addColumn(mb_convert_encoding((string) $entry->getEntry(), 'ISO-8859-1', 'UTF-8'));
            } elseif ($entry instanceof Alert) {
                $to = $this->plugin->txt('log_alert_to_all');
                if (!empty($writer = $this->writerRepo->getWriterById((int) $entry->getWriterId())) && !empty($writer->getUserId())) {
                    $to = $this->user_data_helper->getFullname($writer->getUserId());
                }
                $csv->addColumn(mb_convert_encoding((string) $entry->getShownFrom(), 'ISO-8859-1', 'UTF-8'));
                $csv->addColumn($this->plugin->txt('log_cat_alert'));
                $csv->addColumn(mb_convert_encoding($to, 'ISO-8859-1', 'UTF-8'));
                $csv->addColumn(mb_convert_encoding($entry->getMessage(), 'ISO-8859-1', 'UTF-8'));
            }
        }

        return $csv->getCSVString();
    }


}