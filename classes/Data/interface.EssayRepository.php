<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

interface EssayRepository
{
	// Create operations
	public function createEssay(Essay $a_essay);

	// Read operations
	public function getEssayById(int $a_id): ?Essay;
	public function getEssayByUUID(string $a_uuid): ?Essay;
	public function ifEssayExistsById(int $a_id): bool;

	// Update operations
	public function updateEssay(Essay $a_essay);

	// Delete operations
	public function deleteEssay(int $a_id);
}