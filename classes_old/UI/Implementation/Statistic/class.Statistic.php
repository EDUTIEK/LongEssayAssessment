<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

class Statistic implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
{
    private ?int $count = null;
    private ?int $final = null;
    private ?int $not_attended = null;
    private ?int $passed = null;
    private ?int $not_passed = null;
    private ?float $not_passed_quota = null;
    private ?float $average_points = null;
    private ?array $grades = null;
    private string $title;
    private ?string $description = null;
    private string $count_label;

    private string $final_label;
    /**
     * @var string[]
     */
    private ?array $pseudonym = null;
    private ?string $own_grade = null;

    public function __construct(string $title, int $count, string $count_label, int $final, string $final_label)
    {
        $this->title = $title;
        $this->count = $count;
        $this->count_label = $count_label;
        $this->final = $final;
        $this->final_label = $final_label;
    }

    public function withTitle(string $title): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    public function withDescription(string $description): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withCountLabel(string $count_label): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->count_label = $count_label;

        return $clone;
    }

    public function withFinalLabel(string $final_label): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->final_label = $final_label;

        return $clone;
    }

    public function withCount(int $count): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->count = $count;

        return $clone;
    }

    public function withFinal(int $final): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->final = $final;

        return $clone;
    }

    public function withNotAttended(int $not_attended): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->not_attended = $not_attended;

        return $clone;
    }

    public function withPassed(int $passed): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->passed = $passed;

        return $clone;
    }

    public function withNotPassed(int $not_passed): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->not_passed = $not_passed;

        return $clone;
    }

    public function withNotPassedQuota(float $not_passed_quota
    ): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic {
        $clone = clone $this;
        $clone->not_passed_quota = $not_passed_quota;

        return $clone;
    }

    public function withAveragePoints(float $average_points): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->average_points = $average_points;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withGrades(array $grades): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->grades = $grades;

        return $clone;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getFinal(): int
    {
        return $this->final;
    }

    public function getNotAttended(): ?int
    {
        return $this->not_attended;
    }

    public function getPassed(): ?int
    {
        return $this->passed;
    }

    public function getNotPassed(): ?int
    {
        return $this->not_passed;
    }

    public function getNotPassedQuota(): ?float
    {
        return $this->not_passed_quota;
    }

    public function getAveragePoints(): ?float
    {
        return $this->average_points;
    }

    public function getGrades(): ?array
    {
        return $this->grades;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCanonicalName(): string
    {
        return "Statistic";
    }

    public function getFinalLabel(): string
    {
        return $this->final_label;
    }

    public function getCountLabel(): string
    {
        return $this->count_label;
    }

    public function withPseudonym(array $pseudonym): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->pseudonym = $pseudonym;

        return $clone;
    }

    public function withOwnGrade(string $own_grade): \ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic
    {
        $clone = clone $this;
        $clone->own_grade = $own_grade;

        return $clone;
    }

    public function getPseudonym(): ?array
    {
        return $this->pseudonym;
    }

    public function getOwnGrade(): ?string
    {
        return $this->own_grade;
    }
}