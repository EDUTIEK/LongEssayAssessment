<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\Writer;

use ilDatabaseException;
use Exception;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class WriterRepository extends RecordRepo
{
	private EssayRepository $essay_repo;
	private CorrectorRepository $corrector_repo;

	public function __construct(\ilDBInterface $db,
								\ilLogger $logger,
								EssayRepository $essay_repo,
								CorrectorRepository $corrector_repo)
	{
		parent::__construct($db, $logger);
		$this->essay_repo = $essay_repo;
		$this->corrector_repo = $corrector_repo;
	}

	/**
	 * Save record data of an allowed type
	 * @param Writer|TimeExtension $record
	 */
	public function save(RecordData $record)
	{
		$this->replaceRecord($record);
	}

    public function getWriterById(int $a_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_writer WHERE id = " . $this->db->quote($a_id, 'integer');
		return $this->getSingleRecord($query, Writer::model());
    }

    public function getWritersByTaskId(int $a_task_id, ?array $a_writer_ids = null): array
    {
		$in_writer_ids = "";

		if($a_writer_ids !== null && count($a_writer_ids) > 0){
			$in_writer_ids = " AND " . $this->db->in('id', $a_writer_ids, false, 'integer');
		}

		$query = "SELECT * FROM xlas_writer WHERE task_id = " . $this->db->quote($a_task_id, 'integer') . $in_writer_ids;


		return $this->queryRecords($query, Writer::model());
    }

    public function ifUserExistsInTasksAsWriter(int $a_user_id, int $a_task_id): bool
    {
        return $this->getWriterByUserId($a_user_id, $a_task_id) != null;
    }

    public function getWriterByUserId(int $a_user_id, int $a_task_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_writer WHERE user_id = " . $this->db->quote($a_user_id, 'integer') .
			" AND task_id = " . $this->db->quote($a_task_id, 'integer') ;

		return $this->getSingleRecord($query, Writer::model());
    }

    public function getTimeExtensionById(int $a_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_time_extension WHERE id = " . $this->db->quote($a_id, 'integer');
		return $this->getSingleRecord($query, TimeExtension::model());
    }

    public function getTimeExtensionByWriterId(int $a_writer_id, int $a_task_id): ?RecordData
    {
		$query = "SELECT * FROM xlas_time_extension WHERE writer_id = " . $this->db->quote($a_writer_id, 'integer') .
		" AND task_id = " . $this->db->quote($a_task_id, 'integer');
		return $this->getSingleRecord($query, TimeExtension::model());
    }

    public function getTimeExtensionsByTaskId(int $a_task_id): array
    {
		$query = "SELECT * FROM xlas_time_extension WHERE task_id = " . $this->db->quote($a_task_id, 'integer');
		return $this->queryRecords($query, TimeExtension::model());
    }

    /**
     * Deletes Writer, TimeExtension, CorrectorAssignment and Essay related datasets by WriterId
     *
     * @throws ilDatabaseException|Exception
     */
    public function deleteWriter(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_writer" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));

        $this->deleteTimeExtensionByWriterId($a_id);
        $this->corrector_repo->deleteCorrectorAssignmentByWriter($a_id);
        $this->essay_repo->deleteEssayByWriterId($a_id);
    }

    public function deleteTimeExtensionByWriterId(int $a_writer_id)
    {
		$this->db->manipulate("DELETE FROM xlas_time_extension" .
            " WHERE writer_id = " . $this->db->quote($a_writer_id, "integer"));
    }

    /**
     * Deletes Writer and TimeExtension by Task Id
     *
     * @param int $a_task_id
     */
    public function deleteWriterByTaskId(int $a_task_id)
    {
		$this->db->manipulate("DELETE FROM xlas_writer" .
            " WHERE task_id = " . $this->database->quote($a_task_id, "integer"));

		$this->db->manipulate("DELETE te FROM xlas_time_extension AS te"
            . " LEFT JOIN xlas_writer AS writer ON (te.writer_id = writer.id)"
            . " WHERE writer.task_id = " . $this->db->quote($a_task_id, "integer"));

		$this->corrector_repo->deleteCorrectorAssignmentByTask($a_task_id);
		$this->essay_repo->deleteEssayByTaskId($a_task_id);
	}

    public function deleteTimeExtension(int $a_writer_id, int $a_task_id)
    {
		$this->db->manipulate("DELETE FROM xlas_time_extension" .
            " WHERE writer_id = " . $this->db->quote($a_writer_id, "integer") .
            " AND task_id = " . $this->db->quote($a_task_id, "integer"));
    }

	public function getWriterByUserIds(array $a_user_ids, int $a_task_id): array
	{
		if(count($a_user_ids) == 0){
			return [];
		}

		$query = "SELECT * FROM xlas_time_extension WHERE task_id = " . $this->db->quote($a_task_id, "integer") .
			" AND " . $this->db->in('user_id', $a_user_ids, false, 'integer');
		return $this->queryRecords($query, Writer::model());
	}
}