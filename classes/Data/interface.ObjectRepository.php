<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * Manages ActiveDirectoryClasses:
 *  ObjectSettings
 *  PluginConfig
 *  GradeLevel
 *  RatingCriterion
 *
 * @author Fabian Wolf <wolf@ilias.de>
 */
interface ObjectRepository
{
    // Create operations
    public function createObject(ObjectSettings $a_object_settings, PluginConfig $a_plugin_config);

    public function createGradeLevel(GradeLevel $a_grade_level);

    public function createRatingCriterion(RatingCriterion $a_rating_criterion);

    // Read operations
    public function getObjectSettingsById(int $a_id): ?ObjectSettings;

    public function getPluginConfigById(int $a_id): ?PluginConfig;

    public function ifObjectExistsById(int $a_id): bool;

    public function getGradeLevelById(int $a_id): ?GradeLevel;

    public function ifGradeLevelExistsById(int $a_id): bool;

    public function getRatingCriterionById(int $a_id): ?RatingCriterion;

    public function ifRatingCriterionExistsById(int $a_id): bool;

    /** @return GradeLevel[] */
    public function getGradeLevelsByObjectId(int $a_object_id): array;

    public function getRatingCriterionByObjectId(int $a_object_id): array;

    // Update operations
    public function updateObjectSettings(ObjectSettings $a_object_settings);

    public function updatePluginConfig(PluginConfig $a_plugin_config);

    public function updateGradeLevel(GradeLevel $a_grade_level);

    public function updateRatingCriterion(RatingCriterion $a_rating_criterion);

    // Delete operations
    public function deleteObject(int $a_id);

    public function deleteGradeLevel(int $a_id);

    public function deleteRatingCriterion(int $a_id);

    public function deleteGradeLevelByObjectId(int $a_object_id);

    public function deleteRatingCriterionByObjectId(int $a_object_id);
}