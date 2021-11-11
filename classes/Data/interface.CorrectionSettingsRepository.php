<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
interface CorrectionSettingsRepository
{
	// Create operations
	public function createCorrectionSettings(CorrectionSettings $a_settings);

	// Read operations
	public function getCorrectionSettingsById(int $a_id): ?CorrectionSettings;
	public function ifCorrectionSettingsExistsById(int $a_id): bool;

	// Update operations
	public function updateCorrectionSettings(CorrectionSettings $a_settings);

	// Delete operations
	public function deleteCorrectionSettings(int $a_id);
}