<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin;

use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\View\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\View\Data\StatisticView;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin\CorrectorAdminStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorAdminStatisticsGUI extends BaseGUI
{
    private StatisticViewRepo $statistic_repo;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
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

    public function showStartPage()
    {
        $puf = $this->plugin_ui_factory;
        list($general, $correctors) = $this->statistic_repo->someCorrections(['ass_id' => $this->object->getAssId()]);

        $general_statistic = $puf->statistic()->statistic(
            $this->plugin->txt('corrections_all'),
            $general->getCount(),
            $this->plugin->txt('correction_count'),
            $general->getAttended(),
            $this->plugin->txt('correction_final')
        )->withNotAttended($general->getNotAttended())
         ->withNotPassed($general->getNotPassed())
         ->withPassed($general->getPassed())
         ->withAveragePoints($general->getAveragePoints()??0)
         ->withNotPassedQuota($general->getNotPassedQuota()??0);

        if ($general->isGradesUniform()) {
            $general_statistic = $general_statistic->withGrades($general->getGradeCounts());
        }

        if ($general->isMaxPointUniform()) {
            $general_statistic = $general_statistic->withPoints($general->getPointsCounts());
        }


        $sections = [
            $puf->statistic()->statisticSection($this->plugin->txt("total_statistic")),
            $general_statistic,
            $puf->statistic()->statisticSection($this->plugin->txt("corrector_statistic"))
        ];

        foreach ($correctors as $corrector) {
            $statistic = $puf->statistic()->statistic(
                $corrector->getUser()->getFullname(true),
                $corrector->getCount(),
                $this->plugin->txt('correction_count'),
                $corrector->getAttended(),
                $this->plugin->txt('correction_final')
            )->withNotAttended($corrector->getNotAttended())
                                     ->withNotPassed($corrector->getNotPassed())
                                     ->withPassed($corrector->getPassed())
                                     ->withAveragePoints($corrector->getAveragePoints()??0)
                                     ->withNotPassedQuota($corrector->getNotPassedQuota()??0);

            if ($corrector->isGradesUniform()) {
                $statistic = $statistic->withGrades($corrector->getGradeCounts());
            }

            if ($general->isMaxPointUniform()) {
                $statistic = $statistic->withPoints($corrector->getPointsCounts());
            }
            $sections[] = $statistic;
        }

        $this->tpl->setContent($this->renderer->render(
            $puf->statistic()->extendableStatisticGroup($this->plugin->txt("statistic"), $sections)
        ));
    }
}
