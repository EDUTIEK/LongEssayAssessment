<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

class EssayDatabaseRepository implements EssayRepository
{

	public function createEssay(Essay $a_essay)
	{
		$a_essay->create();
	}

	public function getEssayById(int $a_id): ?Essay
	{
		$essay = Essay::findOrGetInstance($a_id);
		if ($essay != null) {
			return $essay;
		}
		return null;
	}

	public function getEssayByUUID(string $a_uuid): ?Essay
	{
		$essay = Essay::where(array('uuid' => $a_uuid))->get();

		if (count($essay) > 0) {
			return $essay[0];
		}
		return null;
	}

	public function ifEssayExistsById(int $a_id): bool
	{
		return ( $this->getEssayById($a_id) != null );
	}

	public function updateEssay(Essay $a_essay)
	{
		$a_essay->update();
	}

	public function deleteEssay(int $a_id)
	{
		$essay = $this->getEssayById($a_id);

		if ( $essay != null ){
			$essay->delete();
		}
	}
}

