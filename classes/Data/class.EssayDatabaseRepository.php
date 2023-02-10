<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class EssayDatabaseRepository implements EssayRepository
{
	private \ilDBInterface $database;

	public function __construct(\ilDBInterface $database)
	{
		$this->database = $database;
	}

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

	public function getEssaysByTaskId(int $a_task_id):array
	{
		return  Essay::where(['task_id'=> $a_task_id])->get();
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

    public function getCorrectorSummaryByEssayIdAndCorrectorId(int $essay_id, int $corrector_id): ?CorrectorSummary
    {
        foreach(CorrectorSummary::where(['essay_id' => $essay_id, 'corrector_id' => $corrector_id])->get() as $summary) {
            return $summary;
        }
        return null;
    }

    public function getAccessTokenByUserIdAndTaskId(int $a_user_id, int $a_task_id, string $a_purpose): ?AccessToken
    {
        foreach(AccessToken::where(['user_id' => $a_user_id, 'task_id'=> $a_task_id, 'purpose' => $a_purpose])
                    ->orderBy('id', "DESC")->get() as $token) {
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
		$this->database->manipulate("DELETE FROM xlas_essay" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));

		$this->database->manipulate("DELETE FROM xlas_access_token" .
            " WHERE essay_id = " . $this->database->quote($a_id, "integer"));
		$this->database->manipulate("DELETE FROM xlas_corrector_summary" .
            " WHERE essay_id = " . $this->database->quote($a_id, "integer"));
		$this->database->manipulate("DELETE FROM xlas_corrector_comment" .
            " WHERE essay_id = " . $this->database->quote($a_id, "integer"));

		$this->database->manipulate("DELETE cp FROM xlas_crit_points AS cp"
            . " LEFT JOIN xlas_corrector_comment AS cc ON (cp.corr_comment_id = cc.id)"
            . " WHERE cc.essay_id = " . $this->database->quote($a_id, "integer"));

		$this->database->manipulate("DELETE FROM xlas_writer_history" .
            " WHERE essay_id = " . $this->database->quote($a_id, "integer"));
    }

    public function deleteEssayByTaskId(int $a_task_id)
    {
		$this->database->manipulate("DELETE FROM xlas_essay" .
            " WHERE task_id = " . $this->database->quote($a_task_id, "integer"));

		$this->database->manipulate("DELETE FROM xlas_access_token" .
			" WHERE task_id = " . $this->database->quote($a_task_id, "integer"));

		$this->database->manipulate("DELETE corrector_summary FROM xlas_corrector_summary AS corrector_summary"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_summary.essay_id = essay.id)"
			. " WHERE essay.task_id = " . $this->database->quote($a_task_id, "integer"));

		$this->database->manipulate("DELETE corrector_comment FROM xlas_corrector_comment AS corrector_comment"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_comment.essay_id = essay.id)"
			. " WHERE essay.task_id = " . $this->database->quote($a_task_id, "integer"));

		$this->database->manipulate("DELETE crit_points FROM xlas_crit_points AS crit_points"
			. " LEFT JOIN xlas_corrector_comment AS corrector_comment ON (crit_points.corr_comment_id = corrector_comment.id)"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_comment.essay_id = essay.id)"
			. " WHERE essay.task_id = " . $this->database->quote($a_task_id, "integer"));

		$this->database->manipulate("DELETE writer_history FROM xlas_writer_history AS writer_history"
			. " LEFT JOIN xlas_essay AS essay ON (writer_history.essay_id = essay.id)"
			. " WHERE essay.task_id = " . $this->database->quote($a_task_id, "integer"));
    }

    public function deleteEssayByWriterId(int $a_user_id)
    {
		$this->database->manipulate("DELETE FROM xlas_essay" .
            " WHERE writer_id = " . $this->database->quote($a_user_id, "integer"));

		$this->database->manipulate("DELETE corrector_summary FROM xlas_corrector_summary AS corrector_summary"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_summary.essay_id = essay.id)"
			. " WHERE essay.writer_id = " . $this->database->quote($a_user_id, "integer"));

		$this->database->manipulate("DELETE corrector_comment FROM xlas_corrector_comment AS corrector_comment"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_comment.essay_id = essay.id)"
			. " WHERE essay.writer_id = " . $this->database->quote($a_user_id, "integer"));

		$this->database->manipulate("DELETE crit_points FROM xlas_crit_points AS crit_points"
			. " LEFT JOIN xlas_corrector_comment AS corrector_comment ON (crit_points.corr_comment_id = corrector_comment.id)"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_comment.essay_id = essay.id)"
			. " WHERE essay.writer_id = " . $this->database->quote($a_user_id, "integer"));

		$this->database->manipulate("DELETE writer_history FROM xlas_writer_history AS writer_history"
			. " LEFT JOIN xlas_essay AS essay ON (writer_history.essay_id = essay.id)"
			. " WHERE essay.writer_id = " . $this->database->quote($a_user_id, "integer"));

    }

    public function deleteWriterHistory(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_writer_history" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));
    }

    public function deleteCorrectorSummary(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_corrector_summary" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));
    }

    public function deleteCorrectorSummaryByCorrectorId(int $a_user_id)
    {
		$this->database->manipulate("DELETE FROM xlas_corrector_summary" .
            " WHERE corrector_id = " . $this->database->quote($a_user_id, "integer"));
    }

    public function deleteCorrectorComment(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_corrector_comment" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));

		$this->database->manipulate("DELETE FROM xlas_crit_points" .
            " WHERE corr_comment_id = " . $this->database->quote($a_id, "integer"));
    }

    public function deleteCorrectorCommentByCorrectorId(int $a_user_id)
    {
		$this->database->manipulate("DELETE FROM xlas_corrector_comment" .
            " WHERE corrector_id = " . $this->database->quote($a_user_id, "integer"));

		$this->database->manipulate("DELETE cp FROM xlas_crit_points AS cp"
            . " LEFT JOIN xlas_corrector_comment AS cc ON (cp.corr_comment_id = cc.id)"
            . " WHERE cc.corrector_id = " . $this->database->quote($a_user_id, "integer"));
    }

    public function deleteCriterionPoints(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_crit_points" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));
    }

    public function deleteCriterionPointsByRatingId(int $a_rating_id)
    {
		$this->database->manipulate("DELETE FROM xlas_crit_points" .
            " WHERE rating_id = " . $this->database->quote($a_rating_id, "integer"));
    }

    public function deleteAccessToken(int $a_id)
    {
		$this->database->manipulate("DELETE FROM xlas_access_token" .
            " WHERE id = " . $this->database->quote($a_id, "integer"));
    }

    public function deleteAccessTokenByUserIdAndTaskId(int $a_user_id, int $a_task_id, string $a_purpose)
    {
		$this->database->manipulate("DELETE FROM xlas_access_token" .
            " WHERE user_id = " . $this->database->quote($a_user_id, "integer") .
            " AND task_id = " . $this->database->quote($a_task_id, "integer") .
            " AND purpose = " . $this->database->quote($a_purpose, "text"));
    }

    public function deleteAccessTokenByCorrectorId(int $a_corrector_id)
    {
		$this->database->manipulate("DELETE access_token FROM xlas_access_token AS access_token"
            . " LEFT JOIN xlas_corrector AS corrector ON (access_token.user_id = corrector.user_id)"
            . " WHERE corrector.id = " . $this->database->quote($a_corrector_id, "integer"));
    }

    public function deleteAccessTokenByWriterId(int $a_writer_id)
    {
		$this->database->manipulate("DELETE access_token FROM xlas_access_token AS access_token"
            . " LEFT JOIN xlas_writer AS writer ON (access_token.user_id = writer.user_id)"
            . " WHERE writer.id = " . $this->database->quote($a_writer_id, "integer"));
    }

	public function getLastWriterHistoryPerUserByTaskId(int $a_task_id): array
	{
		$res = $this->database->queryf("SELECT wh.id as id, wh.essay_id as essay_Id, max(wh.timestamp) as maxc 
                     FROM xlas_writer_history AS wh
                     LEFT JOIN xlas_essay AS e ON (wh.essay_id = e.id) 
                     WHERE e.task_id = %s group by essay_id", ['integer'], [$a_task_id]);

		$ids = [];
		while ($row = $this->database->fetchAssoc($res)) {
			$ids[] = $row["id"];
		}

		if (count($ids) <= 0) {
			return [];
		}

		return WriterHistory::where(["id" => $ids], "IN")->get();
	}
}

