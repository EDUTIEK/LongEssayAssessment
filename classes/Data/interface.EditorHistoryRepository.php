<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

interface EditorHistoryRepository
{
	// Create operations
	public function createEditorHistory(EditorHistory $a_history);

	// Read operations
	public function getEditorHistoryById(int $a_id): ?EditorHistory;
	public function ifEditorHistoryExistsById(int $a_id): bool;

	// Update operations
	public function updateEditorHistory(EditorHistory $a_history);

	// Delete operations
	public function deleteEditorHistory(int $a_id);
}