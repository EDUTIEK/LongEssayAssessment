<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * Manages ActiveDirectoryClasses:
 *  Essay
 *  WriterHistory
 *  CorrectorSummary
 *  CorrectorComment
 *  CriterionPoints
 *  AccessToken
 *
 * @author Fabian Wolf <wolf@ilias.de>
 */
interface EssayRepository
{
    // Create operations
    public function createEssay(Essay $a_essay);

    public function createWriterHistory(WriterHistory $a_writer_history);

    public function createCorrectorSummary(CorrectorSummary $a_corrector_summary);

    public function createCorrectorComment(CorrectorComment $a_corrector_comment);

    public function createCriterionPoints(CriterionPoints $a_criterion_points);

    public function createAccessToken(AccessToken $a_access_token);

    // Read operations
    public function getEssayById(int $a_id): ?Essay;

    public function getEssayByUUID(string $a_uuid): ?Essay;

    public function ifEssayExistsById(int $a_id): bool;

    // Update operations
    public function updateEssay(Essay $a_essay);

    public function updateWriterHistory(WriterHistory $a_writer_history);

    public function updateCorrectorSummary(CorrectorSummary $a_corrector_summary);

    public function updateCorrectorComment(CorrectorComment $a_corrector_comment);

    public function updateCriterionPoints(CriterionPoints $a_criterion_points);
    // public function updateAccessToken(AccessToken $a_access_token);

    // Delete operations
    public function deleteEssay(int $a_id);

    public function deleteEssayByTaskId(int $a_task_id);

    public function deleteEssayByWriterId(int $a_user_id);

    public function deleteWriterHistory(int $a_id);

    public function deleteCorrectorSummary(int $a_id);

    public function deleteCorrectorSummaryByCorrectorId(int $a_user_id);

    public function deleteCorrectorComment(int $a_id);

    public function deleteCorrectorCommentByCorrectorId(int $a_user_id);

    public function deleteCriterionPoints(int $a_id);

    public function deleteCriterionPointsByRatingId(int $a_rating_id);

    public function deleteAccessToken(int $a_id);

    public function deleteAccessTokenByCorrectorId(int $a_corrector_id);

    public function deleteAccessTokenByWriterId(int $a_writer_id);
}