<?php

namespace ILIAS\Plugin\LongEssayTask;

use ILIAS\Plugin\LongEssayTask\Data\AlertDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\AlertRepository;
use ILIAS\Plugin\LongEssayTask\Data\CorrectionSettingsDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\CorrectionSettingsRepository;
use ILIAS\Plugin\LongEssayTask\Data\EditorCommentDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\EditorCommentRepository;
use ILIAS\Plugin\LongEssayTask\Data\EditorHistoryDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\EditorHistoryRepository;
use ILIAS\Plugin\LongEssayTask\Data\WriterNoticeDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\WriterNoticeRepository;
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
	protected WriterNoticeRepository $writer_notice;
	protected EditorCommentRepository $editor_comment;
	protected EditorHistoryRepository $editor_history;

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

	public function getWriterNoticeRepo(): WriterNoticeRepository
	{
		if ($this->writer_notice === null)
		{
			$this->writer_notice = new WriterNoticeDatabaseRepository();
		}

		return $this->writer_notice;
	}

	public function getEditorCommentRepo(): EditorCommentRepository
	{
		if ($this->editor_comment === null)
		{
			$this->editor_comment = new EditorCommentDatabaseRepository();
		}

		return $this->editor_comment;
	}

	public function getEditorHistoryRepo(): EditorHistoryRepository
	{
		if ($this->editor_history === null)
		{
			$this->editor_history = new EditorHistoryDatabaseRepository();
		}

		return $this->editor_history;
	}
}