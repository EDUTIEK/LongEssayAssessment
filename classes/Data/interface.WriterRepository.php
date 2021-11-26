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
	public function getWriterById(int $a_id): ?Writer;
    public function getWriterByUserId(int $a_user_id, int $a_task_id): ?Writer;
    public function getWritersByTaskId(int $a_task_id): array;
	public function ifUserExistsInTasksAsWriter(int $a_user_id, int $a_task_id): bool;

    public function getTimeExtensionById(int $a_id): ?TimeExtension;
    public function getTimeExtensionByWriterId(int $a_writer_id, int $a_task_id): ?TimeExtension;
    public function getTimeExtensionsByTaskId(int $a_task_id): array;

	// Update operations
	public function updateWriter(Writer $a_writer);
    public function updateTimeExtension(TimeExtension $a_time_extension);

	// Delete operations
	public function deleteWriter(int $a_id);
    public function deleteWriterByTaskId(int $a_task_id);
    public function deleteTimeExtension(int $a_writer_id, int $a_task_id);
    public function deleteTimeExtensionByWriterId(int $a_writer_id);
    public function deleteTimeExtensionByTaskId(int $a_task_id);
}