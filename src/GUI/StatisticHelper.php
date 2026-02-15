<?php

namespace ILIAS\Plugin\LongEssayAssessment;

use Edutiek\AssessmentService\Views\Data\StatisticView;
use ILIAS\Plugin\LongEssayAssessment\UI\Factory;
use ILIAS\Plugin\LongEssayAssessment\UI\Statistic\Statistic;

trait StatisticHelper
{
    protected \ilLongEssayAssessmentPlugin $plugin;
    protected \ilLanguage $lng;

    protected function buildStatistic(StatisticView $view, bool $is_writer, ?string $title = null): Statistic
    {
        $factory = $this->plugin->dic()->uiFactory();

        if (empty($title)) {
            $title = $view->getTitle();
        }
        $count = $is_writer ? $this->plugin->txt('essay_count') : $this->plugin->txt('correction_count');

        $statistic = $factory->statistic()->statistic(
            $title,
            $view->getCount(),
            $count
        )->withNotPassed($view->getNotPassed())
         ->withPassed($view->getPassed())
         ->withAveragePoints($view->getAveragePoints() ?? 0)
         ->withNotPassedQuota($view->getNotPassedQuota() ?? 0);

        if ($is_writer) {
            $statistic = $statistic->withNotAttended($view->getNotAttended());
        } else {
            $statistic = $statistic->withfinal($view->getAttended())
                                   ->withFinalLabel($this->plugin->txt('correction_final'));
        }

        if ($view->isGradesUniform()) {
            $statistic = $statistic->withGrades($view->getGradeCounts());
        }

        if ($view->isMaxPointUniform()) {
            $statistic = $statistic->withPoints($view->getPointsCounts());
        }

        return $statistic;
    }

    /**
     * @param StatisticView[] $views
     * @param bool  $is_writer
     * @return string
     */
    protected function buildStatisticExport(array $views, bool $is_writer, bool $has_object_title): \ilCSVWriter
    {
        $count = $is_writer ? $this->plugin->txt('essay_count') : $this->plugin->txt('correction_count');
        $final = $is_writer ? $this->plugin->txt('essay_final') : $this->plugin->txt('correction_final');

        $csv = new \ilCSVWriter();
        $csv->setSeparator(';');
        $csv->setDelimiter('"');
        $csv->addColumn($this->lng->txt('login'));
        $csv->addColumn($this->lng->txt('firstname'));
        $csv->addColumn($this->lng->txt('lastname'));
        $csv->addColumn($this->lng->txt('matriculation'));
        if($has_object_title) {
            $csv->addColumn($this->plugin->txt('assessment'));
        }
        $csv->addColumn($count);
        $csv->addColumn($final);
        $csv->addColumn($this->plugin->txt('statistic_not_attended'));
        $csv->addColumn($this->plugin->txt('statistic_passed'));
        $csv->addColumn($this->plugin->txt('statistic_not_passed'));
        $csv->addColumn($this->plugin->txt('essay_not_passed_quota'));
        $csv->addColumn($this->plugin->txt('essay_average_points'));

        foreach (!empty($views) ? current($views)->getGradeCounts() : [] as $key => $value) {
            $csv->addColumn(mb_convert_encoding($key, 'ISO-8859-1', 'UTF-8'));
        }

        foreach ($views as $view) {
            $csv->addRow();
            $user = $view->getUser();

            $csv->addColumn(mb_convert_encoding($user->getLogin(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($user->getFirstname(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($user->getLastname(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($user->getMatriculation(), 'ISO-8859-1', 'UTF-8'));
            if($has_object_title) {
                $first = current($view->getAssessments());
                $csv->addColumn(mb_convert_encoding($first ? $first->getTitle() : "", 'ISO-8859-1', 'UTF-8'));
            }
            $csv->addColumn(mb_convert_encoding((string)$view->getCount(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding((string)$view->getAttended(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding((string)$view->getNotAttended(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding((string)$view->getPassed(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding((string)$view->getNotPassed(), 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($view->getNotPassedQuota() !== null ? sprintf('%.2f', $view->getNotPassedQuota()) : "", 'ISO-8859-1', 'UTF-8'));
            $csv->addColumn(mb_convert_encoding($view->getAveragePoints() !== null ? sprintf('%.2f', $view->getAveragePoints()) : "", 'ISO-8859-1', 'UTF-8'));

            foreach ($view->getGradeCounts() as $value) {
                $csv->addColumn(mb_convert_encoding((string)$value, 'ISO-8859-1', 'UTF-8'));

            }
        }

       return $csv;
    }
}
