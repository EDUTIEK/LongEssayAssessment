<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\Object;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo;

use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;

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

    public function getRatingCriteriaByObjectId(int $a_object_id): array
    {
        $query = "SELECT * FROM xlas_rating_crit WHERE object_id = " . $this->db->quote($a_object_id, 'integer');
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

    /**
     * Save record data of an allowed type
     * @param ObjectSettings|GradeLevel|RatingCriterion $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }
}