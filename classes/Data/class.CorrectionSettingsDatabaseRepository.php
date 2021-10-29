<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

class CorrectionSettingsDatabaseRepository implements CorrectionSettingsRepository
{

	public function createCorrectionSettings(CorrectionSettings $a_settings)
	{
		$a_settings->create();
	}

	public function getCorrectionSettingsById(int $a_id): ?CorrectionSettings
	{
		$settings = CorrectionSettings::findOrGetInstance($a_id);
		if ($settings != null) {
			return $settings;
		}
		return null;
	}

	public function ifCorrectionSettingsExistsById(int $a_id): bool
	{
		return ( $this->getCorrectionSettingsById($a_id) != null );
	}

	public function updateCorrectionSettings(CorrectionSettings $a_settings)
	{
		$a_settings->update();
	}

	public function deleteCorrectionSettings(int $a_id)
	{
		$settings = $this->getCorrectionSettingsById($a_id);

		if ( $settings != null ){
			$settings->delete();
		}
	}
}