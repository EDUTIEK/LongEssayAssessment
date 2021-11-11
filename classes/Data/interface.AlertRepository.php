<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
interface AlertRepository
{
	// Create operations
	public function createAlert(Alert $a_alert);

	// Read operations
	public function getAlertById(int $a_id): ?Alert;
	public function ifAlertExistsById(int $a_id): bool;

	// Update operations
	public function updateAlert(Alert $a_alert);

	// Delete operations
	public function deleteAlert(int $a_id);
}