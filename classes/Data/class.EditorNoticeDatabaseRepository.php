<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class EditorNoticeDatabaseRepository implements EditorNoticeRepository
{

	public function createEditorNotice(EditorNotice $a_notice)
	{
		$a_notice->create();
	}

	public function getEditorNoticeById(int $a_id): ?EditorNotice
	{
		$notice =  EditorNotice::findOrGetInstance($a_id);
		if ($notice != null) {
			return $notice;
		}
		return null;
	}

	public function ifEditorNoticeExistsById(int $a_id): bool
	{
		return ( $this->getEditorNoticeById($a_id) != null );
	}

	public function updateEditorNotice(EditorNotice $a_notice)
	{
		$a_notice->update();
	}

	public function deleteEditorNotice(int $a_id)
	{
		$notice = $this->getEditorNoticeById($a_id);

		if ( $notice != null ){
			$notice->delete();
		}
	}
}

