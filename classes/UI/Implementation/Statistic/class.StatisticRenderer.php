<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Renderer;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;
use ILIAS\UI\Component\Table\PresentationRow;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\StatisticItem;
use ILIAS\UI\Implementation\Component\Symbol\Glyph\Glyph;
use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Component\Panel\Sub;
use ILIAS\UI\Component\Chart\Bar\BarConfig;
use ILIAS\UI\Component\Chart\Bar\XAxis;
use ILIAS\UI\Component\Chart\Bar\YAxis;

class StatisticRenderer extends AbstractComponentRenderer
{
    private ?array $files_cache = null;

    /**
     * @inheritdoc
     */
    public function render(Component $component, Renderer $default_renderer): string
    {
        $this->checkComponent($component);

        switch (true) {
            case ($component instanceof Statistic):
                return $this->renderStatistic($component, $default_renderer);
            case ($component instanceof GraphStatisticGroup):
                return $this->renderGraphStatisticGroup($component, $default_renderer);
            case ($component instanceof ExtendableStatisticGroup):
                return $this->renderExtendableStatisticGroup($component, $default_renderer);
            default:
                throw new \LogicException("Cannot render '" . get_class($component) . "'");
        }
    }


    protected function getComponentInterfaceName(): array
    {
        return [Statistic::class, GraphStatisticGroup::class, ExtendableStatisticGroup::class];
    }

    /**
     * @param $name
     * @return mixed|string
     */
    protected function getTemplatePath($name) : string
    {
        if (in_array($name, $this->getPluginTemplateFiles())) {
            return "Statistic/$name";
        }

        return "src/UI/templates/default/Item/$name";
    }

    protected function getPluginTemplateFiles(): array
    {
        if ($this->files_cache === null) {
            $this->files_cache = array_filter(
                scandir(dirname(__FILE__) . "/../../../templates/Item"),
                function ($item) {
                    return str_starts_with($item, "tpl.");
                }
            );
        }

        return $this->files_cache;
    }

    protected function buildStatistic(Statistic $component): Sub
    {
        $items = [];
        $items[$component->getCountLabel()] = (string)$component->getCount();
        $items[$component->getFinalLabel()] = (string)$component->getFinal();

        if($component->getNotAttended() !== null) {
            $items[$this->pluginTxt('statistic_not_attended')] = (string)$component->getNotAttended();
        }
        $items[$this->pluginTxt('statistic_passed')] = (string)$component->getPassed();
        $items[$this->pluginTxt('statistic_not_passed')] = (string)$component->getNotPassed();

        if($component->getNotPassedQuota() !== null) {
            $perc = 100 * $component->getNotPassedQuota();
            $items[$this->pluginTxt('essay_not_passed_quota')] = sprintf('%.1f', $perc) . '%';
        }

        if($component->getAveragePoints() !== null) {
            $items[$this->pluginTxt('essay_average_points')] = sprintf('%.2f', $component->getAveragePoints());
        }

        list($grades, $chart) = $this->buildGradesAndGraph($component);

        $root = $this->getUIFactory()->panel()->sub($component->getTitle(), $this->getUIFactory()->listing()->characteristicValue()->text($items));

        if(!empty($grades)) {
            $root = $root->withFurtherInformation(
                $this->getUIFactory()->card()->standard("<h5>" . $this->pluginTxt('grade_distribution') . "</h5>")->withSections([
                    $chart,
                    $this->getUIFactory()->listing()->characteristicValue()->text($grades)
                ])
            );
        }
        return $root;
    }

    protected function renderStatistic(Statistic $component, Renderer $default_renderer): string
    {
        return $default_renderer->render($this->getUIFactory()->panel()->report($component->getTitle(), [$this->buildStatistic($component->withTitle(""))]));
    }

    protected function renderGraphStatisticGroup(GraphStatisticGroup $component, Renderer $default_renderer): string
    {
        $panels = [];

        foreach($component->getStatistics() as $statistic) {
            if($statistic instanceof Statistic) {
                $panels[] = $this->buildStatistic($statistic);
            } else {
                $panels[] = $this->getUIFactory()->panel()->sub($statistic->getTitle(), []);
            }
        }

        $root = $this->getUIFactory()->panel()->report($component->getTitle(), $panels);

        return $default_renderer->render($root);
    }

