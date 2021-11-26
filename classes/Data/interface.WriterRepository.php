<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * Manages ActiveDirectoryClasses:
 *  Writer
 *  TimeExtension
 *
 * @author Fabian Wolf <wolf@ilias.de>
 */
interface WriterRepository
{
	// Create operations
	public function createWriter(Writer $a_writer);
    public function createTimeExtension(TimeExtension $a_time_extension);

	// Read operations
	public function getWriterById(int $a_id): ?Alert;
	public function ifWriterExistsById(int $a_id): bool;

	// Update operations
	public function updateWriter(Writer $a_writer);
    public function updateTimeExtension(TimeExtension $a_time_extension);

	// Delete operations
	public function deleteAlert(int $a_id);
    public function deleteTimeExtension(int $a_writer_id, int $a_task_id);
}