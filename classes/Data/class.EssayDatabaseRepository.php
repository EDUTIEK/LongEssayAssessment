<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

use Exception;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class EssayDatabaseRepository implements EssayRepository
{

    public function createEssay(Essay $a_essay)
    {
        $a_essay->create();
    }

    public function createWriterHistory(WriterHistory $a_writer_history)
    {
        $a_writer_history->create();
    }

    public function createCorrectorSummary(CorrectorSummary $a_corrector_summary)
    {
        $a_corrector_summary->create();
    }

    public function createCorrectorComment(CorrectorComment $a_corrector_comment)
    {
        $a_corrector_comment->create();
    }

    public function createCriterionPoints(CriterionPoints $a_criterion_points)
    {
        $a_criterion_points->create();
    }

    public function createAccessToken(AccessToken $a_access_token)
    {
        $a_access_token->create();
    }

    public function getEssayByUUID(string $a_uuid): ?Essay
    {
        $essay = Essay::where(array('uuid' => $a_uuid))->get();

        if (count($essay) > 0) {
            return $essay[0];
        }
        return null;
    }

    public function ifEssayExistsById(int $a_id): bool
    {
        return ($this->getEssayById($a_id) != null);
    }

    public function getEssayById(int $a_id): ?Essay
    {
        $essay = Essay::findOrGetInstance($a_id);
        if ($essay != null) {
            return $essay;
        }
        return null;
    }

    public function getEssayByWriterIdAndTaskId(int $a_writer_id, int $a_task_id): ?Essay
    {
        foreach(Essay::where(['writer_id' => $a_writer_id, 'task_id'=> $a_task_id])->get() as $essay) {
            return  $essay;
        }
        return null;
    }

    public function getWriterHistoryStepsByEssayId(int $essay_id, ?int $limit = null): array
    {
        if ($limit > 0) {
            return array_reverse(
                WriterHistory::where(['essay_id' => $essay_id])
                ->orderBy('id', 'DESC')
                ->limit(0, $limit)
                ->get()
            );
        }
        else {
            return WriterHistory::where(['essay_id' => $essay_id])
                ->orderBy('id', 'ASC')
                ->get();
        }
    }

    public function ifWriterHistoryExistByEssayIdAndHashAfter(int $essay_id, string $hash_after): bool
    {
        return WriterHistory::where(['essay_id' => $essay_id, 'hash_after' => $hash_after])->hasSets();
    }

    public function getAccessTokenByUserIdAndTaskId(int $a_user_id, int $a_task_id, string $a_purpose): ?AccessToken
    {
        foreach(AccessToken::where(['user_id' => $a_user_id, 'task_id'=> $a_task_id, 'purpose' => $a_purpose])->orderBy('id')->get() as $token) {
            return  $token;
        }
        return null;
    }

    public function updateEssay(Essay $a_essay)
    {
        $a_essay->update();
    }

    public function updateWriterHistory(WriterHistory $a_writer_history)
    {
        $a_writer_history->update();
    }

    public function updateCorrectorSummary(CorrectorSummary $a_corrector_summary)
    {
        $a_corrector_summary->update();
    }

    public function updateCorrectorComment(CorrectorComment $a_corrector_comment)
    {
        $a_corrector_comment->update();
    }

    public function updateCriterionPoints(CriterionPoints $a_criterion_points)
    {
        $a_criterion_points->update();
    }

    public function deleteEssay(int $a_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_essay" .
            " WHERE id = " . $db->quote($a_id, "integer"));

        $db->manipulate("DELETE FROM xlet_access_token" .
            " WHERE essay_id = " . $db->quote($a_id, "integer"));
        $db->manipulate("DELETE FROM xlet_corrector_summary" .
            " WHERE essay_id = " . $db->quote($a_id, "integer"));
        $db->manipulate("DELETE FROM xlet_corrector_comment" .
            " WHERE essay_id = " . $db->quote($a_id, "integer"));

        $db->manipulate("DELETE xlet_crit_points FROM xlet_crit_points AS cp"
            . " LEFT JOIN xlet_corrector_comment AS cc ON (cp.corr_comment_id = cc.id)"
            . " WHERE cc.essay_id = " . $db->quote($a_id, "integer"));

        $db->manipulate("DELETE FROM xlet_writer_history" .
            " WHERE essay_id = " . $db->quote($a_id, "integer"));
    }

    public function deleteEssayByTaskId(int $a_task_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_essay" .
            " WHERE task_id = " . $db->quote($a_task_id, "integer"));
    }

    public function deleteEssayByWriterId(int $a_user_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_essay" .
            " WHERE writer_id = " . $db->quote($a_user_id, "integer"));
    }

    public function deleteWriterHistory(int $a_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_writer_history" .
            " WHERE id = " . $db->quote($a_id, "integer"));
    }

    public function deleteCorrectorSummary(int $a_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_corrector_summary" .
            " WHERE id = " . $db->quote($a_id, "integer"));
    }

    public function deleteCorrectorSummaryByCorrectorId(int $a_user_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_corrector_summary" .
            " WHERE corrector_id = " . $db->quote($a_user_id, "integer"));
    }

    public function deleteCorrectorComment(int $a_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_corrector_comment" .
            " WHERE id = " . $db->quote($a_id, "integer"));

        $db->manipulate("DELETE FROM xlet_crit_points" .
            " WHERE corr_comment_id = " . $db->quote($a_id, "integer"));
    }

    public function deleteCorrectorCommentByCorrectorId(int $a_user_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_corrector_comment" .
            " WHERE corrector_id = " . $db->quote($a_user_id, "integer"));

        $db->manipulate("DELETE xlet_crit_points FROM xlet_crit_points AS cp"
            . " LEFT JOIN xlet_corrector_comment AS cc ON (cp.corr_comment_id = cc.id)"
            . " WHERE cc.corrector_id = " . $db->quote($a_user_id, "integer"));
    }

    public function deleteCriterionPoints(int $a_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_crit_points" .
            " WHERE id = " . $db->quote($a_id, "integer"));
    }

    public function deleteCriterionPointsByRatingId(int $a_rating_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_crit_points" .
            " WHERE rating_id = " . $db->quote($a_rating_id, "integer"));
    }

    public function deleteAccessToken(int $a_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE FROM xlet_access_token" .
            " WHERE id = " . $db->quote($a_id, "integer"));
    }

    public function deleteAccessTokenByCorrectorId(int $a_corrector_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE xlet_access_token FROM xlet_access_token AS access_token"
            . " LEFT JOIN xlet_corrector AS corrector ON (access_token.user_id = corrector.user_id)"
            . " WHERE corrector.id = " . $db->quote($a_corrector_id, "integer"));
    }

    public function deleteAccessTokenByWriterId(int $a_writer_id)
    {
        global $DIC;
        $db = $DIC->database();

        $db->manipulate("DELETE xlet_access_token FROM xlet_access_token AS access_token"
            . " LEFT JOIN xlet_writer AS writer ON (access_token.user_id = writer.user_id)"
            . " WHERE writer.id = " . $db->quote($a_writer_id, "integer"));
    }
}

