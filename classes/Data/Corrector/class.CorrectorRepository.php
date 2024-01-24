<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\Corrector;

use ilDatabaseException;
use Exception;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\GradeLevel;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorRepository extends RecordRepo
{
    private EssayRepository $essay_repo;

    public function __construct(
        \ilDBInterface $db,
        \ilLogger $logger,
        EssayRepository $essay_repo
    ) {
        parent::__construct($db, $logger);
        $this->essay_repo = $essay_repo;
    }


    /**
     * @return Corrector|null
     */
    public function getCorrectorById(int $a_id):?RecordData
    {
        $query = "SELECT * FROM " . Corrector::tableName() . " WHERE id = " . $this->db->quote($a_id, 'integer');
        return $this->getSingleRecord($query, Corrector::model());
    }

    /**
     * @return Corrector[]
     */
    public function getCorrectorsByTaskId(int $a_task_id): array
    {
        $query = "SELECT * FROM " . Corrector::tableName() . " WHERE task_id = " . $this->db->quote($a_task_id, 'integer');
        return $this->queryRecords($query, Corrector::model());
    }

    public function ifUserExistsInTaskAsCorrector(int $a_user_id, int $a_task_id): bool
    {
        return $this->getCorrectorByUserId($a_user_id, $a_task_id) != null;
    }

    /**
     * @return Corrector|null
     */
    public function getCorrectorByUserId(int $a_user_id, int $a_task_id): ?RecordData
    {
        $query = "SELECT * FROM " . Corrector::tableName() . " WHERE user_id = " .
            $this->db->quote($a_user_id, 'integer') . " AND task_id = " . $this->db->quote($a_task_id, 'integer');
        return $this->getSingleRecord($query, Corrector::model());
    }

    /**
     * Get the preferences of a corrector
     * This will get defaults if preferences are not yet saved by the corrector
     * @param int $a_corrector_id
     * @return CorrectorPreferences
     */
    public function getCorrectorPreferences(int $a_corrector_id) : RecordData
    {
        $query = "SELECT * FROM " . CorrectorPreferences::tableName() . " WHERE corrector_id = " . $this->db->quote($a_corrector_id, 'integer');
        return $this->getSingleRecord($query, CorrectorPreferences::model(), new CorrectorPreferences($a_corrector_id));
    }

    /**
     * @param int $a_id
     * @return CorrectorAssignment|null
     */
    public function getCorrectorAssignmentById(int $a_id): ?RecordData
    {
        $query = "SELECT * FROM " . CorrectorAssignment::tableName() . " WHERE id = " . $this->db->quote($a_id, 'integer');
        return $this->getSingleRecord($query, CorrectorAssignment::model());
    }

    /**
     * @return CorrectorAssignment[]
     */
    public function getAssignmentsByWriterId(int $a_writer_id): array
    {
        $query = "SELECT * FROM " . CorrectorAssignment::tableName() . " WHERE writer_id = " .
            $this->db->quote($a_writer_id, 'integer');
        return $this->queryRecords($query, CorrectorAssignment::model());
    }

    /**
     * @return CorrectorAssignment[]
     */
    public function getAssignmentsByCorrectorId(int $a_corrector_id): array
    {
        $query = "SELECT * FROM " . CorrectorAssignment::tableName() . " WHERE corrector_id = " .
            $this->db->quote($a_corrector_id, 'integer') . ' ORDER BY position ASC';
        return $this->queryRecords($query, CorrectorAssignment::model());
    }

    public function ifCorrectorIsAssigned(int $a_writer_id, int $a_corrector_id): bool
    {
        return $this->getCorrectorAssignmentByPartIds($a_writer_id, $a_corrector_id) != null;
    }

    /**
     * @return CorrectorAssignment|null
     */
    public function getCorrectorAssignmentByPartIds(int $a_writer_id, int $a_corrector_id): ?RecordData
    {
        $query = "SELECT * FROM " . CorrectorAssignment::tableName() . " WHERE writer_id = " .
            $this->db->quote($a_writer_id, 'integer') . " AND corrector_id = " . $this->db->quote($a_corrector_id, 'integer');
        return $this->getSingleRecord($query, CorrectorAssignment::model());
    }

    /**
     * Also deletes all assignments of this corrector
     *
     * @param int $a_id
     * @throws Exception
     * @throws ilDatabaseException
     */
    public function deleteCorrector(int $a_id)
    {
        $this->db->manipulate("DELETE FROM xlas_corrector" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));

        $this->deleteCorrectorAssignmentByCorrector($a_id);
        $this->deleteCorrectorPreferencesByCorrector($a_id);
        $this->essay_repo->deleteAccessTokenByCorrectorId($a_id);
        $this->essay_repo->deleteCorrectorCommentByCorrectorId($a_id);
        $this->essay_repo->deleteCorrectorSummaryByCorrectorId($a_id);
    }

    public function deleteCorrectorAssignmentByCorrector(int $a_corrector_id)
    {
        $this->db->manipulate("DELETE FROM xlas_corrector_ass" .
            " WHERE corrector_id = " . $this->db->quote($a_corrector_id, "integer"));
    }

    public function deleteCorrectorPreferencesByCorrector(int $a_corrector_id)
    {
        $this->db->manipulate("DELETE FROM xlas_corrector_prefs" .
            " WHERE corrector_id = " . $this->db->quote($a_corrector_id, "integer"));
    }


    public function deleteCorrectorAssignment(int $a_writer_id, int $a_corrector_id)
    {
        $this->db->manipulate("DELETE FROM xlas_corrector_ass" .
            " WHERE writer_id = " . $this->db->quote($a_writer_id, "integer") .
            " AND corrector_id = " . $this->db->quote($a_corrector_id, "integer"));
    }

    public function deleteCorrectorAssignmentByTask(int $a_task_id)
    {
        $this->db->manipulate("DELETE ass FROM xlas_corrector_ass AS ass"
            . " LEFT JOIN xlas_corrector AS corrector ON (ass.corrector_id = corrector.id)"
            . " WHERE corrector.task_id = " . $this->db->quote($a_task_id, "integer"));
    }

    public function deleteCorrectorAssignmentByWriter(int $a_writer_id)
    {
        $this->db->manipulate("DELETE FROM xlas_corrector_ass" .
            " WHERE writer_id = " . $this->db->quote($a_writer_id, "integer"));
    }

    public function deleteCorrectorByTask(int $a_task_id)
    {
        $this->db->manipulate("DELETE ass FROM xlas_corrector_ass AS ass"
            . " JOIN xlas_corrector AS corrector ON (ass.corrector_id = corrector.id)"
            . " WHERE corrector.task_id = " . $this->db->quote($a_task_id, "integer"));

        $this->db->manipulate("DELETE prefs FROM xlas_corrector_prefs AS prefs"
            . " JOIN xlas_corrector AS corrector ON (prefs.corrector_id = corrector.id)"
            . " WHERE corrector.task_id = " . $this->db->quote($a_task_id, "integer"));
        
        $this->db->manipulate("DELETE FROM xlas_corrector" .
            " WHERE task_id = " . $this->db->quote($a_task_id, "integer"));

    }

    /**
     * @param int $a_task_id
     * @return CorrectorAssignment[]
     */
    public function getAssignmentsByTaskId(int $a_task_id): array
    {
        $query = "SELECT xlas_corrector_ass.* FROM xlas_corrector_ass LEFT JOIN xlas_corrector ON (xlas_corrector_ass.corrector_id = xlas_corrector.id)"
            . " WHERE task_id = " . $this->db->quote($a_task_id, 'integer');
        return $this->queryRecords($query, CorrectorAssignment::model());
    }
    /**
     * Save record data of an allowed type
     * @param Corrector|CorrectorPreferences|CorrectorAssignment $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }
}
