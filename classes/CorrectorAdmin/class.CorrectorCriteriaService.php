<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\CorrectorPoints;

class CorrectorCriteriaService
{
    public function __construct(
        private ObjectRepository $object_repo,
        private CorrectorRepository $corrector_repo,
        private EssayRepository $essay_repo
    ) {
    }

    public function changeCriteriaMode(int $task_id, string $old_mode, string $new_mode)
    {
        switch ($old_mode. '->' . $new_mode) {
            case CorrectionSettings::CRITERIA_MODE_NONE . '->' . CorrectionSettings::CRITERIA_MODE_FIXED:
            case CorrectionSettings::CRITERIA_MODE_NONE . '->' . CorrectionSettings::CRITERIA_MODE_CORRECTOR:
                $this->purgeAllPoints($task_id);
                break;

            case CorrectionSettings::CRITERIA_MODE_FIXED . '->' . CorrectionSettings::CRITERIA_MODE_NONE:
            case CorrectionSettings::CRITERIA_MODE_CORRECTOR . '->' . CorrectionSettings::CRITERIA_MODE_NONE:
                $this->purgeCriteriaInPoints($task_id);
                $this->deleteAllCriteria($task_id);
                break;

            case CorrectionSettings::CRITERIA_MODE_FIXED . '->' . CorrectionSettings::CRITERIA_MODE_CORRECTOR:
                $this->distributeFixedCriteriaWithPoints($task_id);
                break;

            case CorrectionSettings::CRITERIA_MODE_CORRECTOR . '->' . CorrectionSettings::CRITERIA_MODE_FIXED:
                $this->purgeAllPoints($task_id);
                $this->deletePersonalCriteria($task_id);
                break;
        }
    }

    /**
     * Purge all points given by correctors in the task
     */
    private function purgeAllPoints(int $task_id)
    {
        foreach ($this->essay_repo->getEssaysByTaskId($task_id) as $essay) {
            $this->essay_repo->deleteCorrectorPointsByEssayId($essay->getId());
        }
    }

    /**
     * Copy general criteria to the correctors and re-assign the points
     */
    private function distributeFixedCriteriaWithPoints(int $task_id)
    {

        $fixed_criteria = [];
        foreach ($this->object_repo->getRatingCriteriaByObjectId($task_id) as $criterion) {
            if ($criterion->getCorrectorId() === null) {
                $fixed_criteria[$criterion->getId()] = $criterion;
            }
        }

        foreach ($this->corrector_repo->getCorrectorsByTaskId($task_id) as $corrector) {

            $matching = [];
            foreach ($fixed_criteria as $criterion) {
                $corr_criterion = clone($criterion);
                $corr_criterion->setId(0);
                $corr_criterion->setCorrectorId($corrector->getId());
                $this->corrector_repo->save($corr_criterion);
                $matching[$criterion->getId()] = $corr_criterion->getId();
            }

            foreach ($this->essay_repo->getEssaysByTaskId($task_id) as $essay) {
                foreach ($this->essay_repo->getCorrectorPointsByEssayIdAndCorrectorId(
                    $essay->getId(), $corrector->getId()) as $points) {
                    if (isset($matching[$points->getCriterionId()])) {
                        $points->setCriterionId($matching[$points->getCriterionId()]);
                        $this->essay_repo->save($points);
                    }
                }
            }
        }

        foreach ($fixed_criteria as $criterion) {
            $this->object_repo->deleteRatingCriterion($criterion->getId());
        }
    }

    /**
     * Sum up criteria points that are assigned to comments
     * Delete the points that are only assigned to criteria
     */
    private function purgeCriteriaInPoints(int $task_id)
    {
        foreach ($this->corrector_repo->getCorrectorsByTaskId($task_id) as $corrector) {
            foreach ($this->essay_repo->getEssaysByTaskId($task_id) as $essay) {

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
    private function deleteAllCriteria($task_id)
    {
        $this->object_repo->deleteRatingCriterionByObjectId($task_id);
    }

    /**
     * Delete the individual rating criteria of correctors
     */
    private function deletePersonalCriteria($task_id)
    {
        foreach ($this->corrector_repo->getCorrectorsByTaskId($task_id) as $corrector) {
            $this->object_repo->deleteRatingCriterionByObjectIdAndCorrectorId($task_id, $corrector->getId());
        }
    }
}