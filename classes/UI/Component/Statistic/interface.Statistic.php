<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;


interface Statistic extends StatisticItem
{
    public function withDescription(string $description): Statistic;
    public function withFinalLabel(string $final_label): Statistic;
    public function withCountLabel(string $count_label): Statistic;
    public function withCount(int $count): Statistic;

    public function withFinal(int $final): Statistic;

    public function withNotAttended(int $not_attended): Statistic;

    public function withPassed(int $passed): Statistic;

    public function withNotPassed(int $not_passed): Statistic;

    public function withNotPassedQuota(float $not_passed_quota): Statistic;

    public function withAveragePoints(float $average_points): Statistic;

    /**
     * @param int[] $grades
     * @return Statistic
     */
    public function withGrades(array $grades): Statistic;

    /**
     * @param string[] $pseudonym
     * @return Statistic
     */
    public function withPseudonym(array $pseudonym): Statistic;

    public function withOwnGrade(string $own_grade): Statistic;
    public function getCount(): int;
    public function getFinal(): int;
    public function getNotAttended(): ?int;
    public function getPassed(): ?int;
    public function getNotPassed(): ?int;
    public function getNotPassedQuota(): ?float;
    public function getAveragePoints(): ?float;
    public function getGrades(): ?array;
    public function getDescription(): ?string;
    public function getFinalLabel(): string;
    public function getCountLabel(): string;
    public function getPseudonym(): ?array;
    public function getOwnGrade(): ?string;
}