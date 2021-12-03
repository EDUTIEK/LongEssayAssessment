<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class EssayDatabaseRepository implements EssayRepository
{

	public function createEssay(Essay $a_essay)
	{
		$a_essay->create();
	}

	public function getEssayById(int $a_id): ?Essay
	{
		$essay = Essay::findOrGetInstance($a_id);
		if ($essay != null) {
			return $essay;
		}
		return null;
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
		return ( $this->getEssayById($a_id) != null );
	}

	public function updateEssay(Essay $a_essay)
	{
		$a_essay->update();
	}

	public function deleteEssay(int $a_id)
	{
		$essay = $this->getEssayById($a_id);

		if ( $essay != null ){
			$essay->delete();
		}
	}

    public function createWriterHistory(WriterHistory $a_writer_history)
    {
        // TODO: Implement createWriterHistory() method.
    }

    public function createCorrectorSummary(CorrectorSummary $a_corrector_summary)
    {
        // TODO: Implement createCorrectorSummary() method.
    }

    public function createCorrectorComment(CorrectorComment $a_corrector_comment)
    {
        // TODO: Implement createCorrectorComment() method.
    }

    public function createCriterionPoints(CriterionPoints $a_criterion_points)
    {
        // TODO: Implement createCriterionPoints() method.
    }

    public function createAccessToken(AccessToken $a_access_token)
    {
        // TODO: Implement createAccessToken() method.
    }

    public function updateWriterHistory(WriterHistory $a_writer_history)
    {
        // TODO: Implement updateWriterHistory() method.
    }

    public function updateCorrectorSummary(CorrectorSummary $a_corrector_summary)
    {
        // TODO: Implement updateCorrectorSummary() method.
    }

    public function updateCorrectorComment(CorrectorComment $a_corrector_comment)
    {
        // TODO: Implement updateCorrectorComment() method.
    }

    public function updateCriterionPoints(CriterionPoints $a_criterion_points)
    {
        // TODO: Implement updateCriterionPoints() method.
    }

    public function deleteWriterHistory(int $a_id)
    {
        // TODO: Implement deleteWriterHistory() method.
    }

    public function deleteCorrectorSummary(int $a_id)
    {
        // TODO: Implement deleteCorrectorSummary() method.
    }

    public function deleteCorrectorComment(int $a_id)
    {
        // TODO: Implement deleteCorrectorComment() method.
    }

    public function deleteCriterionPoints(int $a_id)
    {
        // TODO: Implement deleteCriterionPoints() method.
    }

    public function deleteAccessToken(int $a_id)
    {
        // TODO: Implement deleteAccessToken() method.
    }

    public function deleteEssayByTaskId(int $a_id)
    {
        // TODO: Implement deleteEssayByTaskId() method.
    }
}

