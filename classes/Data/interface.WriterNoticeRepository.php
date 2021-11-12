<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

interface WriterNoticeRepository
{
	// Create operations
	public function createEditorNotice(WriterNotice $a_notice);

	// Read operations
	public function getEditorNoticeById(int $a_id): ?WriterNotice;
	public function ifEditorNoticeExistsById(int $a_id): bool;

	// Update operations
	public function updateEditorNotice(WriterNotice $a_notice);

	// Delete operations
	public function deleteEditorNotice(int $a_id);
}