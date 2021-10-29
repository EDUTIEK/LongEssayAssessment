<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class AlertDatabaseRepository implements AlertRepository
{

	public function createAlert(Alert $a_alert)
	{
		$a_alert->create();
	}

	public function getAlertById(int $a_id): ?Alert
	{
		$alert = Alert::findOrGetInstance($a_id);
		if ($alert != null) {
			return $alert;
		}
		return null;
	}

	public function ifAlertExistsById(int $a_id): bool
	{
		return ( $this->getAlertById($a_id) != null );
	}

	public function updateAlert(Alert $a_alert)
	{
		$a_alert->update();
	}

	public function deleteAlert(int $a_id)
	{
		$alert = $this->getAlertById($a_id);

		if ( $alert != null ){
			$alert->delete();
		}
	}
}

