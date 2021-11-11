<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

interface EditorCommentRepository
{
	// Create operations
	public function createEditorComment(EditorComment $a_comment);

	// Read operations
	public function getEditorCommentById(int $a_id): ?EditorComment;
	public function ifEditorCommentExistsById(int $a_id): bool;

	// Update operations
	public function updateEditorComment(EditorComment $a_comment);

	// Delete operations
	public function deleteEditorComment(int $a_id);
}