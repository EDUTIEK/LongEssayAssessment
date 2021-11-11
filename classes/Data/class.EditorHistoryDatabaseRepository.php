<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class EditorHistoryDatabaseRepository implements EditorHistoryRepository
{

	public function createEditorHistory(EditorHistory $a_history)
	{
		$a_history->create();
	}

	public function getEditorHistoryById(int $a_id): ?EditorHistory
	{
		$history =  EditorHistory::findOrGetInstance($a_id);
		if ($history != null) {
			return $history;
		}
		return null;
	}

	public function ifEditorHistoryExistsById(int $a_id): bool
	{
		return ( $this->getEditorHistoryById($a_id) != null );
	}

	public function updateEditorHistory(EditorHistory $a_history)
	{
		$a_history->update();
	}

	public function deleteEditorHistory(int $a_id)
	{
		$history = $this->getEditorHistoryById($a_id);

		if ( $history != null ){
			$history->delete();
		}
	}
}

