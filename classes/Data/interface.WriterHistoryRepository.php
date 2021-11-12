<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

interface WriterHistoryRepository
{
	// Create operations
	public function createEditorHistory(WriterHistory $a_history);

	// Read operations
	public function getEditorHistoryById(int $a_id): ?WriterHistory;
	public function ifEditorHistoryExistsById(int $a_id): bool;

	// Update operations
	public function updateEditorHistory(WriterHistory $a_history);

	// Delete operations
	public function deleteEditorHistory(int $a_id);
}