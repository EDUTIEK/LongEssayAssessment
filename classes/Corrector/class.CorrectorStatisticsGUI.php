<?php

namespace ILIAS\Plugin\LongEssayAssessment\Corrector;

use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use ILIAS\Plugin\LongEssayAssessment\View\Data\StatisticViewRepo;
use ILIAS\Plugin\LongEssayAssessment\View\Data\StatisticView;
use Edutiek\AssessmentService\Assessment\Data\Corrector;

/**
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStatisticsGUI: ilObjLongEssayAssessmentGUI
 */
class CorrectorStatisticsGUI extends BaseGUI
{
    private StatisticViewRepo $statistic_repo;
    private ?Corrector $corrector;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->statistic_repo = $this->plugin->dic()->view()->statistic();
        $this->corrector = $this->assessment_api->corrector()->oneByUserId($this->user->getId());

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
        $own = $this->statistic_repo->oneCorrector($this->corrector->getId(), ['ass_id' => $this->object->getAssId()]);
        list($general, $all) = $this->statistic_repo->someCorrections(['ass_id' => $this->object->getAssId()]);


        $general_statistic = $puf->statistic()->statistic(
            $this->plugin->txt('corrections_all'),
            $general->getCount(),
            $this->plugin->txt('essay_count'),
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

        $own_statistic = $puf->statistic()->statistic(
            $this->plugin->txt('tab_corrector'),
            $own->getCount(),
            $this->plugin->txt('correction_count'),
            $own->getAttended(),
            $this->plugin->txt('correction_final')
        )->withNotAttended($own->getNotAttended())
                                 ->withNotPassed($own->getNotPassed())
                                 ->withPassed($own->getPassed())
                                 ->withAveragePoints($own->getAveragePoints()??0)
                                 ->withNotPassedQuota($own->getNotPassedQuota()??0);

        if ($own->isGradesUniform()) {
            $own_statistic = $own_statistic->withGrades($own->getGradeCounts());
        }

        if ($own->isMaxPointUniform()) {
            $own_statistic = $own_statistic->withPoints($own->getPointsCounts());
        }

        $this->tpl->setContent($this->renderer->render(
            $puf->statistic()->graphStatisticGroup($this->plugin->txt("statistic"), [$own_statistic, $general_statistic,])
        ));
    }

}