    protected function renderExtendableStatisticGroup(ExtendableStatisticGroup $component, Renderer $default_renderer): string
    {
        return $default_renderer->render($this->getUIFactory()->table()->presentation(
            $component->getTitle(),
            [],
            function (PresentationRow $row, StatisticItem $record, $ui_factory, $environment) use ($default_renderer) { //mapping-closure
                $pseudonym = [];
                $fproperties = [];
                $properties = [];

                if($record instanceof StatisticSection) {
                    return [$ui_factory->legacy("<h4>" . $record->getTitle() . "</h4>")];
                } elseif ($record instanceof Statistic) {

                    $properties[$record->getCountLabel()] = (string)$record->getCount();
                    $properties[$record->getFinalLabel()] = (string)$record->getFinal();

                    if($record->getNotAttended() !== null) {
                        $properties[$this->pluginTxt('statistic_not_attended')] = (string)$record->getNotAttended();
                    }
                    $properties[$this->pluginTxt('statistic_passed')] = (string)$record->getPassed();
                    $properties[$this->pluginTxt('statistic_not_passed')] = (string)$record->getNotPassed();

                    if($record->getNotPassedQuota() !== null) {
                        $perc = 100 * $record->getNotPassedQuota();
                        $properties[$this->pluginTxt('essay_not_passed_quota')] = sprintf('%.1f', $perc) . '%';
                    }

                    if($record->getAveragePoints() !== null) {
                        $properties[$this->pluginTxt('essay_average_points')] = sprintf('%.2f', $record->getAveragePoints());
                    }

                    list($fproperties, $chart) = $this->buildGradesAndGraph($record);

                    if($record->getPseudonym() !== null) {
                        $pseudonym = [$this->pluginTxt("pseudonym") => implode(", ", array_unique($record->getPseudonym()))];
                    }

                    if($record->getOwnGrade() !== null) {
                        $row = $row->withSubheadline($this->pluginTxt("final_result") . ": " . $record->getOwnGrade());
                    }

                    $row = $row->withImportantFields($properties)
                               ->withContent($ui_factory->listing()->descriptive([
                        "" => $this->getUIFactory()->listing()->characteristicValue()->text(array_merge($pseudonym, $properties))
                    ]));

                    if(!empty($fproperties)) {
                        $row = $row->withFurtherFieldsHeadline("<h5>" . $this->pluginTxt('grade_distribution') . "</h5>")
                                   ->withFurtherFields([
                                       "<span class='hidden'>1</span>" => $default_renderer->render($chart),
                                       "<span class='hidden'>2</span>" => $default_renderer->render($this->getUIFactory()->listing()->characteristicValue()->text($fproperties))
                                   ]);
                    }
                }

                return $row->withHeadline($record->getTitle());
            }
        )->withData($component->getStatistics()));
    }

    public function buildGradesAndGraph(Statistic $component): array
    {
        $grades = [];
        $df = new \ILIAS\Data\Factory();
        $c_dimension = $df->dimension()->cardinal();
        $dataset = $df->dataset([$this->pluginTxt("count") => $c_dimension]);
        $i=0;

        foreach($component->getGrades() as $name => $count) {
            $label = \ilStr::shortenTextExtended((string) $name, 50, true);
            if($component->getOwnGrade() !== null && str_starts_with($component->getOwnGrade(), $label)) {
                $name = '<b><span class="glyphicon glyphicon-star" aria-hidden="true"></span>' . $name . '</b>';
                $label = '<b><span class="glyphicon glyphicon-star" aria-hidden="true"></span>' . $label . '</b>';
            }

            $grades[$name . " "] = " " . $count;
            $dataset = $dataset->withPoint($name, [$this->pluginTxt("count") => $count]);
            $i++;
        }

        $bars = [$this->pluginTxt("count") => (new BarConfig())->withColor($df->color("#d38000"))];
        $bar_chart = $this->getUIFactory()->chart()->bar()->vertical("", $dataset, $bars)
                                                          ->withTitleVisible(false)
                                                          ->withLegendVisible(false)
                                                          ->withCustomYAxis((new YAxis())
                                                              ->withBeginAtZero(true)
                                                              ->withStepSize(10));

        return [$grades, $bar_chart];
    }

    private function pluginTxt($var): string
    {
        return $this->txt("rep_robj_xlas_" . $var);
    }
}
