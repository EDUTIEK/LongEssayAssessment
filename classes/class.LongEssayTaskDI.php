<?php

namespace ILIAS\Plugin\LongEssayTask;

use ILIAS\Plugin\LongEssayTask\Data\AlertDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\AlertRepository;
use ILIAS\Plugin\LongEssayTask\Data\CorrectionSettingsDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\CorrectionSettingsRepository;
use ILIAS\Plugin\LongEssayTask\Data\EssayRepository;
use ILIAS\Plugin\LongEssayTask\Data\EssayDatabaseRepository;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class LongEssayTaskDI
{
	protected EssayRepository $essay;
	protected AlertRepository $alert;
	protected CorrectionSettingsRepository $correction_settings;

	public function getEssayRepo(): EssayRepository
	{
		if ($this->essay === null)
		{
			$this->essay = new EssayDatabaseRepository();
		}

		return $this->essay;
	}

	public function getAlertRepo(): AlertRepository
	{
		if ($this->alert === null)
		{
			$this->alert = new AlertDatabaseRepository();
		}

		return $this->alert;
	}

	public function getCorrectionSettingsRepo(): CorrectionSettingsRepository
	{
		if ($this->correction_settings === null)
		{
			$this->correction_settings = new CorrectionSettingsDatabaseRepository();
		}

		return $this->correction_settings;
	}
}