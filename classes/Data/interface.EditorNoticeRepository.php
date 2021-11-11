<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

interface EditorNoticeRepository
{
	// Create operations
	public function createEditorNotice(EditorNotice $a_notice);

	// Read operations
	public function getEditorNoticeById(int $a_id): ?EditorNotice;
	public function ifEditorNoticeExistsById(int $a_id): bool;

	// Update operations
	public function updateEditorNotice(EditorNotice $a_notice);

	// Delete operations
	public function deleteEditorNotice(int $a_id);
}