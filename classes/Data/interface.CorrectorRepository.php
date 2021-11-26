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
    public function createCorrectorAssignment(CorrectorAssignment $a_time_extension);

	// Read operations
	public function getCorrectorById(int $a_id): ?Corrector;
	public function ifCorrectorExistsById(int $a_id): bool;

	// Update operations
	public function updateCorrector(Corrector $a_corrector);
    public function updateCorrectorAssignment(CorrectorAssignment $a_time_extension);

	// Delete operations
	public function deleteCorrector(int $a_id);
    public function deleteCorrectorAssignment(int $a_writer_id, int $a_task_id);
}