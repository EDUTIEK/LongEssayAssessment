<?php

namespace ILIAS\Plugin\LongEssayAssessment\View\Data;

use Edutiek\AssessmentService\System\Data\UserData;
use Edutiek\AssessmentService\Assessment\Data\GradeLevel;
use Edutiek\AssessmentService\Assessment\Data\Properties;

class StatisticView extends \Edutiek\AssessmentService\Views\Data\StatisticView
{
    private int $count;
    private int $finalized;
    private int $passed = 0;
    private int $not_passed = 0;
    private int $attended = 0;
    private int $not_attended = 0;
    private ?float $average_points;
    private ?float $not_passed_quota;

    /**
     * @param UserData|string|null $context        $context
     * @param GradingObject[]|null $grading_objects
     * @param array                $points_counts
     * @param array                $grade_counts
     * @param bool                 $max_point_uniform
     * @param bool                 $grades_uniform
     */
    public function __construct(
        private UserData|string|null $context = null,
        private ?array $grading_objects,
        private array $points_counts,
        private array $grade_counts,
        private bool $max_point_uniform,
        private bool $grades_uniform
    ) {
        $this->count = count($grading_objects);
        $point_sum = 0;
        $sum_finalized = 0;

        foreach ($grading_objects??[] as $obj) {
            $this->attended += $obj->isAttended() ? 1 : 0;
            $this->not_attended += $obj->isAttended() ? 0 : 1;

            if ($obj->isFinalized()) { // Only count finalized grades and points
                $this->passed += $obj->isPassed() ? 1 : 0;
                $this->not_passed += $obj->isPassed() ? 0 : 1;
                $point_sum += $obj->getPoints() ?? 0;
                $sum_finalized += 1;

                $point_key = (string)abs($obj->getPoints()??0);
                $grade_key = $obj->getGrade();

                $this->points_counts[$point_key] = ($this->points_counts[$point_key] ?? 0) + 1;
                if ($grade_key !== null) {
                    $this->grade_counts[$grade_key] = ($this->grade_counts[$grade_key] ?? 0) + 1;
                }
            }
        }
        $this->finalized = $sum_finalized;
        $this->average_points = $sum_finalized > 0 ? $point_sum / $sum_finalized : null;
        $this->not_passed_quota = $this->count > 0 ? $this->not_passed / $this->count : null;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getFinalized(): int
    {
        return $this->finalized;
    }

    public function getPassed(): int
    {
        return $this->passed;
    }

    public function getNotPassed(): int
    {
        return $this->not_passed;
    }

    public function getAttended(): int
    {
        return $this->attended;
    }

    public function getNotAttended(): int
    {
        return $this->not_attended;
    }

    public function getAveragePoints(): ?float
    {
        return $this->average_points;
    }

    public function getNotPassedQuota(): ?float
    {
        return $this->not_passed_quota;
    }

    public function getUser(): ?UserData
    {
        return $this->context;
    }

    public function getTitle(): string
    {
        return $this->context instanceof UserData ? $this->context->getFullName(true) : $this->context ?? "";
    }

    public function getGradingObjects(): ?array
    {
        return $this->grading_objects;
    }

    public function getGradeCounts(): array
    {
        return $this->grade_counts;
    }

    public function getPointsCounts(): array
    {
        return $this->points_counts;
    }

    public function isMaxPointUniform(): bool
    {
        return $this->max_point_uniform;
    }

    public function isGradesUniform(): bool
    {
        return $this->grades_uniform;
    }

    public function getUsers(): array
    {
        return array_unique(array_map(fn (GradingObject $go) => $go->getUserData(), $this->grading_objects??[]), SORT_REGULAR);
    }

    public function getAssessments(): array
    {
        return array_unique(array_map(fn (GradingObject $go) => $go->getAssessment(), $this->grading_objects??[]), SORT_REGULAR);
    }

    public function fromUser(UserData $user): self
    {
        $user_id = $user->getId();

        $grading_objects = array_filter(
            $this->getGradingObjects() ?? [],
            fn (GradingObject $go) => $go->getUserData()->getId() === $user_id
        );
        $grade_counts = array_map(fn ($x) => 0, $this->getGradeCounts());
        $points_counts = array_map(fn ($x) => 0, $this->getPointsCounts());

        return new self($user, $grading_objects, $points_counts, $grade_counts, $this->isMaxPointUniform(), $this->isGradesUniform());
    }

    public function fromAssessent(Properties $assessment): self
    {
        $ass_id = $assessment->getAssId();

        $grading_objects = array_filter(
            $this->getGradingObjects() ?? [],
            fn (GradingObject $go) => $go->getAssessment()->getAssId() === $ass_id
        );
        $grade_counts = array_map(fn ($x) => 0, $this->getGradeCounts());
        $points_counts = array_map(fn ($x) => 0, $this->getPointsCounts());

        return new self($assessment->getTitle(), $grading_objects, $points_counts, $grade_counts, $this->isMaxPointUniform(), $this->isGradesUniform());
    }

    public function fromUserAndAssessemnt(UserData $user, Properties $assessment): self
    {
        $ass_id = $assessment->getAssId();
        $user_id = $user->getId();

        $grading_objects = array_filter(
            $this->getGradingObjects() ?? [],
            fn (GradingObject $go) => $go->getUserData()->getId() === $user_id && $go->getAssessment()->getAssId() === $ass_id
        );

        $grade_counts = array_map(fn ($x) => 0, $this->getGradeCounts());
        $points_counts = array_map(fn ($x) => 0, $this->getPointsCounts());

        return new self($user, $grading_objects, $points_counts, $grade_counts, $this->isMaxPointUniform(), $this->isGradesUniform());
    }

}
