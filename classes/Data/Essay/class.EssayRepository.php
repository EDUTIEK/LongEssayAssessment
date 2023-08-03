<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo;
use Edutiek\LongEssayAssessmentService\Data\CorrectionComment;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class EssayRepository extends RecordRepo
{
	public function __construct(\ilDBInterface $db,\ilLogger $logger)
	{
		parent::__construct($db, $logger);
	}

	/**
	 * Save record data of an allowed type
	 * @param AccessToken|CorrectorComment|CorrectorSummary|CriterionPoints|Essay|EssayImage|WriterComment|WriterHistory $record
	 */
	public function save(RecordData $record)
	{
		$this->replaceRecord($record);
	}

	/**
	 * @param string $a_uuid
	 * @return Essay|null
	 */
    public function getEssayByUUID(string $a_uuid): ?RecordData
    {
		$query = "SELECT * FROM " . Essay::tableName() . " WHERE uuid = " . $this->db->quote($a_uuid, 'text');
		return $this->getSingleRecord($query, Essay::model());
	}

	/**
	 * @param string $a_uuid
	 * @return Essay|null
	 */
	public function getEssayByPDFVersionFileID(string $a_file_id): ?RecordData
	{
		$query = "SELECT * FROM " . Essay::tableName() . " WHERE pdf_version = " . $this->db->quote($a_file_id, 'text');
		return $this->getSingleRecord($query, Essay::model());
	}

    /**
     * @param int $a_essay_id
     * @return EssayImage[]
     */
    public function getEssayImagesByEssayID(int $a_essay_id): array
    {
        $query = "SELECT * FROM " . EssayImage::tableName() . " WHERE essay_id = " . $this->db->quote($a_essay_id, 'text')
            . ' ORDER BY page_no ASC';
        return $this->queryRecords($query, EssayImage::model(), true, true, 'page_no');

    }

    /**
     * @param string $a_file_id
     * @return EssayImage|null
     */
    public function getEssayImageByFileID(string $a_file_id): ?RecordData
    {
        $query = "SELECT * FROM " . EssayImage::tableName() . " WHERE file_id = " . $this->db->quote($a_file_id, 'text');
        return $this->getSingleRecord($query, EssayImage::model());
    }
    

    public function ifEssayExistsById(int $a_id): bool
    {
        return ($this->getEssayById($a_id) != null);
    }

	/**
	 * @param int $a_id
	 * @return Essay|null
	 */
    public function getEssayById(int $a_id): ?RecordData
    {
		$query = "SELECT * FROM " . Essay::tableName() . " WHERE id = " . $this->db->quote($a_id, 'integer');
		return $this->getSingleRecord($query, Essay::model());
    }

	/**
	 * @param int $a_task_id
	 * @return Essay[]
	 */
	public function getEssaysByTaskId(int $a_task_id):array
	{
		$query = "SELECT * FROM " . Essay::tableName() . " WHERE task_id = " . $this->db->quote($a_task_id, 'integer');
		return $this->queryRecords($query, Essay::model());
	}

	/**
	 * @param int $a_writer_id
	 * @param int $a_task_id
	 * @return Essay|null
	 */
    public function getEssayByWriterIdAndTaskId(int $a_writer_id, int $a_task_id): ?RecordData
    {
		$query = "SELECT * FROM " . Essay::tableName() . " WHERE writer_id = " . $this->db->quote($a_writer_id, 'integer') .
		" AND task_id = ". $this->db->quote($a_task_id, 'integer');
		return $this->getSingleRecord($query, Essay::model());
    }

	/**
	 * @param int $essay_id
	 * @param int|null $limit
	 * @return WriterHistory[]
	 */
    public function getWriterHistoryStepsByEssayId(int $essay_id, ?int $limit = null): array
    {
        if ($limit > 0) {
			$query = "SELECT * FROM " . WriterHistory::tableName() . " WHERE essay_id = " . $this->db->quote($essay_id, 'integer') .
			" ORDER BY id DESC LIMIT 0 " . $this->db->quote($limit, 'integer');
			return array_reverse($this->queryRecords($query, WriterHistory::model()));
        }
        else {
			$query = "SELECT * FROM " . WriterHistory::tableName() . " WHERE essay_id = " . $this->db->quote($essay_id, 'integer') .
				" ORDER BY id ASC";
			return $this->queryRecords($query, WriterHistory::model());
        }
    }

    public function ifWriterHistoryExistByEssayIdAndHashAfter(int $essay_id, string $hash_after): bool
    {
		$query = "SELECT * FROM " . WriterHistory::tableName() . " WHERE task_id = " . $this->db->quote($essay_id, 'integer') .
		" AND hash_after = ". $this->db->quote($hash_after, 'text');
		$records =  $this->queryRecords($query, WriterHistory::model());

        return count($records) > 0;
    }

	/**
	 * @param int $essay_id
	 * @param int $corrector_id
	 * @return CorrectorSummary|null
	 */
    public function getCorrectorSummaryByEssayIdAndCorrectorId(int $essay_id, int $corrector_id): ?RecordData
    {
		$query = "SELECT * FROM " . CorrectorSummary::tableName() . " WHERE essay_id = " . $this->db->quote($essay_id, 'integer') .
			" AND corrector_id = ". $this->db->quote($corrector_id, 'integer');
		return $this->getSingleRecord($query, CorrectorSummary::model());
    }

    /**
     * @param int $id
     * @return CorrectorComment|null
     */
    public function getCorrectorCommentById(int $id) : ?RecordData 
    {
        $query = "SELECT * FROM " . CorrectorComment::tableName() . " WHERE id = ". $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, CorrectorComment::model());
    }
    
    /**
     * @param int $essay_id
     * @param int $corrector_id
     * @return CorrectorComment[]
     */
    public function getCorrectorCommentsByEssayIdAndCorrectorId(int $essay_id, int $corrector_id): array
    {
        $query = "SELECT * FROM " . CorrectorComment::tableName() . " WHERE essay_id = " . $this->db->quote($essay_id, 'integer') .
            " AND corrector_id = ". $this->db->quote($corrector_id, 'integer');
        return $this->queryRecords($query, CorrectorComment::model());
    }

    /**
     * @param int $id
     * @return CriterionPoints|null
     */
    public function getCriterionPointsById(int $id) : ?RecordData
    {
        $query = "SELECT * FROM " . CriterionPoints::tableName() . " WHERE id = ". $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, CriterionPoints::model());
    }


    /**
     * @param int $essay_id
     * @param int $corrector_id
     * @return CriterionPoints[]
     */
    public function getCriterionPointsByEssayIdAndCorrectorId(int $essay_id, int $corrector_id): array
    {
        $query = "SELECT * FROM " . CriterionPoints::tableName() . " WHERE corr_comment_id IN ("
            . "SELECT id FROM " . CorrectorComment::tableName()
            . " WHERE essay_id = " . $this->db->quote($essay_id, 'integer')
            . " AND corrector_id = ". $this->db->quote($corrector_id, 'integer')
            . ")";
        return $this->queryRecords($query, CriterionPoints::model());
    }

    /**
	 * @param int $a_user_id
	 * @param int $a_task_id
	 * @param string $a_purpose
	 * @return AccessToken|null
	 */
    public function getAccessTokenByUserIdAndTaskId(int $a_user_id, int $a_task_id, string $a_purpose): ?RecordData
    {
		$query = "SELECT * FROM " . AccessToken::tableName() . " WHERE user_id = " . $this->db->quote($a_user_id, 'integer') .
			" AND task_id = ". $this->db->quote($a_task_id, 'integer') .
			" AND purpose = ". $this->db->quote($a_purpose, 'text') .
			" ORDER BY id DESC";
		return $this->getSingleRecord($query, AccessToken::model());
    }

    public function deleteEssay(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_essay" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));

		$this->db->manipulate("DELETE FROM xlas_access_token" .
            " WHERE essay_id = " . $this->db->quote($a_id, "integer"));
		$this->db->manipulate("DELETE FROM xlas_corrector_summary" .
            " WHERE essay_id = " . $this->db->quote($a_id, "integer"));
		$this->db->manipulate("DELETE FROM xlas_corrector_comment" .
            " WHERE essay_id = " . $this->db->quote($a_id, "integer"));

		$this->db->manipulate("DELETE cp FROM xlas_crit_points AS cp"
            . " LEFT JOIN xlas_corrector_comment AS cc ON (cp.corr_comment_id = cc.id)"
            . " WHERE cc.essay_id = " . $this->db->quote($a_id, "integer"));

		$this->db->manipulate("DELETE FROM xlas_writer_history" .
            " WHERE essay_id = " . $this->db->quote($a_id, "integer"));
    }

    public function deleteEssayByTaskId(int $a_task_id)
    {
		$this->db->manipulate("DELETE FROM xlas_essay" .
            " WHERE task_id = " . $this->db->quote($a_task_id, "integer"));

		$this->db->manipulate("DELETE FROM xlas_access_token" .
			" WHERE task_id = " . $this->db->quote($a_task_id, "integer"));

		$this->db->manipulate("DELETE corrector_summary FROM xlas_corrector_summary AS corrector_summary"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_summary.essay_id = essay.id)"
			. " WHERE essay.task_id = " . $this->db->quote($a_task_id, "integer"));

		$this->db->manipulate("DELETE corrector_comment FROM xlas_corrector_comment AS corrector_comment"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_comment.essay_id = essay.id)"
			. " WHERE essay.task_id = " . $this->db->quote($a_task_id, "integer"));

		$this->db->manipulate("DELETE crit_points FROM xlas_crit_points AS crit_points"
			. " LEFT JOIN xlas_corrector_comment AS corrector_comment ON (crit_points.corr_comment_id = corrector_comment.id)"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_comment.essay_id = essay.id)"
			. " WHERE essay.task_id = " . $this->db->quote($a_task_id, "integer"));

		$this->db->manipulate("DELETE writer_history FROM xlas_writer_history AS writer_history"
			. " LEFT JOIN xlas_essay AS essay ON (writer_history.essay_id = essay.id)"
			. " WHERE essay.task_id = " . $this->db->quote($a_task_id, "integer"));
    }

    public function deleteEssayByWriterId(int $a_user_id)
    {
		$this->db->manipulate("DELETE FROM xlas_essay" .
            " WHERE writer_id = " . $this->db->quote($a_user_id, "integer"));

		$this->db->manipulate("DELETE corrector_summary FROM xlas_corrector_summary AS corrector_summary"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_summary.essay_id = essay.id)"
			. " WHERE essay.writer_id = " . $this->db->quote($a_user_id, "integer"));

		$this->db->manipulate("DELETE corrector_comment FROM xlas_corrector_comment AS corrector_comment"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_comment.essay_id = essay.id)"
			. " WHERE essay.writer_id = " . $this->db->quote($a_user_id, "integer"));

		$this->db->manipulate("DELETE crit_points FROM xlas_crit_points AS crit_points"
			. " LEFT JOIN xlas_corrector_comment AS corrector_comment ON (crit_points.corr_comment_id = corrector_comment.id)"
			. " LEFT JOIN xlas_essay AS essay ON (corrector_comment.essay_id = essay.id)"
			. " WHERE essay.writer_id = " . $this->db->quote($a_user_id, "integer"));

		$this->db->manipulate("DELETE writer_history FROM xlas_writer_history AS writer_history"
			. " LEFT JOIN xlas_essay AS essay ON (writer_history.essay_id = essay.id)"
			. " WHERE essay.writer_id = " . $this->db->quote($a_user_id, "integer"));

    }
    
    public function deleteEssayImagesByEssayId(int $essay_id) {
        $this->db->manipulate("DELETE FROM essay_image WHERE essay_id = "
            . $this->db->quote($essay_id, 'integer'));
    }

    public function deleteWriterHistory(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_writer_history" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));
    }

    public function deleteCorrectorSummary(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_corrector_summary" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));
    }

    public function deleteCorrectorSummaryByCorrectorId(int $a_user_id)
    {
		$this->db->manipulate("DELETE FROM xlas_corrector_summary" .
            " WHERE corrector_id = " . $this->db->quote($a_user_id, "integer"));
    }

    public function deleteCorrectorComment(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_corrector_comment" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));

		$this->db->manipulate("DELETE FROM xlas_crit_points" .
            " WHERE corr_comment_id = " . $this->db->quote($a_id, "integer"));
    }

    public function deleteCorrectorCommentByCorrectorId(int $a_user_id)
    {
		$this->db->manipulate("DELETE FROM xlas_corrector_comment" .
            " WHERE corrector_id = " . $this->db->quote($a_user_id, "integer"));

		$this->db->manipulate("DELETE cp FROM xlas_crit_points AS cp"
            . " LEFT JOIN xlas_corrector_comment AS cc ON (cp.corr_comment_id = cc.id)"
            . " WHERE cc.corrector_id = " . $this->db->quote($a_user_id, "integer"));
    }

    public function deleteCriterionPoints(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_crit_points" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));
    }

    public function deleteCriterionPointsByRatingId(int $a_rating_id)
    {
		$this->db->manipulate("DELETE FROM xlas_crit_points" .
            " WHERE criterion_id = " . $this->db->quote($a_rating_id, "integer"));
    }

    public function deleteAccessToken(int $a_id)
    {
		$this->db->manipulate("DELETE FROM xlas_access_token" .
            " WHERE id = " . $this->db->quote($a_id, "integer"));
    }

    public function deleteAccessTokenByUserIdAndTaskId(int $a_user_id, int $a_task_id, string $a_purpose)
    {
		$this->db->manipulate("DELETE FROM xlas_access_token" .
            " WHERE user_id = " . $this->db->quote($a_user_id, "integer") .
            " AND task_id = " . $this->db->quote($a_task_id, "integer") .
            " AND purpose = " . $this->db->quote($a_purpose, "text"));
    }

    public function deleteAccessTokenByCorrectorId(int $a_corrector_id)
    {
		$this->db->manipulate("DELETE access_token FROM xlas_access_token AS access_token"
            . " LEFT JOIN xlas_corrector AS corrector ON (access_token.user_id = corrector.user_id)"
            . " WHERE corrector.id = " . $this->db->quote($a_corrector_id, "integer"));
    }

    public function deleteAccessTokenByWriterId(int $a_writer_id)
    {
		$this->db->manipulate("DELETE access_token FROM xlas_access_token AS access_token"
            . " LEFT JOIN xlas_writer AS writer ON (access_token.user_id = writer.user_id)"
            . " WHERE writer.id = " . $this->db->quote($a_writer_id, "integer"));
    }

	/**
	 * @param int $a_task_id
	 * @return WriterHistory[]
	 */
	public function getLastWriterHistoryPerUserByTaskId(int $a_task_id): array
	{
		$res = $this->db->queryf("SELECT wh.id as id, wh.essay_id as essay_Id, max(wh.timestamp) as maxc 
                     FROM xlas_writer_history AS wh
                     LEFT JOIN xlas_essay AS e ON (wh.essay_id = e.id) 
                     WHERE e.task_id = %s group by essay_id", ['integer'], [$a_task_id]);

		$ids = [];
		while ($row = $this->db->fetchAssoc($res)) {
			$ids[] = $row["id"];
		}

		if (count($ids) <= 0) {
			return [];
		}
		$query = "SELECT * FROM xlas_writer_history WHERE " . $this->db->in("id", $ids, false, "integer");
		return $this->queryRecords($query, WriterHistory::model());
	}

	/**
	 * @param int $from_corrector
	 * @param int $to_corrector
	 * @param int $essay_id
	 * @return void
	 */
	public function moveCorrectorSummaries(int $from_corrector, int $to_corrector, int $essay_id)
	{
		$this->db->update(CorrectorSummary::tableName(),
			["corrector_id" => ['integer', $to_corrector]],
			["corrector_id" => ['integer', $from_corrector], "essay_id" => ['integer', $essay_id]]);
	}

	public function moveCorrectorComments(int $from_corrector, int $to_corrector, int $essay_id)
	{
		$this->db->update(CorrectorComment::tableName(),
			["corrector_id" => ['integer', $to_corrector]],
			["corrector_id" => ['integer', $from_corrector], "essay_id" => ['integer', $essay_id]]);
	}

	/**
	 * @param int $writer_ids
	 * @return CorrectorSummary[][]|null
	 */
	public function getCorrectorSummariesByTaskIdAndWriterIds(int $task_id, array $writer_ids): array
	{
		$query = "SELECT summary.*, essay.writer_id as writer_id FROM " . CorrectorSummary::tableName() . " as summary " .
			" LEFT JOIN " . Essay::tableName() . " as essay ON (summary.essay_id = essay.id) " .
		" WHERE " . $this->db->in("essay.writer_id", $writer_ids, false, "integer") .
		" AND essay.task_id = " . $this->db->quote($task_id, "integer");

		$modified_model = new class () extends CorrectorSummary{
			public int $writer_id;
			public static function tableOtherTypes() : array
			{
				$other_types = parent::tableOtherTypes();
				$other_types["writer_id"] = "integer";
				return $other_types;
			}
			public static function model() {
				return new self();
			}
		};

		$summaries = [];
		/** @var CorrectorSummary $summary*/
		foreach($this->queryRecords($query, $modified_model::model()) as $summary){
			$summaries[$summary->writer_id][$summary->getCorrectorId()] = $summary;
		}
		return $summaries;
	}

	public function deleteCorrectorSummaryByCorrectorIdAndEssayId(int $a_corrector_id, int $a_essay_id)
	{
		$this->db->manipulate("DELETE FROM xlas_corrector_summary" .
			" WHERE corrector_id = " . $this->db->quote($a_corrector_id, "integer")) .
		    " AND essay_id = " . $this->db->quote($a_essay_id, "integer");
	}

	public function deleteCorrectorCommentByCorrectorIdAndEssayId(int $a_corrector_id, int $a_essay_id)
	{
		$this->db->manipulate("DELETE FROM xlas_corrector_comment" .
			" WHERE corrector_id = " . $this->db->quote($a_corrector_id, "integer")).
		" AND essay_id = " . $this->db->quote($a_essay_id, "integer");

		$this->deleteCriterionPointsByCorrectorIdAndEssayId($a_corrector_id, $a_essay_id);
	}

	public function deleteCriterionPointsByCorrectorIdAndEssayId(int $a_corrector_id, int $a_essay_id){

		$this->db->manipulate("DELETE cp FROM xlas_crit_points AS cp"
			. " LEFT JOIN xlas_corrector_comment AS cc ON (cp.corr_comment_id = cc.id)"
			. " WHERE cc.corrector_id = " . $this->db->quote($a_corrector_id, "integer"))
		. " AND cc.essay_id = " . $this->db->quote($a_essay_id, "integer");
	}
}

