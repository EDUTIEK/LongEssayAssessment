<?php

namespace ILIAS\Plugin\LongEssayAssessment\Collection;

use Edutiek\AssessmentService\Views\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\UI\Component\Input\Container\Filter;
use Edutiek\AssessmentService\System\User\ReadService as UserService;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Collection\CollectionCorrectorAdminStatisticsGUI: ILIAS\Plugin\LongEssayAssessment\Collection\CollectionGUI
 */
class CollectionCorrectorAdminStatisticsGUI
{
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
    private UserService $user_data_repo;
    private \ilObjUser $user;

    public function __construct(private \ilPlugin $plugin, private PluginDIC $plugin_dic, private \ILIAS\DI\Container $dic, private array $object_nodes)
    {
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
        $this->ctrl  =  $dic->ctrl();
        $this->tpl = $dic->ui()->mainTemplate();
        $this->renderer = $dic->ui()->renderer();
        $this->ui_service = $dic->uiService();
        $this->ui_factory = $dic->ui()->factory();
        $this->refinery = $dic->refinery();
        $this->ass_ids = array_map(fn (array $node) => $node['obj_id'], $this->object_nodes);
        $this->user_data_repo = $this->plugin_dic->system()->user();
        $this->user = $this->dic->user();
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
                                         ->withAdditionalTransformation($this->refinery->to()->listOf($this->refinery->to()->int()))
                                         ->withValue($this->ass_ids),
            "name" => $this->ui_factory->input()->field()->text($this->plugin->txt("participants"))
                                        ->withValue(""),
            "finalized" => $this->ui_factory->input()->field()->numeric($this->plugin->txt("min_finalized_corrections"))
                                           ->withAdditionalTransformation($this->refinery->int()->isGreaterThanOrEqual(0))
                                           ->withAdditionalTransformation($this->refinery->to()->int())
                                           ->withValue(1)
        ], [true, true, true], true, true);
        return $filter_gui;


        $context = [];
        $corr = [];
        $users = [];
        foreach ($this->object_nodes as $node) {
            $context[$node["obj_id"]] = $node["title"];
            foreach ($this->plugin_dic->assessment($node['obj_id'], $this->user->getId())->corrector()->all() as $corrector) {
                $users[] = $corrector->getUserId();
            }
        }

        foreach ($this->user_data_repo->getUsersByIds(array_unique($users)) as $user) {
            if (!isset($corr[$corrector->getUserId()])) {
                $corr[$user->getId()] = $user->getFullname(true);
            }
        }

        $base_action = $this->ctrl->getFormAction($this, 'showStartPage');
        $filter_gui = $this->ui_service->filter()->standard("xlas_statistics", $base_action, [
            "context" => $this->ui_factory->input()->field()->multiSelect($this->plugin->txt("statistic_context_filter"), $context)
                                         ->withValue($this->ass_ids),
            "user_id" => $this->ui_factory->input()->field()->multiSelect($this->plugin->txt("correctors"), $corr)
        ], [true, true], true, true);
        return $filter_gui;
    }

    public function showStartPage()
    {
        $puf = $this->plugin_dic->uiFactory();

        $filter_gui = $this->buildFilter() ;
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? ['context' => []];

        $ass_ids = array_filter($this->ass_ids, fn ($x) => in_array($x, $filter_data['context']));
        $filter_data['ass_id'] = $ass_ids;

        list($general, $correctors)  = $this->statistic_repo->someCorrections($filter_data);

        $general_statistic = $puf->statistic()->statistic(
            $this->plugin->txt('corrections_all'),
            $general->getCount(),
            $this->plugin->txt('correction_count'),
            $general->getAttended(),
            $this->plugin->txt('correction_final')
        )->withNotPassed($general->getNotPassed())
         ->withPassed($general->getPassed())
         ->withAveragePoints($general->getAveragePoints() ?? 0)
         ->withNotPassedQuota($general->getNotPassedQuota() ?? 0);

        if ($general->isGradesUniform()) {
            $general_statistic = $general_statistic->withGrades($general->getGradeCounts());
        }

        if ($general->isMaxPointUniform()) {
            $general_statistic = $general_statistic->withPoints($general->getPointsCounts());
        }

        $sections = [
            $puf->statistic()->statisticSection($this->plugin->txt("total_statistic")),
            $general_statistic,
            $puf->statistic()->statisticSection($this->plugin->txt("correctors")),
        ];

        foreach ($correctors as $corrector_statistic) {
            $statistic = $puf->statistic()->statistic(
                $corrector_statistic->getTitle(),
                $corrector_statistic->getCount(),
                $this->plugin->txt('correction_count'),
                $corrector_statistic->getAttended(),
                $this->plugin->txt('correction_final')
            ) ->withNotPassed($corrector_statistic->getNotPassed())
              ->withPassed($corrector_statistic->getPassed())
              ->withAveragePoints($corrector_statistic->getAveragePoints() ?? 0)
              ->withNotPassedQuota($corrector_statistic->getNotPassedQuota() ?? 0);

            if ($corrector_statistic->isGradesUniform()) {
                $statistic = $statistic->withGrades($corrector_statistic->getGradeCounts());
            }

            if ($general->isMaxPointUniform()) {
                $statistic = $statistic->withPoints($corrector_statistic->getPointsCounts());
            }
            $sections[] = $statistic;
        }

        if (count($sections) > 3) {
            $this->tpl->setContent(
                $this->renderer->render([$filter_gui, $puf->statistic()->extendableStatisticGroup($this->plugin->txt("statistic"), $sections)])
            );
        } else {
            $this->tpl->setContent($this->renderer->render([$filter_gui,  $general_statistic]));
        }
    }
}
