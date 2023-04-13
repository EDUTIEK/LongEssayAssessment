<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use Exception;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class TaskRepository extends RecordRepo
{
	private EssayRepository $essay_repo;
	private CorrectorRepository $corrector_repo;
	private WriterRepository $writer_repo;

	public function __construct(\ilDBInterface $db,
								\ilLogger $logger,
								EssayRepository $essay_repo,
								CorrectorRepository $corrector_repo,
								WriterRepository $writer_repo)
	{
		parent::__construct($db, $logger);
		$this->essay_repo = $essay_repo;
		$this->corrector_repo = $corrector_repo;
		$this->writer_repo = $writer_repo;
	}

	/**
	 * Save record data of an allowed type
	 * @param TaskSettings|EditorSettings|CorrectionSettings|Alert|WriterNotice|Resource $record
	 */
	public function save(RecordData $record)
	{
		$this->replaceRecord($record);
	}

	public function createTask(TaskSettings $a_task_settings, EditorSettings $a_editor_settings, CorrectionSettings $a_correction_settings)
    {
		$this->save($a_task_settings);
		$this->save($a_editor_settings);
		$this->save($a_correction_settings);
    }

	/**
	 * @param int $a_id
	 * @return EditorSettings|null
	 */
    public function getEditorSettingsById(int $a_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_editor_settings WHERE task_id = " . $this->db->quote($a_id, 'integer');
		return $this->getSingleRecord($query, EditorSettings::model());
    }

	/**
	 * @param int $a_id
	 * @return CorrectionSettings|null
	 */
    public function getCorrectionSettingsById(int $a_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_corr_setting WHERE task_id = " . $this->db->quote($a_id, 'integer');
		return $this->getSingleRecord($query, CorrectionSettings::model());
    }


    public function ifTaskExistsById(int $a_id): bool
    {
        return $this->getTaskSettingsById($a_id) != null;
    }

	/**
	 * @param int $a_id
	 * @return TaskSettings|null
	 */
    public function getTaskSettingsById(int $a_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_task_settings WHERE task_id = " . $this->db->quote($a_id, 'integer');
		return $this->getSingleRecord($query, TaskSettings::model());
    }

    public function ifAlertExistsById(int $a_id): bool
    {
        return $this->getAlertById($a_id) != null;
    }

	/**
	 * @param int $a_id
	 * @return Alert|null
	 */
    public function getAlertById(int $a_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_alert WHERE id = " . $this->db->quote($a_id, 'integer');
		return $this->getSingleRecord($query, Alert::model());
    }

    public function ifWriterNoticeExistsById(int $a_id): bool
    {
        return $this->getWriterNoticeById($a_id) != null;
    }

	/**
	public function getWriterNoticeByTaskId(int $a_task_id): array
	{
		$query = "SELECT * FROM xlas_writer_notice WHERE task_id = " . $this->db->quote($a_task_id, 'integer');
		return $this->queryRecords($query, WriterNotice::model());
	}

    public function getWriterNoticeById(int $a_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_writer_notice WHERE id = " . $this->db->quote($a_id, 'integer');
		return $this->getSingleRecord($query, WriterNotice::model());
    }

    /**
     * Deletes TaskSettings, EditorSettings, CorrectionSettings, Resources, Alerts, WriterNotices and Essay related datasets by Task ID
     *
     * @param int $a_id
     * @throws Exception
     */
    public function deleteTask(int $a_id)
    {
        $this->db->manipulate("DELETE FROM xlas_task_settings" .
            " WHERE task_id = " . $this->db->quote($a_id, "integer"));
		$this->db->manipulate("DELETE FROM xlas_editor_settings" .
            " WHERE task_id = " . $this->db->quote($a_id, "integer"));
		$this->db->manipulate("DELETE FROM xlas_corr_setting" .
            " WHERE task_id = " . $this->db->quote($a_id, "integer"));

        $this->deleteAlertByTaskId($a_id);
        $this->deleteWriterNoticeByTaskId($a_id);
        $this->deleteResourceByTaskId($a_id);
		$this->deleteLogEntryByTaskId($a_id);

		$this->essay_repo->deleteEssayByTaskId($a_id);
		$this->corrector_repo->deleteCorrectorByTask($a_id);
		$this->writer_repo->deleteWriter($a_id);
    }

    public function deleteAlertByTaskId(int $a_task_id)
    {
		$this->db->manipulate("DELETE FROM xlas_alert" .
            " WHERE task_id = " . $this->db->quote($a_task_id, "integer"));
    }

    public function deleteWriterNoticeByTaskId(int $a_task_id)
    {
		$this->db->manipulate("DELETE FROM xlas_writer_notice" .
            " WHERE task_id = " . $this->db->quote($a_task_id, "integer"));
    }

    public function deleteAlert(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_alert" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));
    }

    public function deleteWriterNotice(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_writer_notice" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));
    }

    /**
     * Deletes TaskSettings, EditorSettings, CorrectionSettings, Alerts and WriterNotices by Object ID
     *
     * @param int $a_object_id
     */
    public function deleteTaskByObjectId(int $a_object_id)
    {
		$this->deleteTask($a_object_id);
    }

	/**
	 * @param int $a_id
	 * @return Resource|null
	 */
    public function getResourceById(int $a_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_resource WHERE id = " . $this->db->quote($a_id, 'integer');
		return $this->getSingleRecord($query, Resource::model());
    }

	/**
	 * @param int $a_task_id
	 * @return Resource[]
	 */
    public function getResourceByTaskId(int $a_task_id): array
    {
		$query = "SELECT * FROM xlas_resource WHERE task_id = " . $this->db->quote($a_task_id, 'integer');
		return $this->queryRecords($query, Resource::model());
    }

    public function ifResourceExistsById(int $a_id): bool
    {
        return $this->getResourceById($a_id) != null;
    }

    public function deleteResource(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_resource" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));
    }

    public function deleteResourceByTaskId(int $a_task_id)
    {
		$this->db->manipulate("DELETE FROM xlas_resource" .
            " WHERE task_id = " . $this->db->quote($a_task_id, "integer"));
    }

	/**
	 * @param string $a_file_id
	 * @return Resource|null
	 */
    public function getResourceByFileId(string $a_file_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_resource WHERE file_id = " . $this->db->quote($a_file_id, 'text');
		return $this->getSingleRecord($query, Resource::model());
    }

    public function ifResourceExistsByFileId(string $a_file_id): bool
    {
        return $this->getResourceByFileId($a_file_id) != null;
    }

	public function ifLogEntryExistsById(int $a_id): bool
	{
		return $this->getLogEntryById($a_id) != null;
	}

	/**
	 * @param int $a_id
	 * @return LogEntry|null
	 */
	public function getLogEntryById(int $a_id): ?RecordData
	{
		$query = "SELECT * FROM xlas_log_entry WHERE id = " . $this->db->quote($a_id, 'text');
		return $this->getSingleRecord($query, LogEntry::model());
	}

	public function deleteLogEntry(int $a_id)
	{
		$this->db->manipulate("DELETE FROM xlas_log_entry" .
			" WHERE id = " . $this->db->quote($a_id, "integer"));
	}

	public function deleteLogEntryByTaskId(int $a_task_id)
	{
		$this->db->manipulate("DELETE FROM xlas_log_entry" .
			" WHERE task_id = " . $this->db->quote($a_task_id, "integer"));
	}

	/**
	 * @param int $a_task_id
	 * @return LogEntry[]
	 */
	public function getLogEntriesByTaskId(int $a_task_id): array
	{
		$query = "SELECT * FROM xlas_log_entry WHERE task_id = " . $this->db->quote($a_task_id, 'integer');
		return $this->queryRecords($query, LogEntry::model());
	}

	/**
	 * @param int $a_task_id
	 * @return Alert[]
	 */
	public function getAlertsByTaskId(int $a_task_id): array
	{
		$query = "SELECT * FROM xlas_alert WHERE task_id = " . $this->db->quote($a_task_id, 'integer');
		return $this->queryRecords($query, Alert::model());
	}
}