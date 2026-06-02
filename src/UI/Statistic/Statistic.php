<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Statistic;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\ComponentHelper;

class Statistic implements Component
{
    use ComponentHelper;

    private ?int $count = null;
    private ?int $final = null;
    private ?int $not_attended = null;
    private ?int $passed = null;
    private ?int $not_passed = null;
    private ?float $not_passed_quota = null;
    private ?float $average_points = null;
    private ?array $grades = null;
    private ?array $points = null;
    private string $title;
    private ?string $description = null;
    private string $count_label;

    private string $final_label;
    /**
     * @var string[]
     */
    private ?array $pseudonym = null;
    private ?string $own_grade = null;

    public function __construct(string $title, int $count, string $count_label)
    {
        $this->title = $title;
        $this->count = $count;
        $this->count_label = $count_label;
    }

    public function withTitle(string $title): Statistic
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    public function withDescription(string $description): Statistic
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function withCountLabel(string $count_label): Statistic
    {
        $clone = clone $this;
        $clone->count_label = $count_label;

        return $clone;
    }

    public function withFinalLabel(string $final_label): Statistic
    {
        $clone = clone $this;
        $clone->final_label = $final_label;

        return $clone;
    }

    public function withCount(int $count): Statistic
    {
        $clone = clone $this;
        $clone->count = $count;

        return $clone;
    }

    public function withFinal(?int $final): Statistic
    {
        $clone = clone $this;
        $clone->final = $final;

        return $clone;
    }

    public function withNotAttended(int $not_attended): Statistic
    {
        $clone = clone $this;
        $clone->not_attended = $not_attended;

        return $clone;
    }

    public function withPassed(int $passed): Statistic
    {
        $clone = clone $this;
        $clone->passed = $passed;

        return $clone;
    }

    public function withNotPassed(int $not_passed): Statistic
    {
        $clone = clone $this;
        $clone->not_passed = $not_passed;

        return $clone;
    }

    public function withNotPassedQuota(float $not_passed_quota): Statistic
    {
        $clone = clone $this;
        $clone->not_passed_quota = $not_passed_quota;

        return $clone;
    }

    public function withAveragePoints(float $average_points): Statistic
    {
        $clone = clone $this;
        $clone->average_points = $average_points;

        return $clone;
    }

    public function withGrades(array $grades): Statistic
    {
        $clone = clone $this;
        $clone->grades = $grades;

        return $clone;
    }

    public function withPoints(array $points): Statistic
    {
        $clone = clone $this;
        $clone->points = $points;

        return $clone;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getFinal(): ?int
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

    public function getPoints(): ?array
    {
        return $this->points;
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

    public function withPseudonym(array $pseudonym): Statistic
    {
        $clone = clone $this;
        $clone->pseudonym = $pseudonym;

        return $clone;
    }

    public function withOwnGrade(string $own_grade): Statistic
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
