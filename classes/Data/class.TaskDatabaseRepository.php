<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

use ilDatabaseException;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\Plugin\LongEssayTask\LongEssayTaskDI;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class TaskDatabaseRepository implements TaskRepository
{

    public function createTask(TaskSettings $a_task_settings, EditorSettings $a_editor_settings, CorrectionSettings $a_correction_settings)
    {
        $a_task_settings->create();
        $a_editor_settings->create();
        $a_correction_settings->create();
    }

    public function createAlert(Alert $a_alert)
    {
        $a_alert->create();
    }

    public function createWriterNotice(WriterNotice $a_writer_notice)
    {
        $a_writer_notice->create();
    }

    public function getEditorSettingsById(int $a_id): ?EditorSettings
    {
        $editor_settings = EditorSettings::findOrGetInstance($a_id);
        if ($editor_settings != null) {
            return $editor_settings;
        }
        return null;
    }

    public function getCorrectionSettingsById(int $a_id): ?CorrectionSettings
    {
        $correction_settings = CorrectionSettings::findOrGetInstance($a_id);
        if ($correction_settings != null) {
            return $correction_settings;
        }
        return null;
    }

    public function ifTaskExistsById(int $a_id): bool
    {
        return $this->getTaskSettingsById($a_id) != null;
    }

    public function getTaskSettingsById(int $a_id): ?TaskSettings
    {
        $task_settings = TaskSettings::findOrGetInstance($a_id);
        if ($task_settings != null) {
            return $task_settings;
        }
        return null;
    }

    public function ifAlertExistsById(int $a_id): bool
    {
        return $this->getAlertById($a_id) != null;
    }

    public function getAlertById(int $a_id): ?Alert
    {
        $alert = Alert::findOrGetInstance($a_id);
        if ($alert != null) {
            return $alert;
        }
        return null;
    }

    public function ifWriterNoticeExistsById(int $a_id): bool
    {
        return $this->getWriterNoticeById($a_id) != null;
    }

    public function getWriterNoticeById(int $a_id): ?WriterNotice
    {
        $writer_notice = WriterNotice::findOrGetInstance($a_id);
        if ($writer_notice != null) {
            return $writer_notice;
        }
        return null;
    }

    public function updateEditorSettings(EditorSettings $a_editor_settings)
    {
        $a_editor_settings->update();
    }

    public function updateCorrectionSettings(CorrectionSettings $a_correction_settings)
    {
        $a_correction_settings->update();
    }

    public function updateTaskSettings(TaskSettings $a_task_settings)
    {
        $a_task_settings->update();
    }

    public function updateAlert(Alert $a_alert)
    {
        $a_alert->update();
    }

    public function updateWriterNotice(WriterNotice $a_writer_notice)
    {
        $a_writer_notice->update();
    }

    /**
     * Deletes TaskSettings, EditorSettings, CorrectionSettings, Alerts, WriterNotices and Essay related datasets by Task ID
     *
     * @param int $a_id
     * @throws Exception
     * @throws ilDatabaseException
     */
    public function deleteTask(int $a_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->beginTransaction();
        try {
            $db->manipulate("DELETE FROM xlet_task_settings" .
                " WHERE task_id = " . $db->quote($a_id, "integer"));
            $db->manipulate("DELETE FROM xlet_editor_settings" .
                " WHERE task_id = " . $db->quote($a_id, "integer"));
            $db->manipulate("DELETE FROM xlet_corr_setting" .
                " WHERE task_id = " . $db->quote($a_id, "integer"));

            $this->deleteAlertByTaskId($a_id);
            $this->deleteWriterNoticeByTaskId($a_id);

            $di = LongEssayTaskDI::getInstance();

            $essay_repo = $di->getEssayRepo();
            $essay_repo->deleteEssayByTaskId($a_id);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        $db->commit();
    }

    public function deleteAlertByTaskId(int $a_task_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_alert" .
            " WHERE task_id = " . $DIC->database()->quote($a_task_id, "integer"));
    }

    public function deleteWriterNoticeByTaskId(int $a_task_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_writer_notice" .
            " WHERE task_id = " . $DIC->database()->quote($a_task_id, "integer"));
    }

    public function deleteAlert(int $a_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_alert" .
            " WHERE id = " . $db->quote($a_id, "integer"));
    }

    public function deleteWriterNotice(int $a_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_writer_notice" .
            " WHERE id = " . $db->quote($a_id, "integer"));
    }

    /**
     * Deletes TaskSettings, EditorSettings, CorrectionSettings, Alerts and WriterNotices by Object ID
     *
     * @param int $a_object_id
     */
    public function deleteTaskByObjectId(int $a_object_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_task_settings" .
            " WHERE task_id = " . $db->quote($a_object_id, "integer"));
        $db->manipulate("DELETE FROM xlet_editor_settings" .
            " WHERE task_id = " . $db->quote($a_object_id, "integer"));
        $db->manipulate("DELETE FROM xlet_corr_setting" .
            " WHERE task_id = " . $db->quote($a_object_id, "integer"));

        $this->deleteAlertByTaskId($a_object_id);
        $this->deleteWriterNoticeByTaskId($a_object_id);
    }
}