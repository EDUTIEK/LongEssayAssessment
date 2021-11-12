<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

interface WriterCommentRepository
{
	// Create operations
	public function createEditorComment(WriterComment $a_comment);

	// Read operations
	public function getEditorCommentById(int $a_id): ?WriterComment;
	public function ifEditorCommentExistsById(int $a_id): bool;

	// Update operations
	public function updateEditorComment(WriterComment $a_comment);

	// Delete operations
	public function deleteEditorComment(int $a_id);
}