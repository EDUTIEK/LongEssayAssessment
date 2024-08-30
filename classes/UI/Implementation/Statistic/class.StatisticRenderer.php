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
    protected function getTemplatePath($name)
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
                scandir(dirname(__FILE__) . "/../../../templates/Item"), function ($item) {
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
            $root = $root->withCard(
                $this->getUIFactory()->card()->standard("<h5>" . $this->pluginTxt('grade_distribution') . "</h5>")->withSections([
                    $this->getUIFactory()->legacy($chart->getHTML() ?? ""),
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
            if($statistic instanceof Statistic){
                $panels[] = $this->buildStatistic($statistic);
            }else
            {
                $panels[] = $this->getUIFactory()->panel()->sub($statistic->getTitle(), []);
            }
        }

        $root = $this->getUIFactory()->panel()->report($component->getTitle(),$panels);

        return $default_renderer->render($root);
    }

    protected function renderExtendableStatisticGroup(ExtendableStatisticGroup $component, Renderer $default_renderer): string
    {
        return $default_renderer->render($this->getUIFactory()->table()->presentation(
            $component->getTitle(),
            [],
            function (PresentationRow $row, StatisticItem $record, $ui_factory, $environment) use ($default_renderer){ //mapping-closure
                $pseudonym = [];
                $fproperties = [];
                $properties = [];

                if($record instanceof StatisticSection) {
                    return [$ui_factory->legacy("<h4>" . $record->getTitle() . "</h4>")];
                }elseif ($record instanceof Statistic) {

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
                        $pseudonym = [$this->pluginTxt("pseudonym") => implode(", ", array_unique($record["pseudonym"]))];
                    }

                    if($record->getOwnGrade() !== null){
                        $row = $row->withSubheadline($this->pluginTxt("final_result") . ": " . $record->getOwnGrade());
                    }
                }

                $chart_js = function ($id) use ($chart) {
                    $html = $chart->getHTML();
                    $js = 'var htmlContent = ' . json_encode($html) . ';
                           $("main").append("<div id=\"' . $id . '_chart\">" + htmlContent + "</div>");
                           setTimeout(function(){$("#' . $id . '_chart").appendTo( $("#' . $id . '").find(".chart"));}, 50);
                          ';
                    return $js;
                };
                return $row
                    ->withAdditionalOnLoadCode($chart_js)
                    ->withHeadline($record->getTitle())
                    ->withImportantFields($properties)
                    ->withContent($ui_factory->listing()->descriptive([
                        "" => $this->getUIFactory()->listing()->characteristicValue()->text(array_merge($pseudonym, $properties))
                    ]))
                    ->withFurtherFieldsHeadline("<h5>" . $this->pluginTxt('grade_distribution') . "</h5>")
                    ->withFurtherFields([
                        "<span class='hidden'>1</span>" => "<div class='chart'></div>",
                        "<span class='hidden'>2</span>" => $default_renderer->render($this->getUIFactory()->listing()->characteristicValue()->text($fproperties))
                    ]);
            }
        )->withData($component->getStatistics()));
    }

    public function buildGradesAndGraph(Statistic $component): array
    {
        $grades = [];
        $chart = \ilChart::getInstanceByType(\ilChart::TYPE_GRID, uniqid("grade_statisitc"));
        $i=0;
        $labels = [];

        foreach($component->getGrades() as $name => $count) {
            $label = \ilUtil::shortenText($name, 50, true);

            if($component->getOwnGrade() !== null && str_starts_with($component->getOwnGrade(), $label)){
                $name = '<b><span class="glyphicon glyphicon-star" aria-hidden="true"></span>' . $name . '</b>';
                $label = '<b><span class="glyphicon glyphicon-star" aria-hidden="true"></span>' . $label . '</b>';
            }

            $grades[$name . " "] = " " . $count;
            $data = $chart->getDataInstance(\ilChartGrid::DATA_BARS);
            $data->setLabel($name);
            $data->setBarOptions(0.5, "center", false);
            $data->setFill(1);
            $data->addPoint($i, $count);
            $chart->addData($data);

            $labels[$i] = $label;
            $i++;
        }

        $chart->setTicks($labels, false, true);
        $chart->setSize("100%", 200);
        $chart->setAutoResize(true);
        $chart->setYAxisToInteger(true);
        $chart->setColors($this->getGraphColors());

        return [$grades, $chart];
    }

    private function getGraphColors(): array
    {
        // Source: SurveyQuestionEvaluation::getChartColors
        return array(
            // flot "default" theme
            "#edc240", "#afd8f8", "#cb4b4b", "#4da74d", "#9440ed",
            // http://godsnotwheregodsnot.blogspot.de/2012/09/color-distribution-methodology.html
            "#1CE6FF", "#FF34FF", "#FF4A46", "#008941", "#006FA6", "#A30059",
            "#FFDBE5", "#7A4900", "#0000A6", "#63FFAC", "#B79762", "#004D43", "#8FB0FF", "#997D87",
            "#5A0007", "#809693", "#FEFFE6", "#1B4400", "#4FC601", "#3B5DFF", "#4A3B53", "#FF2F80",
            "#61615A", "#BA0900", "#6B7900", "#00C2A0", "#FFAA92", "#FF90C9", "#B903AA", "#D16100",
            "#DDEFFF", "#000035", "#7B4F4B", "#A1C299", "#300018", "#0AA6D8", "#013349", "#00846F",
            "#372101", "#FFB500", "#C2FFED", "#A079BF", "#CC0744", "#C0B9B2", "#C2FF99", "#001E09",
            "#00489C", "#6F0062", "#0CBD66", "#EEC3FF", "#456D75", "#B77B68", "#7A87A1", "#788D66",
            "#885578", "#FAD09F", "#FF8A9A", "#D157A0", "#BEC459", "#456648", "#0086ED", "#886F4C",
            "#34362D", "#B4A8BD", "#00A6AA", "#452C2C", "#636375", "#A3C8C9", "#FF913F", "#938A81",
            "#575329", "#00FECF", "#B05B6F", "#8CD0FF", "#3B9700", "#04F757", "#C8A1A1", "#1E6E00",
            "#7900D7", "#A77500", "#6367A9", "#A05837", "#6B002C", "#772600", "#D790FF", "#9B9700",
            "#549E79", "#FFF69F", "#201625", "#72418F", "#BC23FF", "#99ADC0", "#3A2465", "#922329",
            "#5B4534", "#FDE8DC", "#404E55", "#0089A3", "#CB7E98", "#A4E804", "#324E72", "#6A3A4C"
        );
    }

    private function pluginTxt($var): string
    {
        return $this->txt( "rep_robj_xlas_" . $var);
    }
}