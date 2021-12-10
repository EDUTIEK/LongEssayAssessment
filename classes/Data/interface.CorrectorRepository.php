<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * Manages ActiveDirectoryClasses:
 *  Corrector
 *  CorrectorAssignment
 *
 * @author Fabian Wolf <wolf@ilias.de>
 */
interface CorrectorRepository
{
    // Create operations
    public function createCorrector(Corrector $a_corrector);

    public function createCorrectorAssignment(CorrectorAssignment $a_corrector_assignment);

    // Read operations
    public function getCorrectorById(int $a_id): ?Corrector;

    public function getCorrectorsByTaskId(int $a_task_id): array;

    public function getCorrectorByUserId(int $a_user_id, int $a_task_id): ?Corrector;

    public function ifUserExistsInTaskAsCorrector(int $a_user_id, int $a_task_id): bool;

    public function getCorrectorAssignmentById(int $a_id): ?CorrectorAssignment;

    public function getCorrectorAssignmentByPartIds(int $a_writer_id, int $a_corrector_id): ?CorrectorAssignment;

    public function getAssignmentsByWriterId(int $a_writer_id): array;

    public function getAssignmentsByCorrectorId(int $a_corrector_id): array;

    public function ifCorrectorIsAssigned(int $a_writer_id, int $a_corrector_id): bool;

    // Update operations
    public function updateCorrector(Corrector $a_corrector);

    public function updateCorrectorAssignment(CorrectorAssignment $a_corrector_assignment);

    // Delete operations
    public function deleteCorrector(int $a_id);

    public function deleteCorrectorByTask(int $a_task_id);

    public function deleteCorrectorAssignment(int $a_writer_id, int $a_corrector_id);

    public function deleteCorrectorAssignmentByTask(int $a_task_id);

    public function deleteCorrectorAssignmentByCorrector(int $a_corrector_id);

    public function deleteCorrectorAssignmentByWriter(int $a_writer_id);
}