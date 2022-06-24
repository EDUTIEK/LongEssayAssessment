<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * Manages ActiveDirectoryClasses:
 *  EditorSettings
 *  CorrectionSettings
 *  TaskSettings
 *  LogEntry
 *  Alert
 *  WriterNotice
 *
 * @author Fabian Wolf <wolf@ilias.de>
 */
interface TaskRepository
{
    // Create operations
    public function createTask(TaskSettings       $a_task_settings, EditorSettings $a_editor_settings,
                               CorrectionSettings $a_correction_settings);

    public function createResource(Resource $a_resource);
    public function createLogEntry(LogEntry $a_log_entry);
    public function createAlert(Alert $a_alert);

    public function createWriterNotice(WriterNotice $a_writer_notice);

    // Read operations
    public function getEditorSettingsById(int $a_id): ?EditorSettings;

    public function getCorrectionSettingsById(int $a_id): ?CorrectionSettings;

    public function getTaskSettingsById(int $a_id): ?TaskSettings;

    public function ifTaskExistsById(int $a_id): bool;

    public function getResourceById(int $a_id): ?Resource;
    public function getResourceByFileId(string $a_file_id): ?Resource;
    public function getResourceByTaskId(int $a_task_id): array;
    public function ifResourceExistsById(int $a_id): bool;
    public function ifResourceExistsByFileId(string $a_file_id): bool;

    public function ifLogEntryExistsById(int $a_id): bool;
    public function getLogEntryById(int $a_id): ?LogEntry;

    public function getAlertById(int $a_id): ?Alert;
    public function ifAlertExistsById(int $a_id): bool;

    public function getWriterNoticeById(int $a_id): ?WriterNotice;
    public function ifWriterNoticeExistsById(int $a_id): bool;

    // Update operations
    public function updateEditorSettings(EditorSettings $a_editor_settings);

    public function updateCorrectionSettings(CorrectionSettings $a_correction_settings);

    public function updateTaskSettings(TaskSettings $a_task_settings);

    public function updateResource(Resource $a_resource);

    public function updateLogEntry(LogEntry $a_log_entry);
    public function updateAlert(Alert $a_alert);

    public function updateWriterNotice(WriterNotice $a_writer_notice);

    // Delete operations
    public function deleteTask(int $a_id);

    public function deleteTaskByObjectId(int $a_object_id);

    public function deleteResource(int $a_id);
    public function deleteResourceByTaskId(int $a_task_id);

    public function deleteLogEntry(int $a_id);
    public function deleteLogEntryByTaskId(int $a_task_id);
    public function deleteAlert(int $a_id);

    public function deleteAlertByTaskId(int $a_task_id);

    public function deleteWriterNotice(int $a_id);

    public function deleteWriterNoticeByTaskId(int $a_task_id);
}