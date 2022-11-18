<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

use Edutiek\LongEssayService\Data\CorrectionSummary;

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
    
    public function getEssayByWriterIdAndTaskId(int $a_writer_id, int $a_task_id): ?Essay;

    /** @return Essay[] */
	public function getEssaysByTaskId(int $a_task_id): array;

    public function ifEssayExistsById(int $a_id): bool;

    public function getAccessTokenByUserIdAndTaskId(int $a_user_id, int $a_task_id, string $a_purpose): ?AccessToken;

    /** @return WriterHistory[] */
    public function getWriterHistoryStepsByEssayId(int $essay_id, ?int $limit = null): array;

	public function getLastWriterHistoryPerUserByTaskId(int $a_task_id): array;

    public function ifWriterHistoryExistByEssayIdAndHashAfter(int $essay_id, string $hash_after): bool;

    public function getCorrectorSummaryByEssayIdAndCorrectorId(int $essay_id, int $corrector_id): ?CorrectorSummary;

    // Update operations
    public function updateEssay(Essay $a_essay);

    public function updateWriterHistory(WriterHistory $a_writer_history);

    public function updateCorrectorSummary(CorrectorSummary $a_corrector_summary);

    public function updateCorrectorComment(CorrectorComment $a_corrector_comment);

    public function updateCriterionPoints(CriterionPoints $a_criterion_points);
    // public function updateAccessToken(AccessToken $a_access_token);

    // Delete operations
    public function deleteEssay(int $a_id);

    public function    deleteEssayByTaskId(int $a_task_id);

    public function deleteEssayByWriterId(int $a_user_id);

    public function deleteWriterHistory(int $a_id);

    public function deleteCorrectorSummary(int $a_id);

    public function deleteCorrectorSummaryByCorrectorId(int $a_user_id);

    public function deleteCorrectorComment(int $a_id);

    public function deleteCorrectorCommentByCorrectorId(int $a_user_id);

    public function deleteCriterionPoints(int $a_id);

    public function deleteCriterionPointsByRatingId(int $a_rating_id);

    public function deleteAccessToken(int $a_id);

    public function deleteAccessTokenByUserIdAndTaskId(int $a_user_id, int $task_id, string $purpose);

    public function deleteAccessTokenByCorrectorId(int $a_corrector_id);

    public function deleteAccessTokenByWriterId(int $a_writer_id);
}