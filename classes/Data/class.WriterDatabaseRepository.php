<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

use ilDatabaseException;
use Exception;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class WriterDatabaseRepository implements WriterRepository
{

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

    public function getWritersByTaskId(int $a_task_id): array
    {
        return Writer::where(['task_id' => $a_task_id])->get();
    }

    public function ifUserExistsInTasksAsWriter(int $a_user_id, int $a_task_id): bool
    {
        return $this->getWriterByUserId($a_user_id, $a_task_id) != null;
    }

    public function getWriterByUserId(int $a_user_id, int $a_task_id): ?Writer
    {
        $writer = Writer::where(['user_id' => $a_user_id, 'task_id' => $a_task_id])->get();
        if (count($writer) > 0) {
            return $writer[1];
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
        $extension = TimeExtension::where(['writer_id' => $a_writer_id, 'task_id' => $a_task_id])->get();
        if (count($extension) > 0) {
            return $extension[1];
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
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_writer" .
            " WHERE id = " . $db->quote($a_id, "integer"));

        $this->deleteTimeExtensionByWriterId($a_id);

        $di = LongEssayTaskDI::getInstance();

        $corrector_repo = $di->getCorrectorRepo();
        $corrector_repo->deleteCorrectorAssignmentByWriter($a_id);

        $essay_repo = $di->getEssayRepo();
        $essay_repo->deleteEssayByWriterId($a_id);

    }

    public function deleteTimeExtensionByWriterId(int $a_writer_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_time_extension" .
            " WHERE writer_id = " . $db->quote($a_writer_id, "integer"));
    }

    /**
     * Deletes Writer and TimeExtension by Task Id
     *
     * @param int $a_task_id
     */
    public function deleteWriterByTaskId(int $a_task_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_writer" .
            " WHERE task_id = " . $db->quote($a_task_id, "integer"));

        $db->manipulate("DELETE xlet_time_extension FROM xlet_time_extension AS te"
            . " LEFT JOIN xlet_writer AS writer ON (te.writer_id = writer.id)"
            . " WHERE writer.task_id = " . $db->quote($a_task_id, "integer"));
    }

    public function deleteTimeExtension(int $a_writer_id, int $a_task_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_time_extension" .
            " WHERE writer_id = " . $db->quote($a_writer_id, "integer") .
            " AND task_id = " . $db->quote($a_task_id, "integer"));
    }

    public function deleteTimeExtensionByTaskId(int $a_task_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_time_extension" .
            " WHERE task_id = " . $db->quote($a_task_id, "integer"));
    }
}