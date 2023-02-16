<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\Object;


use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\System\PluginConfig;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class ObjectRepository
{
	private \ilDBInterface $database;
	private EssayRepository $essay_repo;
	private TaskRepository $task_repo;

	public function __construct(\ilDBInterface $database, EssayRepository $essay_repo, TaskRepository $task_repo)
	{
		$this->database = $database;
		$this->essay_repo = $essay_repo;
		$this->task_repo = $task_repo;
	}

    public function createObject(ObjectSettings $a_object_settings)
    {
        $a_object_settings->create();
    }

    public function createGradeLevel(GradeLevel $a_grade_level)
    {
        $a_grade_level->create();
    }

    public function createRatingCriterion(RatingCriterion $a_rating_criterion)
    {
        $a_rating_criterion->create();
    }


    public function ifObjectExistsById(int $a_id): bool
    {
        return $this->getObjectSettingsById($a_id) != null;
    }

    public function getObjectSettingsById(int $a_id): ?ObjectSettings
    {
        $object = ObjectSettings::findOrGetInstance($a_id);
        if ($object != null) {
            return $object;
        }
        return null;
    }

    public function ifGradeLevelExistsById(int $a_id): bool
    {
        return $this->getGradeLevelById($a_id) != null;
    }

    public function getGradeLevelById(int $a_id): ?GradeLevel
    {
        $grade_level = GradeLevel::findOrGetInstance($a_id);
        if ($grade_level != null) {
            return $grade_level;
        }
        return null;
    }

    public function getGradeLevelsByObjectId(int $a_object_id): array
    {
        return GradeLevel::where(['object_id' => $a_object_id])->orderBy('min_points', 'DESC')->get();
    }

    public function ifRatingCriterionExistsById(int $a_id): bool
    {
        return $this->getRatingCriterionById($a_id) != null;
    }

    public function getRatingCriterionById(int $a_id): ?RatingCriterion
    {
        $rating_criterion = RatingCriterion::findOrGetInstance($a_id);
        if ($rating_criterion != null) {
            return $rating_criterion;
        }
        return null;
    }

    public function getRatingCriterionByObjectId(int $a_object_id): array
    {
        return RatingCriterion::where(['object_id' => $a_object_id])->get();
    }

    public function updateObjectSettings(ObjectSettings $a_object_settings)
    {
        $a_object_settings->update();
    }

    public function updateGradeLevel(GradeLevel $a_grade_level)
    {
        $a_grade_level->update();
    }

    public function updateRatingCriterion(RatingCriterion $a_rating_criterion)
    {
        $a_rating_criterion->update();
    }

    public function deleteObject(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_object_settings" .
            " WHERE obj_id = " . $this->database->quote($a_id, "integer"));

		$this->database->manipulate("DELETE FROM xlas_plugin_config" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));

        $this->deleteGradeLevelByObjectId($a_id);
        $this->deleteRatingCriterionByObjectId($a_id);
		$this->task_repo->deleteTaskByObjectId($a_id);
    }

    public function deleteGradeLevelByObjectId(int $a_object_id)
    {
		$this->database->manipulate("DELETE FROM xlas_grade_level" .
            " WHERE object_id = " . $this->database->quote($a_object_id, "integer"));
    }

    public function deleteRatingCriterionByObjectId(int $a_object_id)
    {
		$this->database->manipulate("DELETE FROM xlas_rating_crit" .
            " WHERE object_id = " . $this->database->quote($a_object_id, "integer"));
    }

    public function deleteGradeLevel(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_grade_level" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));
    }

    public function deleteRatingCriterion(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_rating_crit" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));

        $this->essay_repo->deleteCriterionPointsByRatingId($a_id);
    }
}