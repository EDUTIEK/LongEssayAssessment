<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

use ILIAS\DI\Exceptions\Exception;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;

class ObjectDatabaseRepository implements ObjectRepository
{

    public function createObject(ObjectSettings $a_object_settings, PluginConfig $a_plugin_config)
    {
        $a_object_settings->create();
        $a_plugin_config->create();
    }

    public function createGradeLevel(GradeLevel $a_grade_level)
    {
        $a_grade_level->create();
    }

    public function createRatingCriterion(RatingCriterion $a_rating_criterion)
    {
        $a_rating_criterion->create();
    }

    public function getObjectSettingsById(int $a_id): ?ObjectSettings
    {
        $object = ObjectSettings::findOrGetInstance($a_id);
        if ($object != null) {
            return $object;
        }
        return null;
    }

    public function getPluginConfigById(int $a_id): ?PluginConfig
    {
        $plugin = PluginConfig::findOrGetInstance($a_id);
        if ($plugin != null) {
            return $plugin;
        }
        return null;
    }

    public function ifObjectExistsById(int $a_id): bool
    {
        return $this->getObjectSettingsById($a_id) != null;
    }

    public function getGradeLevelById(int $a_id): ?GradeLevel
    {
        $grade_level = GradeLevel::findOrGetInstance($a_id);
        if ($grade_level != null) {
            return $grade_level;
        }
        return null;
    }

    public function ifGradeLevelExistsById(int $a_id): bool
    {
        return $this->getGradeLevelById($a_id) != null;
    }

    public function getGradeLevelByObjectId(int $a_object_id): array
    {
        return GradeLevel::where(['object_id' => $a_object_id])->get();
    }

    public function getRatingCriterionById(int $a_id): ?RatingCriterion
    {
        $rating_criterion = RatingCriterion::findOrGetInstance($a_id);
        if ($rating_criterion != null) {
            return $rating_criterion;
        }
        return null;
    }

    public function ifRatingCriterionExistsById(int $a_id): bool
    {
        return $this->getRatingCriterionById($a_id) != null;
    }

    public function getRatingCriterionByObjectId(int $a_object_id): array
    {
        return RatingCriterion::where(['object_id' => $a_object_id])->get();
    }

    public function updateObjectSettings(ObjectSettings $a_object_settings)
    {
        $a_object_settings->update();
    }

    public function updatePluginConfig(PluginConfig $a_plugin_config)
    {
        $a_plugin_config->update();
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
        global $DIC;
        $DIC->
        $object = $this->getObjectSettingsById($a_id);

        if ( $object != null ){
            $DIC->database()->beginTransaction();
            try {
                $this->deleteGradeLevelByObjectId($object->getObjId());
                $this->deleteRatingCriterionByObjectId($object->getObjId());
                $di = LongEssayTaskDI::getInstance();

                $corrector_repo = $di->getCorrectorRepo();
                $corrector_repo->deleteCorrectorByTask($object->getObjId());

                $writer_repo = $di->getWriterRepo();
                $writer_repo->deleteWriterByTaskId($object->getObjId());

                $task_repo = $di->getTaskRepo();
                $task_repo->deleteTaskByObjectId($object->getObjId());

                $object->delete();
            }catch (Exception $e)
            {
                $DIC->database()->rollback();
                throw $e;
            }

            $DIC->database()->commit();
        }
    }

    public function deleteGradeLevel(int $a_id)
    {
        $grade_level = $this->getGradeLevelById($a_id);

        if ( $grade_level != null ){
            $grade_level->delete();
        }
    }

    public function deleteRatingCriterion(int $a_id)
    {
        $rating_criterion = $this->getRatingCriterionById($a_id);

        if ( $rating_criterion != null ){
            //TODO: Criterion Points

            $rating_criterion->delete();
        }
    }

    public function deleteGradeLevelByObjectId(int $a_object_id)
    {
        global $DIC;
        $DIC->database()->manipulate("DELETE FROM xlet_grade_level".
            " WHERE object_id = ". $DIC->database()->quote($a_object_id, "integer"));
    }

    public function deleteRatingCriterionByObjectId(int $a_object_id)
    {
        global $DIC;
        $DIC->database()->manipulate("DELETE FROM xlet_rating_crit".
            " WHERE object_id = ". $DIC->database()->quote($a_object_id, "integer"));
    }
}