<?php

namespace ILIAS\Plugin\LongEssayAssessment\Collection;

use Edutiek\AssessmentService\Views\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\UI\Component\Input\Container\Filter;
use ILIAS\Plugin\LongEssayAssessment\StatisticHelper;
use ILIAS\Data\UUID\Factory as UUID;
use ilFileDelivery;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Collection\CollectionWriterAdminStatisticsGUI: ILIAS\Plugin\LongEssayAssessment\Collection\CollectionGUI
 */
class CollectionWriterAdminStatisticsGUI
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
    private \ILIAS\Refinery\Factory $refinery;
    private \ilToolbarGUI $toolbar;
    protected \ilLanguage $lng;

    public function __construct(protected \ilLongEssayAssessmentPlugin $plugin, private PluginDIC $plugin_dic, private \ILIAS\DI\Container $dic, private array $object_nodes)
    {
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
        $this->ctrl  =  $dic->ctrl();
        $this->tpl = $dic->ui()->mainTemplate();
        $this->toolbar = $dic->toolbar();
        $this->lng = $dic->language();
        $this->renderer = $dic->ui()->renderer();
        $this->ui_service = $dic->uiService();
        $this->ui_factory = $dic->ui()->factory();
        $this->refinery = $dic->refinery();
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
        $corr = [];

        foreach ($this->object_nodes as $node) {
            $context[(int) $node["obj_id"]] = $node["title"];
        }

        $base_action = $this->ctrl->getFormAction($this, 'showStartPage');
        $filter_gui = $this->ui_service->filter()->standard("xlas_statistics", $base_action, [
            "context" => $this->ui_factory->input()->field()->multiSelect($this->plugin->txt("statistic_context_filter"), $context)
                                         ->withValue($this->ass_ids),
            "name" => $this->ui_factory->input()->field()->text($this->plugin->txt("participants"))
                                        ->withValue("")
        ], [true, true], true, true);
        return $filter_gui;
    }

    public function showStartPage()
    {
        $this->toolbar->addComponent($this->ui_factory->button()->primary(
            $this->plugin->txt("export_statistics"),
            $this->ctrl->getLinkTarget($this, "exportCSV")
        ));
        
        $puf = $this->plugin_dic->uiFactory();

        $filter_gui = $this->buildFilter() ;
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? ['context' => []];

        $ass_ids = array_filter($this->ass_ids, fn ($x) => in_array($x, $filter_data['context']));
        $filter_data['ass_id'] = $ass_ids;

        $general = $this->statistic_repo->someAssessments($filter_data);
        $general_statistic = $this->buildStatistic($general, true, $this->plugin->txt('total_statistic'));

        $sections = [
            $puf->statistic()->statisticSection($this->plugin->txt("total_statistic")),
            $general_statistic,
        ];

        if (count($ass_ids) > 1) {
            foreach ($general->getAssessments() as $assessment) {
                $assessments[$assessment->getAssId()] = $assessment;
            }

            foreach ($ass_ids as $ass_id) {
                $assessment = $assessments[$ass_id] ??
                    $this->plugin_dic->assessment($ass_id, $this->dic->user()->getId())->properties()->get();
                $assessment_statistic = $general->fromAssessent($assessment);
                $sections[] = $this->buildStatistic($assessment_statistic, true);
            }
        }

        $sections[] = $puf->statistic()->statisticSection($this->plugin->txt("writers"));

        foreach ($general->getUsers() as $user) {
            $user_statistic = $general->fromUser($user);
            $sections[] = $this->buildStatistic($user_statistic, true);
        }

        if (count($sections) > 3) {
            $this->tpl->setContent(
                $this->renderer->render(
                    [$filter_gui, $puf->statistic()->extendableStatisticGroup($this->plugin->txt("statistic"), $sections)]
                )
            );
        } else {
            $this->tpl->setContent($this->renderer->render([$filter_gui,  $general_statistic]));
        }
    }

    public function exportCSV(): void
    {
        $filter_gui = $this->buildFilter() ;
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? ['context' => []];

        $ass_ids = array_filter($this->ass_ids, fn ($x) => in_array($x, $filter_data['context']));
        $filter_data['ass_id'] = $ass_ids;

        $general  = $this->statistic_repo->someAssessments($filter_data);
        $views = [];

        foreach ($general->getUsers() as $user) {
            $view = $general->fromUser($user);
            if (!empty($view->getGradingObjects())) {
                $views[] = $view;
            }
        }

        $csv = $this->buildStatisticExport($views, false, false);

        $storage = $this->dic->filesystem()->temp();
        $basedir = ILIAS_DATA_DIR . '/' . CLIENT_ID . '/temp';
        $file = 'xlas/'. (new UUID)->uuid4AsString() . '.csv';
        $storage->write($file, $csv->getCSVString());
        $filename = ilFileDelivery::returnASCIIFilename($this->plugin->txt('export_statistics_writer_file')). '.csv';

        ilFileDelivery::deliverFileAttached($basedir . '/' . $file, $filename, 'text/csv', true);
    }
}
