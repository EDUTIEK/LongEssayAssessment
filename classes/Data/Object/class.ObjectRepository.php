<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\Object;

use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo;

use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class ObjectRepository extends RecordRepo
{
	private EssayRepository $essay_repo;
	private TaskRepository $task_repo;

	public function __construct(
        \ilDBInterface $db,
        \ilLogger $logger,
        EssayRepository $essay_repo, 
        TaskRepository $task_repo
    )
	{
        parent::__construct($db, $logger);
		$this->essay_repo = $essay_repo;
		$this->task_repo = $task_repo;
	}

    /**
     * @return ObjectSettings|null
     */
    public function getObjectSettingsById(int $a_id): ?RecordData
    {
        $query = "SELECT * FROM xlas_object_settings WHERE obj_id = " . $this->db->quote($a_id, 'integer');
        return $this->getSingleRecord($query, ObjectSettings::model());
    }

    public function ifGradeLevelExistsById(int $a_id): bool
    {
        return $this->getGradeLevelById($a_id) != null;
    }

    /**
     * @return GradeLevel|null
     */
    public function getGradeLevelById(int $a_id): ?RecordData
    {
        $query = "SELECT * FROM xlas_grade_level WHERE id = " . $this->db->quote($a_id, 'integer');
        return $this->getSingleRecord($query, GradeLevel::model());
    }

    /**
     * @return GradeLevel[]
     */
    public function getGradeLevelsByObjectId(int $a_object_id): array
    {
        $query = "SELECT * FROM xlas_grade_level WHERE object_id = " . $this->db->quote($a_object_id, 'integer')
            . ' ORDER BY min_points DESC';
        return $this->queryRecords($query, GradeLevel::model());
    }

    /**
     * @return RatingCriterion|null
     */
    public function getRatingCriterionById(int $a_id): ?RecordData
    {
        $query = "SELECT * FROM xlas_rating_crit WHERE id = " . $this->db->quote($a_id, 'integer');
        return $this->getSingleRecord($query, RatingCriterion::model());
    }

	/**
	 * @param int $a_object_id
	 * @return RatingCriterion[]
	 */
    public function getRatingCriteriaByObjectId(int $a_object_id, ?int $corrector_id = null): array
    {
        $query = "SELECT * FROM xlas_rating_crit WHERE object_id = " . $this->db->quote($a_object_id, 'integer');

		if($corrector_id !== null){
			$query .= " AND corrector_id = " . $this->db->quote($corrector_id, "integer");
		}else{
			$query .= " AND corrector_id IS NULL";
		}

        return $this->queryRecords($query, RatingCriterion::model());
    }


    public function deleteObject(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_object_settings" .
            " WHERE obj_id = " . $this->db->quote($a_id, "integer"));

		$this->db->manipulate("DELETE FROM xlas_plugin_config" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));

        $this->deleteGradeLevelByObjectId($a_id);
        $this->deleteRatingCriterionByObjectId($a_id);
		$this->task_repo->deleteTaskByObjectId($a_id);
    }

    public function deleteGradeLevelByObjectId(int $a_object_id)
    {
		$this->db->manipulate("DELETE FROM xlas_grade_level" .
            " WHERE object_id = " . $this->db->quote($a_object_id, "integer"));
    }

    public function deleteRatingCriterionByObjectId(int $a_object_id)
    {
		$this->db->manipulate("DELETE FROM xlas_rating_crit" .
            " WHERE object_id = " . $this->db->quote($a_object_id, "integer"));
    }

	public function deleteRatingCriterionByObjectIdAndCorrectorId(int $a_object_id, ?int $a_corrector_id)
	{
		$query = "DELETE FROM xlas_rating_crit WHERE object_id = " . $this->db->quote($a_object_id, "integer");

		if($a_corrector_id != null){
			$query .= " AND corrector_id = " . $this->db->quote($a_corrector_id, "integer");
		}else {
			$query .= " AND corrector_id IS NULL";
		}

		$this->db->manipulate($query);
	}

    public function deleteGradeLevel(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_grade_level" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));
    }

    public function deleteRatingCriterion(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_rating_crit" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));

        $this->essay_repo->deleteCriterionPointsByRatingId($a_id);
    }

	public function getRatingCriterionGroupForCopy(int $object_id): array
	{
		$query = $this->db->query(
			"SELECT rating.corrector_id as corrector_id, corrector.user_id as usr_id FROM " . RatingCriterion::tableName() . " as rating".
			" LEFT JOIN " . Corrector::tableName() . " as corrector ON (rating.corrector_id = corrector.id)".
			" WHERE rating.corrector_id IS NOT NULL AND corrector.criterion_copy = 1".
			" AND rating.object_id = " . $this->db->quote($object_id, "integer") .
			" GROUP BY rating.corrector_id");

		$result = array();
		while ($row = $this->db->fetchAssoc($query)) {
			$result[] = ["corrector_id" => (int)$row["corrector_id"], "usr_id" => (int)$row["usr_id"]];
		}

		return $result;
	}

    /**
     * Save record data of an allowed type
     * @param ObjectSettings|GradeLevel|RatingCriterion $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }
}