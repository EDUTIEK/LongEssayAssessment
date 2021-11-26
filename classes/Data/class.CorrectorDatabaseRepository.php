<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

use ILIAS\DI\Exceptions\Exception;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CorrectorDatabaseRepository implements CorrectorRepository
{

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

    public function createCorrector(Corrector $a_corrector)
    {
        $a_corrector->create();
    }

    public function createCorrectorAssignment(CorrectorAssignment $a_corrector_assignment)
    {
        $a_corrector_assignment->create();
    }

    public function getCorrectorById(int $a_id): ?Corrector
    {
        $corrector = Corrector::findOrGetInstance($a_id);
        if ($corrector != null) {
            return $corrector;
        }
        return null;
    }

    public function getCorrectorsByTaskId(int $a_task_id): array
    {
        return Corrector::where(['task_id' => $a_task_id])->get();
    }

    public function getCorrectorByUserId(int $a_user_id, int $a_task_id): ?Corrector
    {
        $correctors = Corrector::where(['user_id' => $a_user_id, 'task_id' => $a_task_id])->get();
        if (count($correctors) > 0) {
            return $correctors[1];
        }
        return null;
    }

    public function ifUserExistsInTaskAsCorrector(int $a_user_id, int $a_task_id): bool
    {
        return $this->getCorrectorByUserId($a_user_id, $a_task_id) != null;
    }

    public function getCorrectorAssignmentById(int $a_id): ?CorrectorAssignment
    {
        $assignment = CorrectorAssignment::findOrGetInstance($a_id);
        if ($assignment != null) {
            return $assignment;
        }
        return null;
    }

    public function getCorrectorAssignmentByPartIds(int $a_writer_id, int $a_corrector_id): ?CorrectorAssignment
    {
        $assignments = CorrectorAssignment
            ::where(['writer_id' => $a_writer_id, 'corrector_id' => $a_corrector_id])->get();

        if (count($assignments) > 0) {
            return $assignments[1];
        }
        return null;
    }

    public function getAssignmentsByWriterId(int $a_writer_id): array
    {
        return CorrectorAssignment::where(['writer_id' => $a_writer_id])->get();
    }

    public function getAssignmentsByCorrectorId(int $a_corrector_id): array
    {
        return CorrectorAssignment::where(['corrector_id' => $a_corrector_id])->get();
    }

    public function ifCorrectorIsAssigned(int $a_writer_id, int $a_corrector_id): bool
    {
        return $this->getCorrectorAssignmentByPartIds($a_writer_id, $a_corrector_id) != null;
    }

    public function updateCorrector(Corrector $a_corrector)
    {
       $a_corrector->update();
    }

    public function updateCorrectorAssignment(CorrectorAssignment $a_corrector_assignment)
    {
        $a_corrector_assignment->update();
    }

    /**
     * Also deletes all assignments of this corrector
     *
     * @param int $a_id
     * @throws \ilDatabaseException
     */
    public function deleteCorrector(int $a_id)
    {
        global $DIC;

        $corrector = $this->getCorrectorById($a_id);

        if ( $corrector != null ){
            $DIC->database()->beginTransaction();
            try {
                $this->deleteCorrectorAssignmentByCorrector($corrector->getId());
                // TODO: AccessToken, CorrectorComment
                $corrector->delete();
            }catch (\ilDatabaseException $e)
            {
                $DIC->database()->rollback();
                throw $e;
            }

            $DIC->database()->commit();
        }
    }

    public function deleteCorrectorAssignment(int $a_writer_id, int $a_corrector_id)
    {
        global $DIC;
        $DIC->database()->manipulate("DELETE FROM xlet_corrector_ass".
            " WHERE writer_id = ". $DIC->database()->quote($a_writer_id, "integer") .
            " AND corrector_id = " . $DIC->database()->quote($a_corrector_id, "integer"));
    }

    public function deleteCorrectorAssignmentByTask(int $a_task_id)
    {
        global $DIC;
        $DIC->database()->manipulate("DELETE xlet_corrector_ass FROM xlet_corrector_ass AS ass"
            . " LEFT JOIN xlet_corrector AS corrector ON (ass.corrector_id = corrector.id)"
            . " WHERE corrector.task_id = ".$DIC->database()->quote($a_task_id, "integer"));
    }

    public function deleteCorrectorAssignmentByCorrector(int $a_corrector_id)
    {
        global $DIC;
        $DIC->database()->manipulate("DELETE FROM xlet_corrector_ass".
            " WHERE corrector_id = " . $DIC->database()->quote($a_corrector_id, "integer"));
    }

    public function deleteCorrectorAssignmentByWriter(int $a_writer_id)
    {
        global $DIC;
        $DIC->database()->manipulate("DELETE FROM xlet_corrector_ass".
            " WHERE writer_id = ". $DIC->database()->quote($a_writer_id, "integer"));
    }

    public function deleteCorrectorByTask(int $a_task_id)
    {
        global $DIC;
        $DIC->database()->manipulate("DELETE FROM xlet_corrector".
            " WHERE task_id = ". $DIC->database()->quote($a_task_id, "integer"));
    }
}