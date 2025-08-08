<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorPoints;
use ILIAS\Plugin\LongEssayAssessment\BaseService;

class CorrectorCriteriaService extends BaseService
{
    private ObjectRepository $object_repo;
    private CorrectorRepository $corrector_repo;
    private EssayRepository $essay_repo;

    private int $task_id;

    public function __construct(int $task_id)
    {
        parent::__construct();
        $this->task_id = $task_id;
        
        $this->object_repo = $this->localDI->getObjectRepo();
        $this->corrector_repo = $this->localDI->getCorrectorRepo();
        $this->essay_repo = $this->localDI->getEssayRepo();
    }

    /**
     * Do the neccessary cleanup if the criteria mode is changed
     */
    public function changeCriteriaMode(string $old_mode, string $new_mode)
    {
        switch ($old_mode. '->' . $new_mode) {
            case CorrectionSettings::CRITERIA_MODE_NONE . '->' . CorrectionSettings::CRITERIA_MODE_FIXED:
            case CorrectionSettings::CRITERIA_MODE_NONE . '->' . CorrectionSettings::CRITERIA_MODE_CORRECTOR:
                $this->purgeAllPoints();
                break;

            case CorrectionSettings::CRITERIA_MODE_FIXED . '->' . CorrectionSettings::CRITERIA_MODE_NONE:
            case CorrectionSettings::CRITERIA_MODE_CORRECTOR . '->' . CorrectionSettings::CRITERIA_MODE_NONE:
                $this->purgeCriteriaInPoints();
                $this->deleteAllCriteria();
                break;

            case CorrectionSettings::CRITERIA_MODE_FIXED . '->' . CorrectionSettings::CRITERIA_MODE_CORRECTOR:
                $this->copyFixedCriteriaWithPoints();
                break;

            case CorrectionSettings::CRITERIA_MODE_CORRECTOR . '->' . CorrectionSettings::CRITERIA_MODE_FIXED:
                $this->purgeAllPoints();
                $this->deletePersonalCriteria();
                break;
        }
    }

    /**
     * Purge all points given by correctors in the task
     */
    private function purgeAllPoints()
    {
        foreach ($this->essay_repo->getEssaysByTaskId($this->task_id) as $essay) {
            $this->essay_repo->deleteCorrectorPointsByEssayId($essay->getId());
        }
    }

    /**
     * Copy general criteria to the correctors and re-assign the points
     */
    private function copyFixedCriteriaWithPoints()
    {

        $fixed_criteria = [];
        foreach ($this->object_repo->getRatingCriteriaByObjectId($this->task_id) as $criterion) {
            if ($criterion->getCorrectorId() === null) {
                $fixed_criteria[$criterion->getId()] = $criterion;
            }
        }

        foreach ($this->corrector_repo->getCorrectorsByTaskId($this->task_id) as $corrector) {

            $matching = [];
            foreach ($fixed_criteria as $criterion) {
                $corr_criterion = clone($criterion);
                $corr_criterion->setId(0);
                $corr_criterion->setCorrectorId($corrector->getId());
                $this->corrector_repo->save($corr_criterion);
                $matching[$criterion->getId()] = $corr_criterion->getId();
            }

            foreach ($this->essay_repo->getEssaysByTaskId($this->task_id) as $essay) {
                foreach ($this->essay_repo->getCorrectorPointsByEssayIdAndCorrectorId(
                    $essay->getId(), $corrector->getId()) as $points) {
                    if (isset($matching[$points->getCriterionId()])) {
                        $points->setCriterionId($matching[$points->getCriterionId()]);
                        $this->essay_repo->save($points);
                    }
                }
            }
        }
    }

    /**
     * Sum up criteria points that are assigned to comments
     * Delete the points that are only assigned to criteria
     */
    private function purgeCriteriaInPoints()
    {
        foreach ($this->corrector_repo->getCorrectorsByTaskId($this->task_id) as $corrector) {
            foreach ($this->essay_repo->getEssaysByTaskId($this->task_id) as $essay) {

                $comment_points = [];
                foreach ($this->essay_repo->getCorrectorPointsByEssayIdAndCorrectorId(
                    $essay->getId(), $corrector->getId()) as $points) {
                    if ($points->getCommentId() !== null) {
                        $comment_points[$points->getCommentId()] = ($comment_points[$points->getCommentId()] ?? 0) + $points->getPoints();
                    }
                }

                $this->essay_repo->deleteCorrectorPointsByCorrectorIdAndEssayId($corrector->getId(), $essay->getId());

                foreach ($comment_points as $comment_id => $sum_of_points) {
                    $points = (new CorrectorPoints())
                        ->setEssayId($essay->getId())
                        ->setCorrectorId($corrector->getId())
                        ->setCommentId($comment_id)
                        ->setPoints($sum_of_points);
                    $this->essay_repo->save($points);
                }
            }
        }
    }

    /**
     * Delete all general and personal rating criteria
     */
    private function deleteAllCriteria()
    {
        $this->object_repo->deleteRatingCriterionByObjectId($this->task_id);
    }

    /**
     * Delete the individual rating criteria of correctors
     */
    private function deletePersonalCriteria()
    {
        foreach ($this->corrector_repo->getCorrectorsByTaskId($this->task_id) as $corrector) {
            $this->object_repo->deleteRatingCriterionByObjectIdAndCorrectorId($this->task_id, $corrector->getId());
        }
    }

    /**
     * Check if the corrector has points given to comments without criteria
     */
    public function hasCorrectorPointsWithoutCriteria(int $corrector_id): bool
    {
        return $this->essay_repo->hasCorrectorPointsWithoutCriteria($corrector_id);
    }

    /**
     * Delete points given by a corrector to comments without criteria
     */
    public function deleteCorrectorPointsWithoutCriteria(int $corrector_id)
    {
         $this->essay_repo->deleteCorrectorPointsWithoutCriteria($corrector_id);
    }
}