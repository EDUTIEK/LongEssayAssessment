<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

use ilDatabaseException;
use Exception;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class WriterDatabaseRepository implements WriterRepository
{
	private \ilDBInterface $database;
	private EssayRepository $essay_repo;
	private CorrectorRepository $corrector_repo;

	public function __construct(\ilDBInterface $database, EssayRepository $essay_repo, CorrectorRepository $corrector_repo)
	{
		$this->database = $database;
		$this->essay_repo = $essay_repo;
		$this->corrector_repo = $corrector_repo;
	}

	public function createWriter(Writer $a_writer)
    {
        $a_writer->create();
    }

    public function createTimeExtension(TimeExtension $a_time_extension)
    {
        $a_time_extension->create();
    }

    public function getWriterById(int $a_id): ?Writer
    {
        $writer = Writer::findOrGetInstance($a_id);
        if ($writer != null) {
            return $writer;
        }
        return null;
    }

    public function getWritersByTaskId(int $a_task_id, ?array $a_writer_ids = null): array
    {
		$ar_list = Writer::where(['task_id' => $a_task_id]);

		if($a_writer_ids !== null && count($a_writer_ids) > 0){
			$ar_list = $ar_list->where(array( 'id' => $a_writer_ids), 'IN');
		}

        return $ar_list->get();
    }

    public function ifUserExistsInTasksAsWriter(int $a_user_id, int $a_task_id): bool
    {
        return $this->getWriterByUserId($a_user_id, $a_task_id) != null;
    }

    public function getWriterByUserId(int $a_user_id, int $a_task_id): ?Writer
    {
        foreach(Writer::where(['user_id' => $a_user_id, 'task_id' => $a_task_id])->get() as $writer) {
            return $writer;
        }
        return null;
    }

    public function getTimeExtensionById(int $a_id): ?TimeExtension
    {
        $extension = TimeExtension::findOrGetInstance($a_id);
        if ($extension != null) {
            return $extension;
        }
        return null;
    }

    public function getTimeExtensionByWriterId(int $a_writer_id, int $a_task_id): ?TimeExtension
    {
        foreach(TimeExtension::where(['writer_id' => $a_writer_id, 'task_id' => $a_task_id])->get() as $extension) {
            return $extension;
        }
        return null;
    }

    public function getTimeExtensionsByTaskId(int $a_task_id): array
    {
        return TimeExtension::where(['task_id' => $a_task_id])->get();
    }

    public function updateWriter(Writer $a_writer)
    {
        $a_writer->update();
    }

    public function updateTimeExtension(TimeExtension $a_time_extension)
    {
        $a_time_extension->update();
    }

    /**
     * Deletes Writer, TimeExtension, CorrectorAssignment and Essay related datasets by WriterId
     *
     * @throws ilDatabaseException|Exception
     */
    public function deleteWriter(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlet_writer" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));

        $this->deleteTimeExtensionByWriterId($a_id);
        $this->corrector_repo->deleteCorrectorAssignmentByWriter($a_id);
        $this->essay_repo->deleteEssayByWriterId($a_id);
    }

    public function deleteTimeExtensionByWriterId(int $a_writer_id)
    {
		$this->database->manipulate("DELETE FROM xlet_time_extension" .
            " WHERE writer_id = " . $this->database->quote($a_writer_id, "integer"));
    }

    /**
     * Deletes Writer and TimeExtension by Task Id
     *
     * @param int $a_task_id
     */
    public function deleteWriterByTaskId(int $a_task_id)
    {
		$this->database->manipulate("DELETE FROM xlet_writer" .
            " WHERE task_id = " . $this->database->quote($a_task_id, "integer"));

		$this->database->manipulate("DELETE te FROM xlet_time_extension AS te"
            . " LEFT JOIN xlet_writer AS writer ON (te.writer_id = writer.id)"
            . " WHERE writer.task_id = " . $this->database->quote($a_task_id, "integer"));

		$this->corrector_repo->deleteCorrectorAssignmentByTask($a_task_id);
		$this->essay_repo->deleteEssayByTaskId($a_task_id);
	}

    public function deleteTimeExtension(int $a_writer_id, int $a_task_id)
    {
		$this->database->manipulate("DELETE FROM xlet_time_extension" .
            " WHERE writer_id = " . $this->database->quote($a_writer_id, "integer") .
            " AND task_id = " . $this->database->quote($a_task_id, "integer"));
    }

	public function getWriterByUserIds(array $a_user_ids, int $a_task_id): array
	{
		if(count($a_user_ids) == 0){
			return [];
		}

		return Writer::where(['task_id' => $a_task_id])->where(array( 'user_id' => $a_user_ids), 'IN')->get();
	}
}