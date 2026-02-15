<?php

namespace ILIAS\Plugin\LongEssayAssessment\Collection;

use Edutiek\AssessmentService\Views\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\UI\Component\Input\Container\Filter;
use ILIAS\Plugin\LongEssayAssessment\StatisticHelper;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Collection\CollectionWriterStatisticsGUI: ILIAS\Plugin\LongEssayAssessment\Collection\CollectionGUI
 */
class CollectionWriterStatisticsGUI
{
    use StatisticHelper;

    private StatisticViewRepo $statistic_repo;
    private \Edutiek\AssessmentService\Assessment\Data\Writer $writer;
    private \ilCtrlInterface $ctrl;
    private \ilGlobalTemplateInterface $tpl;
    private \ILIAS\UI\Renderer $renderer;
    private \ilUIService $ui_service;
    private \ILIAS\UI\Factory $ui_factory;
    /**
     * @var int[]
     */
    private array $ass_ids;

    public function __construct(protected \ilLongEssayAssessmentPlugin $plugin, private PluginDIC $plugin_dic, private \ILIAS\DI\Container $dic, private array $object_nodes)
    {
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
        $this->ctrl  =  $dic->ctrl();
        $this->tpl = $dic->ui()->mainTemplate();
        $this->renderer = $dic->ui()->renderer();
        $this->ui_service = $dic->uiService();
        $this->ui_factory = $dic->ui()->factory();
        $this->ass_ids = array_map(fn (array $node) => $node['obj_id'], $this->object_nodes);

    }

    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                $cmd = $this->ctrl->getCmd('showStartPage');
                switch ($cmd) {
                    case 'showStartPage':
                    case 'exportCSV':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }
    }

    protected function buildFilter() : Filter\Standard
    {
        $context = [];

        foreach ($this->object_nodes as $node) {
            $context[$node["obj_id"]] = $node["title"];
        }

        $base_action = $this->ctrl->getFormAction($this, 'showStatistics');
        return $this->ui_service->filter()->standard("xlas_statistics", $base_action, [
            "context" => $this->ui_factory->input()->field()->multiSelect($this->plugin->txt("objs_xlas"), $context)
            ->withValue($this->ass_ids)
        ], [true], true, true);
    }

    public function showStartPage()
    {
        $puf = $this->plugin_dic->uiFactory();

        $filter_gui = $this->buildFilter() ;
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? ['context' => []];

        $ass_ids = array_filter($this->ass_ids, fn ($x) => in_array($x, $filter_data['context']));

        $general = $this->statistic_repo->someAssessments(['ass_id' => $ass_ids]);
        $general_statistic = $this->buildStatistic($general, true, $this->plugin->txt('total_statistic'));

        $sections = [
            $puf->statistic()->statisticSection($this->plugin->txt("total_statistic")),
            $general_statistic
        ];

        foreach ($general->getAssessments() as $assessment) {
            $ass_statistic = $general->fromAssessent($assessment);
            $sections[] = $this->buildStatistic($ass_statistic, true);
        }

        if (count($sections) > 2) {
            $this->tpl->setContent(
                $this->renderer->render([$filter_gui, $puf->statistic()->extendableStatisticGroup($this->plugin->txt("statistic"), $sections)])
            );
        } else {
            $this->tpl->setContent($this->renderer->render([$filter_gui,  $general_statistic]));
        }
    }
}
