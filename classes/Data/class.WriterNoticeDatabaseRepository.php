<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class WriterNoticeDatabaseRepository implements WriterNoticeRepository
{

	public function createEditorNotice(WriterNotice $a_notice)
	{
		$a_notice->create();
	}

	public function getEditorNoticeById(int $a_id): ?WriterNotice
	{
		$notice =  WriterNotice::findOrGetInstance($a_id);
		if ($notice != null) {
			return $notice;
		}
		return null;
	}

	public function ifEditorNoticeExistsById(int $a_id): bool
	{
		return ( $this->getEditorNoticeById($a_id) != null );
	}

	public function updateEditorNotice(WriterNotice $a_notice)
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

